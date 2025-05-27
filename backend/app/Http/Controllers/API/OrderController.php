<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Address;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;

class OrderController extends Controller
{
    /**
     * Mendapatkan daftar pesanan pengguna.
     * PERBAIKAN: Eager loading yang konsisten
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Order::where('user_id', $user->id);
        
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $allowedSortFields = ['nomor_pesanan', 'total', 'status', 'created_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // PERBAIKAN: Eager loading yang benar
        $orders = $query->with([
            'orderItems:pesanan_id,produk_id,nama_produk,jumlah,harga,subtotal',
            'orderItems.product:id,nama,gambar,jenis_ikan',
            'address:id,nama_penerima,telepon,alamat_lengkap,provinsi,kota,kecamatan,kode_pos'
        ])->paginate($request->per_page ?? 10);
        
        return response()->json([
            'success' => true,
            'data' => new OrderCollection($orders)
        ]);
    }

    /**
     * Menampilkan detail pesanan.
     * PERBAIKAN: Eager loading yang lebih spesifik
     */
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        
        if ($user->id !== $order->user_id && !$order->orderItems->contains('penjual_id', $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }
        
        // PERBAIKAN: Load relasi dengan select spesifik
        $order->load([
            'user:id,name,email,phone', 
            'address:id,nama_penerima,telepon,alamat_lengkap,provinsi,kota,kecamatan,kode_pos,alamat_utama', 
            'orderItems:id,pesanan_id,produk_id,penjual_id,nama_produk,jumlah,harga,subtotal', 
            'orderItems.product:id,nama,gambar,jenis_ikan',
            'orderItems.seller:id,name'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => new OrderResource($order)
        ]);
    }

