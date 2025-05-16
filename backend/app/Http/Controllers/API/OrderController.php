<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Address;

class OrderController extends Controller
{
    /**
     * Mendapatkan daftar pesanan pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Order::with(['address'])
                        ->where('user_id', $user->id);
        
        // Filter berdasarkan status
        if ($request->has('status') && in_array($request->status, array_keys(Order::$statuses))) {
            $query->where('status', $request->status);
        }
        
        // Pengurutan
        $query->orderBy('created_at', 'desc');
        
        // Paginasi
        $perPage = $request->per_page ?? 10;
        $orders = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
    
    /**
     * Melihat detail pesanan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Periksa apakah pesanan milik pengguna atau penjual yang terkait
        $isSeller = $user->hasRole('seller');
        $isOrderOwner = $order->user_id === $user->id;
        $isSellerOfOrderItem = false;
        
        if ($isSeller) {
            $isSellerOfOrderItem = $order->orderItems()->where('penjual_id', $user->id)->exists();
        }
        
        if (!$isOrderOwner && !$isSellerOfOrderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $order->load([
            'address', 
            'orderItems',
            'orderItems.product',
            'orderItems.seller'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
    
    /**
     * Mendapatkan item pesanan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function items(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Periksa apakah pesanan milik pengguna atau penjual yang terkait
        $isSeller = $user->hasRole('seller');
        $isOrderOwner = $order->user_id === $user->id;
        $isSellerOfOrderItem = false;
        
        if ($isSeller) {
            $isSellerOfOrderItem = $order->orderItems()->where('penjual_id', $user->id)->exists();
        }
        
        if (!$isOrderOwner && !$isSellerOfOrderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Jika penjual, kembalikan hanya item yang mereka jual
        if ($isSeller && !$isOrderOwner) {
            $items = $order->orderItems()
                          ->with(['product', 'seller'])
                          ->where('penjual_id', $user->id)
                          ->get();
        } else {
            $items = $order->orderItems()
                          ->with(['product', 'seller'])
                          ->get();
        }
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
    
    /**
     * Checkout dari keranjang.
     * 
     * PENTING: Logika stok pada aplikasi ini adalah:
     * 1. Saat produk dimasukkan ke keranjang, stok hanya dicek ketersediaannya, TIDAK dikurangi.
     * 2. Stok hanya dikurangi pada saat proses checkout (dalam fungsi ini).
     * 3. Jika pesanan dibatalkan, stok akan dikembalikan (lihat fungsi cancelOrder).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alamat_id' => 'required|exists:addresses,id',
            'metode_pengiriman' => 'required|string',
            'biaya_kirim' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|string',
            'catatan' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        
        // Verifikasi alamat milik pengguna
        $address = Address::find($request->alamat_id);
        if (!$address || $address->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak valid'
            ], 400);
        }
        
        // Dapatkan keranjang pengguna
        $cart = Cart::where('user_id', $user->id)->first();
        if (!$cart || $cart->items()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang kosong'
            ], 400);
        }
        
        // Validasi item keranjang (stok, dll)
        $cartItems = $cart->items()->with('product')->get();
        $subtotal = 0;
        $invalidItems = [];
        
        foreach ($cartItems as $item) {
            $product = $item->product;
            
            // Cek ketersediaan produk
            if (!$product || !$product->aktif) {
                $invalidItems[] = [
                    'product_id' => $item->produk_id,
                    'reason' => 'Produk tidak tersedia'
                ];
                continue;
            }
            
            // Cek stok - pastikan stok masih mencukupi saat checkout
            if ($product->stok < $item->jumlah) {
                $invalidItems[] = [
                    'product_id' => $item->produk_id,
                    'reason' => 'Stok tidak mencukupi',
                    'available' => $product->stok,
                    'requested' => $item->jumlah
                ];
                continue;
            }
            
            // Hitung subtotal
            $subtotal += $product->harga * $item->jumlah;
        }
        
        // Jika ada item tidak valid
        if (count($invalidItems) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Beberapa item tidak valid',
                'data' => [
                    'invalid_items' => $invalidItems
                ]
            ], 400);
        }
        
        // Hitung biaya
        $biayaKirim = $request->biaya_kirim;
        $pajak = 0; // Sesuaikan jika ada pajak
        $total = $subtotal + $biayaKirim + $pajak;
        
        try {
            DB::beginTransaction();
            
            // Buat pesanan
            $order = Order::create([
                'user_id' => $user->id,
                'nomor_pesanan' => Order::generateOrderNumber(),
                'status' => 'menunggu',
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_pembayaran' => 'menunggu',
                'alamat_id' => $address->id,
                'metode_pengiriman' => $request->metode_pengiriman,
                'biaya_kirim' => $biayaKirim,
                'subtotal' => $subtotal,
                'pajak' => $pajak,
                'total' => $total,
                'catatan' => $request->catatan
            ]);
            
            // Buat item pesanan
            foreach ($cartItems as $item) {
                $product = $item->product;
                
                // Buat item pesanan
                OrderItem::create([
                    'pesanan_id' => $order->id,
                    'produk_id' => $product->id,
                    'penjual_id' => $product->penjual_id,
                    'nama_produk' => $product->nama,
                    'jumlah' => $item->jumlah,
                    'harga' => $product->harga,
                    'subtotal' => $product->harga * $item->jumlah
                ]);
                
                // PENGURANGAN STOK: Stok dikurangi hanya pada saat checkout
                $product->stok -= $item->jumlah;
                $product->save();
            }
            
            // Kosongkan keranjang
            $cart->items()->delete();
            
            // Notifikasi untuk pelanggan
            $user->notifications()->create([
                'judul' => 'Pesanan Baru',
                'isi' => "Pesanan #{$order->nomor_pesanan} telah dibuat. Silakan lakukan pembayaran.",
                'jenis' => 'pesanan',
                'pesanan_id' => $order->id,
                'tautan' => '/pesanan/' . $order->id,
            ]);
            
            // Notifikasi untuk setiap penjual
            $sellerGroups = $cartItems->groupBy('product.penjual_id');
            foreach ($sellerGroups as $sellerId => $items) {
                $seller = $items->first()->product->seller;
                $seller->notifications()->create([
                    'judul' => 'Pesanan Baru',
                    'isi' => "Pesanan baru #{$order->nomor_pesanan} dari {$user->name}.",
                    'jenis' => 'pesanan',
                    'pesanan_id' => $order->id,
                    'tautan' => '/pesanan/' . $order->id,
                ]);
            }
            
            DB::commit();
            
            $order->load(['address', 'orderItems', 'orderItems.product', 'orderItems.seller']);
            
            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil',
                'data' => $order
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Checkout gagal: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Membatalkan pesanan.
     * 
     * PENTING: Saat pesanan dibatalkan, stok produk dikembalikan
     * sesuai dengan jumlah yang telah dikurangi sebelumnya.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function cancelOrder(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Periksa apakah pesanan milik pengguna
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Periksa apakah pesanan dapat dibatalkan
        if (!in_array($order->status, ['menunggu', 'dibayar'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak dapat dibatalkan'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Kembalikan stok produk - PENTING: stok dikembalikan saat pesanan dibatalkan
            foreach ($order->orderItems as $item) {
                $product = Product::find($item->produk_id);
                if ($product) {
                    $product->stok += $item->jumlah;
                    $product->save();
                }
            }
            
            // Update status pesanan
            $order->status = 'dibatalkan';
            $order->save();
            
            // Notifikasi untuk pelanggan
            $user->notifications()->create([
                'judul' => 'Pesanan Dibatalkan',
                'isi' => "Pesanan #{$order->nomor_pesanan} telah dibatalkan.",
                'jenis' => 'pesanan',
                'pesanan_id' => $order->id,
                'tautan' => '/pesanan/' . $order->id,
            ]);
            
            // Notifikasi untuk setiap penjual terkait
            $sellerGroups = $order->orderItems->groupBy('penjual_id');
            foreach ($sellerGroups as $sellerId => $items) {
                $seller = $items->first()->seller;
                $seller->notifications()->create([
                    'judul' => 'Pesanan Dibatalkan',
                    'isi' => "Pesanan #{$order->nomor_pesanan} dari {$user->name} telah dibatalkan.",
                    'jenis' => 'pesanan',
                    'pesanan_id' => $order->id,
                    'tautan' => '/pesanan/' . $order->id,
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibatalkan',
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Memperbarui status pesanan (untuk penjual).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:menunggu,dibayar,diproses,dikirim,selesai,dibatalkan'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        
        // Periksa apakah pengguna adalah penjual terkait
        if (!$user->hasRole('seller') || !$order->orderItems()->where('penjual_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Validasi perubahan status
        $newStatus = $request->status;
        
        try {
            $order->updateStatus($newStatus);
            
            return response()->json([
                'success' => true,
                'message' => 'Status pesanan berhasil diperbarui',
                'data' => $order
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status pesanan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mendapatkan pesanan untuk penjual.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sellerOrders(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Dapatkan pesanan yang memiliki item yang dijual oleh penjual
        $orders = Order::with(['user', 'address'])
            ->whereHas('orderItems', function ($query) use ($user) {
                $query->where('penjual_id', $user->id);
            });
        
        // Filter berdasarkan status
        if ($request->has('status') && in_array($request->status, array_keys(Order::$statuses))) {
            $orders->where('status', $request->status);
        }
        
        // Pengurutan
        $orders->orderBy('created_at', 'desc');
        
        // Paginasi
        $perPage = $request->per_page ?? 10;
        $result = $orders->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * Menerima notifikasi pembayaran (webhook untuk integrasi pembayaran).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function paymentNotification(Request $request, Order $order)
    {
        // Implementasi ini adalah contoh, asumsi menggunakan API pembayaran eksternal
        // Verifikasi signature/auth dari payment gateway
        
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'payment_status' => 'required|string',
            'amount' => 'required|numeric'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Validasi jumlah pembayaran
        if ((float) $request->amount !== (float) $order->total) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah pembayaran tidak sesuai'
            ], 400);
        }
        
        try {
            // Update informasi pembayaran
            $order->id_pembayaran = $request->transaction_id;
            
            // Update status pembayaran
            if ($request->payment_status === 'paid' || $request->payment_status === 'settlement') {
                $order->updatePaymentStatus('dibayar');
            } elseif ($request->payment_status === 'failed' || $request->payment_status === 'expire') {
                $order->updatePaymentStatus('gagal');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi pembayaran berhasil diproses'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses notifikasi pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }
}