<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\Product;
use App\Models\OrderItem;

class ReviewController extends Controller
{
    /**
     * Mendapatkan semua ulasan untuk pengguna yang login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $reviews = Review::with(['product', 'orderItem', 'reviewReply', 'reviewReply.user'])
                        ->where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    /**
     * Menyimpan ulasan baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produk_id' => 'required|exists:products,id',
            'item_pesanan_id' => 'required|exists:order_items,id',
            'rating' => 'required|integer|min:1|max:5',
            'komentar' => 'required|string',
            'gambar.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        // Cek apakah item pesanan milik pengguna
        $orderItem = OrderItem::find($request->item_pesanan_id);
        if (!$orderItem || $orderItem->order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item pesanan tidak valid'
            ], 400);
        }
        
        // Cek apakah produk sesuai dengan item pesanan
        if ($orderItem->produk_id != $request->produk_id) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak sesuai dengan item pesanan'
            ], 400);
        }
        
        // Cek apakah pesanan sudah selesai
        if ($orderItem->order->status !== 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan belum selesai'
            ], 400);
        }
        
        // Cek apakah item ini sudah diulas
        $existingReview = Review::where('user_id', $user->id)
                              ->where('produk_id', $request->produk_id)
                              ->where('item_pesanan_id', $request->item_pesanan_id)
                              ->first();
                              
        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memberikan ulasan untuk item ini'
            ], 400);
        }
        
        // Upload gambar jika ada
        $imageNames = [];
        if ($request->hasFile('gambar')) {
            foreach ($request->file('gambar') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/reviews', $imageName);
                $imageNames[] = 'reviews/' . $imageName;
            }
        }
        
        // Buat ulasan
        $review = Review::create([
            'user_id' => $user->id,
            'produk_id' => $request->produk_id,
            'item_pesanan_id' => $request->item_pesanan_id,
            'rating' => $request->rating,
            'komentar' => $request->komentar,
            'gambar' => $imageNames
        ]);
        
        // Update rating produk
        $product = Product::find($request->produk_id);
        $product->updateRating();
        
        // Buat notifikasi untuk penjual
        $product->seller->notifications()->create([
            'judul' => 'Ulasan Baru',
            'isi' => "{$user->name} memberikan ulasan untuk produk {$product->nama}.",
            'jenis' => 'pesanan',
            'pesanan_id' => $orderItem->pesanan_id,
            'tautan' => '/produk/' . $product->id,
        ]);
        
        $review->load(['product', 'orderItem', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil ditambahkan',
            'data' => $review
        ], 201);
    }

    /**
     * Menampilkan ulasan tertentu.
     *
     * @param  \App\Models\Review  $review
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Review $review, Request $request)
    {
        $user = $request->user();
        
        // Periksa apakah ulasan milik pengguna atau ulasan untuk produk penjual
        $isSeller = $user->hasRole('seller');
        $isReviewOwner = $review->user_id === $user->id;
        $isSellerOfProduct = false;
        
        if ($isSeller) {
            $product = Product::find($review->produk_id);
            $isSellerOfProduct = $product && $product->penjual_id === $user->id;
        }
        
        // Jika bukan ulasan publik, periksa kepemilikan
        if (!$isReviewOwner && !$isSellerOfProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $review->load(['product', 'orderItem', 'user', 'reviewReply', 'reviewReply.user']);

        return response()->json([
            'success' => true,
            'data' => $review
        ]);
    }

    /**
     * Memperbarui ulasan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Review $review)
    {
        $user = $request->user();
        
        // Pastikan ulasan milik pengguna
        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|integer|min:1|max:5',
            'komentar' => 'sometimes|string',
            'gambar_baru.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'hapus_gambar' => 'nullable|array',
            'hapus_gambar.*' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Perbarui gambar jika ada
        $imageNames = $review->gambar ?? [];
        
        // Hapus gambar yang ditandai untuk dihapus
        if ($request->has('hapus_gambar') && is_array($request->hapus_gambar)) {
            foreach ($request->hapus_gambar as $imageToDelete) {
                $key = array_search($imageToDelete, $imageNames);
                if ($key !== false) {
                    Storage::delete('public/' . $imageToDelete);
                    unset($imageNames[$key]);
                }
            }
            $imageNames = array_values($imageNames); // Reindex array
        }
        
        // Tambahkan gambar baru
        if ($request->hasFile('gambar_baru')) {
            foreach ($request->file('gambar_baru') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/reviews', $imageName);
                $imageNames[] = 'reviews/' . $imageName;
            }
        }
        
        // Update data ulasan
        $reviewData = $request->only(['rating', 'komentar']);
        $reviewData['gambar'] = $imageNames;
        
        $review->update($reviewData);
        
        // Update rating produk
        $product = Product::find($review->produk_id);
        $product->updateRating();
        
        $review->load(['product', 'orderItem', 'user', 'reviewReply', 'reviewReply.user']);

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil diperbarui',
            'data' => $review
        ]);
    }

    /**
     * Menghapus ulasan.
     *
     * @param  \App\Models\Review  $review
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Review $review, Request $request)
    {
        $user = $request->user();
        
        // Pastikan ulasan milik pengguna
        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Hapus gambar
        if ($review->gambar) {
            foreach ($review->gambar as $image) {
                Storage::delete('public/' . $image);
            }
        }
        
        // Hapus balasan jika ada
        if ($review->reviewReply) {
            $review->reviewReply->delete();
        }
        
        $productId = $review->produk_id;
        
        $review->delete();
        
        // Update rating produk
        $product = Product::find($productId);
        if ($product) {
            $product->updateRating();
        }

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dihapus'
        ]);
    }
    
    /**
     * Membalas ulasan (untuk penjual).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function reply(Request $request, Review $review)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        
        // Periksa apakah pengguna adalah penjual produk
        $product = Product::find($review->produk_id);
        if (!$user->hasRole('seller') || $product->penjual_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Periksa apakah ulasan sudah memiliki balasan
        if ($review->reviewReply) {
            // Update balasan yang ada
            $review->reviewReply->update([
                'user_id' => $user->id,
                'comment' => $request->comment
            ]);
            
            $message = 'Balasan ulasan berhasil diperbarui';
        } else {
            // Buat balasan baru
            ReviewReply::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'comment' => $request->comment
            ]);
            
            // Buat notifikasi untuk pelanggan
            $review->user->notifications()->create([
                'judul' => 'Balasan Ulasan',
                'isi' => "{$user->name} membalas ulasan Anda untuk produk {$product->nama}.",
                'jenis' => 'pesanan',
                'pesanan_id' => $review->orderItem->pesanan_id,
                'tautan' => '/produk/' . $product->id,
            ]);
            
            $message = 'Balasan ulasan berhasil ditambahkan';
        }
        
        $review->load(['product', 'user', 'reviewReply', 'reviewReply.user']);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $review
        ]);
    }
    
    /**
     * Mendapatkan ulasan untuk produk tertentu.
     *
     * @param  \App\Models\Product  $product
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function productReviews(Product $product, Request $request)
    {
        // Filter berdasarkan rating
        $query = Review::with(['user', 'reviewReply', 'reviewReply.user'])
                      ->where('produk_id', $product->id);
        
        if ($request->has('rating') && in_array($request->rating, [1, 2, 3, 4, 5])) {
            $query->where('rating', $request->rating);
        }
        
        // Filter berdasarkan balasan
        if ($request->has('has_reply')) {
            if ($request->boolean('has_reply')) {
                $query->has('reviewReply');
            } else {
                $query->doesntHave('reviewReply');
            }
        }
        
        // Pengurutan
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDir = $request->sort_dir ?? 'desc';
        
        $allowedSortFields = ['created_at', 'rating'];
        
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDir);
        }
        
        // Paginasi
        $perPage = $request->per_page ?? 10;
        $reviews = $query->paginate($perPage);
        
        // Statistik rating
        $ratingStats = [
            'average' => $product->rating_rata,
            'count' => $product->jumlah_ulasan,
            'distribution' => [
                5 => Review::where('produk_id', $product->id)->where('rating', 5)->count(),
                4 => Review::where('produk_id', $product->id)->where('rating', 4)->count(),
                3 => Review::where('produk_id', $product->id)->where('rating', 3)->count(),
                2 => Review::where('produk_id', $product->id)->where('rating', 2)->count(),
                1 => Review::where('produk_id', $product->id)->where('rating', 1)->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'rating_stats' => $ratingStats
            ]
        ]);
    }
}