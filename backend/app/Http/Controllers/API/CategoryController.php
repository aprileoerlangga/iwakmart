<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Mendapatkan daftar kategori.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Category::with('children')
                        ->where('aktif', true);
        
        // Parameter untuk mendapatkan hanya kategori induk
        if ($request->has('parents_only') && $request->boolean('parents_only')) {
            $query->whereNull('induk_id');
        }
        
        // Parameter untuk menyertakan jumlah produk
        $includeProductCount = $request->has('include_product_count') && $request->boolean('include_product_count');
        
        if ($includeProductCount) {
            $query->withCount(['products' => function($q) {
                $q->where('aktif', true)->where('stok', '>', 0);
            }]);
        }
        
        $categories = $query->orderBy('nama')->get();
        
        // Format untuk tampilan
        $categories->transform(function ($category) {
            // Tambahkan URL gambar jika ada
            if ($category->gambar) {
                $category->image_url = asset('storage/' . $category->gambar);
            }
            
            return $category;
        });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Menampilkan detail kategori.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        // Pastikan kategori aktif
        if (!$category->aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan'
            ], 404);
        }
        
        // Muat data terkait
        $category->load([
            'parent',
            'children' => function($query) {
                $query->where('aktif', true);
            }
        ]);
        
        // Hitung jumlah produk
        $category->product_count = $category->products()
                                          ->where('aktif', true)
                                          ->where('stok', '>', 0)
                                          ->count();
        
        // Tambahkan URL gambar jika ada
        if ($category->gambar) {
            $category->image_url = asset('storage/' . $category->gambar);
        }
        
        // Dapatkan beberapa produk terbaru dalam kategori ini
        $latestProducts = $category->products()
                                  ->with('category')
                                  ->where('aktif', true)
                                  ->where('stok', '>', 0)
                                  ->orderBy('created_at', 'desc')
                                  ->take(6)
                                  ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'latest_products' => $latestProducts
            ]
        ]);
    }
}