    /**
     * PERBAIKAN: Method untuk mengambil items pesanan secara terpisah
     */
    public function items($id)
    {
        try {
            $user = request()->user();
            
            $order = Order::where('id', $id)
                         ->where('user_id', $user->id)
                         ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak ditemukan'
                ], 404);
            }

            $orderItems = OrderItem::where('pesanan_id', $order->id)
                                  ->with([
                                      'product:id,nama,deskripsi,gambar,jenis_ikan,spesies_ikan'
                                  ])
                                  ->get();

            $formattedItems = $orderItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'produk_id' => $item->produk_id,
                    'nama_produk' => $item->nama_produk,
                    'jumlah' => $item->jumlah,
                    'harga' => $item->harga,
                    'subtotal' => $item->subtotal,
                    'harga_formatted' => 'Rp ' . number_format($item->harga, 0, ',', '.'),
                    'subtotal_formatted' => 'Rp ' . number_format($item->subtotal, 0, ',', '.'),
                    'produk' => $item->product ? [
                        'id' => $item->product->id,
                        'nama' => $item->product->nama,
                        'deskripsi' => $item->product->deskripsi,
                        'gambar' => $item->product->gambar,
                        'jenis_ikan' => $item->product->jenis_ikan,
                        'spesies_ikan' => $item->product->spesies_ikan,
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedItems
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proses checkout dari keranjang.
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
        
        try {
            DB::beginTransaction();
            
            // Kunci semua produk untuk validasi stok - mencegah race condition
            $cartItems = $cart->items()->with(['product' => function($query) {
                $query->lockForUpdate();
            }])->get();
            
            // Validasi item keranjang (stok, dll)
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
                
                // Cek stok - validasi real-time saat checkout
                if ($product->stok < $item->jumlah) {
                    $invalidItems[] = [
                        'product_id' => $item->produk_id,
                        'product_name' => $product->nama,
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
                DB::rollBack();
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
            
            // Hapus cache keranjang
            Cache::forget('cart_' . $user->id);
            
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
                if ($seller) {
                    $seller->notifications()->create([
                        'judul' => 'Pesanan Baru',
                        'isi' => "Pesanan baru #{$order->nomor_pesanan} dari {$user->name}.",
                        'jenis' => 'pesanan',
                        'pesanan_id' => $order->id,
                        'tautan' => '/pesanan/' . $order->id,
                    ]);
                }
            }
            
            DB::commit();
            
            $order->load(['address', 'orderItems', 'orderItems.product', 'orderItems.seller']);
            
            return response()->json([
                'success' => true,
                'message' => 'Checkout berhasil',
                'data' => new OrderResource($order)
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log error untuk debugging
            Log::error('Checkout failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'cart_id' => $cart->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Checkout gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Membatalkan pesanan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function cancelOrder(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Pastikan pesanan milik pengguna
        if ($user->id !== $order->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }
        
        // Cek status pesanan
        if (!in_array($order->status, ['menunggu', 'diproses'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak dapat dibatalkan dengan status saat ini'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update status
            $order->status = 'dibatalkan';
            $order->status_pembayaran = 'gagal';
            $order->save();
            
            // Kembalikan stok produk - PENTING: stok dikembalikan saat pesanan dibatalkan
            foreach ($order->orderItems as $item) {
                $product = Product::find($item->produk_id);
                if ($product) {
                    $product->stok += $item->jumlah;
                    $product->save();
                }
            }
            
            // Notifikasi untuk pelanggan
            $user->notifications()->create([
                'judul' => 'Pesanan Dibatalkan',
                'isi' => "Pesanan #{$order->nomor_pesanan} telah dibatalkan.",
                'jenis' => 'pesanan',
                'pesanan_id' => $order->id,
                'tautan' => '/pesanan/' . $order->id,
            ]);
            
            // Notifikasi untuk penjual
            $sellerIds = $order->orderItems->pluck('penjual_id')->unique();
            foreach ($sellerIds as $sellerId) {
                $seller = \App\Models\User::find($sellerId);
                if ($seller) {
                    $seller->notifications()->create([
                        'judul' => 'Pesanan Dibatalkan',
                        'isi' => "Pesanan #{$order->nomor_pesanan} telah dibatalkan oleh pembeli.",
                        'jenis' => 'pesanan',
                        'pesanan_id' => $order->id,
                        'tautan' => '/pesanan/' . $order->id,
                    ]);
                }
            }
            
            DB::commit();
            
            $order->load(['address', 'orderItems', 'orderItems.product', 'orderItems.seller']);
            
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibatalkan',
                'data' => new OrderResource($order)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Cancel order failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengubah status pesanan (untuk penjual).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:diproses,dikirim,selesai,dibatalkan'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        
        // Pastikan pengguna adalah penjual yang terkait dengan pesanan
        $isSellerInvolved = $order->orderItems->contains('penjual_id', $user->id);
        
        if (!$isSellerInvolved && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengubah status pesanan ini'
            ], 403);
        }
        
        // Validasi perubahan status
        $currentStatus = $order->status;
        $newStatus = $request->status;
        
        $validTransitions = [
            'menunggu' => ['diproses', 'dibatalkan'],
            'diproses' => ['dikirim', 'dibatalkan'],
            'dikirim' => ['selesai'],
            'selesai' => [],
            'dibatalkan' => []
        ];
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'success' => false,
                'message' => "Perubahan status dari '$currentStatus' ke '$newStatus' tidak valid"
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update status
            $order->status = $newStatus;
            
            // Jika dibatalkan, kembalikan stok
            if ($newStatus === 'dibatalkan') {
                $order->status_pembayaran = 'dibatalkan';
                
                // Kembalikan stok produk
                foreach ($order->orderItems as $item) {
                    if ($item->penjual_id === $user->id || $user->hasRole('admin')) {
                        $product = Product::find($item->produk_id);
                        if ($product) {
                            $product->stok += $item->jumlah;
                            $product->save();
                        }
                    }
                }
            }
            
            $order->save();
            
            // Notifikasi untuk pembeli
            $buyer = $order->user;
            if ($buyer) {
                $statusLabels = [
                    'diproses' => 'sedang diproses oleh penjual',
                    'dikirim' => 'telah dikirim',
                    'selesai' => 'telah selesai',
                    'dibatalkan' => 'telah dibatalkan oleh penjual'
                ];
                
                $buyer->notifications()->create([
                    'judul' => 'Status Pesanan Berubah',
                    'isi' => "Pesanan #{$order->nomor_pesanan} {$statusLabels[$newStatus]}.",
                    'jenis' => 'pesanan',
                    'pesanan_id' => $order->id,
                    'tautan' => '/pesanan/' . $order->id,
                ]);
            }
            
            DB::commit();
            
            $order->load(['address', 'orderItems', 'orderItems.product', 'orderItems.seller']);
            
            return response()->json([
                'success' => true,
                'message' => 'Status pesanan berhasil diperbarui',
                'data' => new OrderResource($order)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Update order status failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status pesanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menandai pesanan sebagai selesai (untuk pembeli).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function completeOrder(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Pastikan pesanan milik pengguna
        if ($user->id !== $order->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan'
            ], 404);
        }
        
        // Cek status pesanan
        if ($order->status !== 'dikirim') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pesanan dengan status dikirim yang dapat ditandai selesai'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Update status
            $order->status = 'selesai';
            $order->tanggal_selesai = now();
            $order->save();
            
            // Notifikasi untuk pembeli
            $user->notifications()->create([
                'judul' => 'Pesanan Selesai',
                'isi' => "Terima kasih telah berbelanja! Pesanan #{$order->nomor_pesanan} telah selesai.",
                'jenis' => 'pesanan',
                'pesanan_id' => $order->id,
                'tautan' => '/pesanan/' . $order->id,
            ]);
            
            // Notifikasi untuk penjual
            $sellerIds = $order->orderItems->pluck('penjual_id')->unique();
            foreach ($sellerIds as $sellerId) {
                $seller = \App\Models\User::find($sellerId);
                if ($seller) {
                    $seller->notifications()->create([
                        'judul' => 'Pesanan Selesai',
                        'isi' => "Pembeli telah menerima dan menyelesaikan pesanan #{$order->nomor_pesanan}.",
                        'jenis' => 'pesanan',
                        'pesanan_id' => $order->id,
                        'tautan' => '/pesanan/' . $order->id,
                    ]);
                }
            }
            
            DB::commit();
            
            $order->load(['address', 'orderItems', 'orderItems.product', 'orderItems.seller']);
            
            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil ditandai selesai',
                'data' => new OrderResource($order)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Complete order failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'order_id' => $order->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan pesanan: ' . $e->getMessage()
            ], 500);
        }
    }
}