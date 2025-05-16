<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartController extends Controller
{
    /**
     * Mendapatkan keranjang pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Cek apakah pengguna memiliki keranjang
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            // Buat keranjang baru jika belum ada
            $cart = Cart::create([
                'user_id' => $user->id
            ]);
        }

        $cartItems = $cart->items()->with(['product', 'product.category'])->get();

        // Hitung total harga
        $totalPrice = 0;
        $validItems = [];

        foreach ($cartItems as $item) {
            // Cek stok dan status aktif produk
            if ($item->product->aktif && $item->product->stok > 0) {
                // Adjust quantity if it's more than available stock
                if ($item->jumlah > $item->product->stok) {
                    $item->jumlah = $item->product->stok;
                    $item->save();
                }
                
                $totalPrice += $item->subtotal;
                $validItems[] = $item;
            } else {
                // Remove invalid items
                $item->delete();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => [
                    'id' => $cart->id,
                    'total_price' => $totalPrice,
                    'item_count' => count($validItems)
                ],
                'items' => $validItems
            ]
        ]);
    }

    /**
     * Menambahkan produk ke keranjang.
     * Catatan: Tidak mengurangi stok, hanya memeriksa ketersediaan
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produk_id' => 'required|exists:products,id',
            'jumlah' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $productId = $request->produk_id;
        $quantity = $request->jumlah;

        // Cek ketersediaan produk
        $product = Product::find($productId);
        if (!$product || !$product->aktif || $product->stok <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak tersedia'
            ], 400);
        }

        // Cek jumlah yang tersedia
        if ($quantity > $product->stok) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah melebihi stok tersedia',
                'data' => [
                    'available_stock' => $product->stok
                ]
            ], 400);
        }

        // Cek atau buat keranjang
        $cart = Cart::firstOrCreate([
            'user_id' => $user->id
        ]);

        // Cek apakah produk sudah ada di keranjang
        $cartItem = CartItem::where('keranjang_id', $cart->id)
                            ->where('produk_id', $productId)
                            ->first();

        if ($cartItem) {
            // Jika sudah ada, update jumlah
            $newQuantity = $cartItem->jumlah + $quantity;
            
            // Pastikan tidak melebihi stok
            if ($newQuantity > $product->stok) {
                $newQuantity = $product->stok;
            }
            
            $cartItem->jumlah = $newQuantity;
            $cartItem->save();
            
            $message = 'Produk berhasil diperbarui dalam keranjang';
        } else {
            // Jika belum ada, tambahkan item baru
            $cartItem = CartItem::create([
                'keranjang_id' => $cart->id,
                'produk_id' => $productId,
                'jumlah' => $quantity
            ]);
            
            $message = 'Produk berhasil ditambahkan ke keranjang';
        }

        $cartItem->load(['product', 'product.category']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $cartItem
        ]);
    }

    /**
     * Mengubah jumlah item dalam keranjang.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CartItem  $cartItem
     * @return \Illuminate\Http\Response
     */
    public function updateCartItem(Request $request, CartItem $cartItem)
    {
        $validator = Validator::make($request->all(), [
            'jumlah' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Pastikan item keranjang milik pengguna
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || $cartItem->keranjang_id !== $cart->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan'
            ], 404);
        }

        // Cek ketersediaan produk
        $product = $cartItem->product;
        if (!$product || !$product->aktif || $product->stok <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak tersedia'
            ], 400);
        }

        // Cek jumlah yang tersedia
        $quantity = $request->jumlah;
        if ($quantity > $product->stok) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah melebihi stok tersedia',
                'data' => [
                    'available_stock' => $product->stok
                ]
            ], 400);
        }

        // Update jumlah
        $cartItem->jumlah = $quantity;
        $cartItem->save();
        $cartItem->load(['product', 'product.category']);

        return response()->json([
            'success' => true,
            'message' => 'Item keranjang berhasil diperbarui',
            'data' => $cartItem
        ]);
    }

    /**
     * Menghapus item dari keranjang.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CartItem  $cartItem
     * @return \Illuminate\Http\Response
     */
    public function removeFromCart(Request $request, CartItem $cartItem)
    {
        // Pastikan item keranjang milik pengguna
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart || $cartItem->keranjang_id !== $cart->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item tidak ditemukan'
            ], 404);
        }

        // Hapus item
        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus dari keranjang'
        ]);
    }

    /**
     * Mengosongkan keranjang.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function clearCart(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Keranjang berhasil dikosongkan'
        ]);
    }
}