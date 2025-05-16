<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
    /**
     * Mendapatkan daftar produk.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'seller'])
                        ->where('aktif', true)
                        ->where('stok', '>', 0);

        // Filter berdasarkan kategori
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Filter berdasarkan jenis ikan
        if ($request->has('jenis_ikan')) {
            $query->where('jenis_ikan', $request->jenis_ikan);
        }

        // Pengurutan
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $allowedSortFields = ['nama', 'harga', 'created_at', 'rating_rata'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Paginasi
        $perPage = $request->per_page ?? 10;
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Mendapatkan daftar produk unggulan.
     *
     * @return \Illuminate\Http\Response
     */
    public function featured()
    {
        $products = Product::with(['category', 'seller'])
                          ->where('aktif', true)
                          ->where('unggulan', true)
                          ->where('stok', '>', 0)
                          ->orderBy('created_at', 'desc')
                          ->take(10)
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Mendapatkan detail produk.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        if (!$product->aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan'
            ], 404);
        }

        $product->load([
            'category', 
            'seller', 
            'seller.sellerLocation',
            'reviews' => function ($query) {
                $query->with(['user', 'reviewReply', 'reviewReply.user'])
                      ->orderBy('created_at', 'desc')
                      ->take(5);
            }
        ]);

        // Dapatkan produk terkait (dari kategori yang sama)
        $relatedProducts = Product::where('kategori_id', $product->kategori_id)
                                 ->where('id', '!=', $product->id)
                                 ->where('aktif', true)
                                 ->take(4)
                                 ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'related_products' => $relatedProducts
            ]
        ]);
    }

    /**
     * Mendapatkan produk berdasarkan kategori.
     *
     * @param  \App\Models\Category  $category
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function byCategory(Category $category, Request $request)
    {
        // Ambil semua ID kategori anak
        $categoryIds = [$category->id];
        $childCategories = $category->children()->pluck('id')->toArray();
        $categoryIds = array_merge($categoryIds, $childCategories);
        
        $query = Product::with(['category', 'seller'])
                        ->whereIn('kategori_id', $categoryIds)
                        ->where('aktif', true)
                        ->where('stok', '>', 0);

        // Filter berdasarkan jenis ikan
        if ($request->has('jenis_ikan')) {
            $query->where('jenis_ikan', $request->jenis_ikan);
        }

        // Filter rentang harga
        if ($request->has('min_price')) {
            $query->where('harga', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('harga', '<=', $request->max_price);
        }

        // Pengurutan
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $allowedSortFields = ['nama', 'harga', 'created_at', 'rating_rata'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Paginasi
        $perPage = $request->per_page ?? 10;
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'products' => $products
            ]
        ]);
    }

    /**
     * Mencari produk berdasarkan kata kunci.
     *
     * @param  string  $keyword
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search($keyword, Request $request)
    {
        $query = Product::with(['category', 'seller'])
                        ->where(function($q) use ($keyword) {
                            $q->where('nama', 'like', "%{$keyword}%")
                              ->orWhere('deskripsi', 'like', "%{$keyword}%")
                              ->orWhere('spesies_ikan', 'like', "%{$keyword}%");
                        })
                        ->where('aktif', true)
                        ->where('stok', '>', 0);

        // Filter berdasarkan kategori
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Filter berdasarkan jenis ikan
        if ($request->has('jenis_ikan')) {
            $query->where('jenis_ikan', $request->jenis_ikan);
        }

        // Filter rentang harga
        if ($request->has('min_price')) {
            $query->where('harga', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('harga', '<=', $request->max_price);
        }

        // Pengurutan
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $allowedSortFields = ['nama', 'harga', 'created_at', 'rating_rata'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Paginasi
        $perPage = $request->per_page ?? 10;
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'keyword' => $keyword,
                'products' => $products
            ]
        ]);
    }

    /**
     * Mendapatkan daftar produk penjual.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sellerProducts(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Product::with('category')
                        ->where('penjual_id', $user->id);

        // Filter berdasarkan status aktif
        if ($request->has('aktif')) {
            $query->where('aktif', $request->boolean('aktif'));
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        // Filter berdasarkan jenis ikan
        if ($request->has('jenis_ikan')) {
            $query->where('jenis_ikan', $request->jenis_ikan);
        }

        // Filter berdasarkan stok
        if ($request->has('stok_habis')) {
            if ($request->boolean('stok_habis')) {
                $query->where('stok', 0);
            } else {
                $query->where('stok', '>', 0);
            }
        }

        // Pengurutan
        $sortField = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        $allowedSortFields = ['nama', 'harga', 'stok', 'created_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Paginasi
        $perPage = $request->per_page ?? 10;
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Menyimpan produk baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'kategori_id' => 'required|exists:categories,id',
            'deskripsi' => 'required|string',
            'harga' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'berat' => 'required|numeric|min:0',
            'jenis_ikan' => 'required|in:segar,beku,olahan,hidup',
            'spesies_ikan' => 'nullable|string|max:255',
            'aktif' => 'boolean',
            'unggulan' => 'boolean',
            'gambar.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Upload gambar
        $imageNames = [];
        if ($request->hasFile('gambar')) {
            foreach ($request->file('gambar') as $image) {
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/products', $imageName);
                $imageNames[] = 'products/' . $imageName;
            }
        }

        $product = Product::create([
            'nama' => $request->nama,
            'slug' => Str::slug($request->nama) . '-' . Str::random(5),
            'kategori_id' => $request->kategori_id,
            'penjual_id' => $user->id,
            'deskripsi' => $request->deskripsi,
            'harga' => $request->harga,
            'stok' => $request->stok,
            'berat' => $request->berat,
            'jenis_ikan' => $request->jenis_ikan,
            'spesies_ikan' => $request->spesies_ikan,
            'aktif' => $request->has('aktif') ? $request->aktif : true,
            'unggulan' => $request->has('unggulan') ? $request->unggulan : false,
            'gambar' => $imageNames,
        ]);

        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan',
            'data' => $product
        ], 201);
    }

    /**
     * Memperbarui produk.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $user = $request->user();

        if (!$user->hasRole('seller') || $product->penjual_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|string|max:255',
            'kategori_id' => 'sometimes|exists:categories,id',
            'deskripsi' => 'sometimes|string',
            'harga' => 'sometimes|numeric|min:0',
            'stok' => 'sometimes|integer|min:0',
            'berat' => 'sometimes|numeric|min:0',
            'jenis_ikan' => 'sometimes|in:segar,beku,olahan,hidup',
            'spesies_ikan' => 'nullable|string|max:255',
            'aktif' => 'sometimes|boolean',
            'unggulan' => 'sometimes|boolean',
            'gambar_baru.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'hapus_gambar' => 'nullable|array',
            'hapus_gambar.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Perbarui gambar jika ada
        $imageNames = $product->gambar ?? [];

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
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/products', $imageName);
                $imageNames[] = 'products/' . $imageName;
            }
        }

        // Update data produk
        $productData = $request->only([
            'nama', 'kategori_id', 'deskripsi', 'harga', 
            'stok', 'berat', 'jenis_ikan', 'spesies_ikan', 
            'aktif', 'unggulan'
        ]);

        // Update slug jika nama diubah
        if ($request->has('nama')) {
            $productData['slug'] = Str::slug($request->nama) . '-' . Str::random(5);
        }

        // Update gambar
        $productData['gambar'] = $imageNames;

        $product->update($productData);
        $product->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui',
            'data' => $product
        ]);
    }

    /**
     * Menghapus produk.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Product $product)
    {
        $user = $request->user();

        if (!$user->hasRole('seller') || $product->penjual_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Hapus gambar produk
        if ($product->gambar) {
            foreach ($product->gambar as $image) {
                Storage::delete('public/' . $image);
            }
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus'
        ]);
    }
}