<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Cart extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'carts';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
    ];

    /**
     * Boot method untuk model.
     */
    protected static function boot()
    {
        parent::boot();

        // Hapus cache saat ada perubahan pada keranjang
        static::updated(function ($cart) {
            Cache::forget('cart_' . $cart->user_id);
        });

        static::deleted(function ($cart) {
            Cache::forget('cart_' . $cart->user_id);
        });
    }

    /**
     * Mendapatkan user yang memiliki keranjang ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan item-item dalam keranjang.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'keranjang_id');
    }

    /**
     * Mendapatkan jumlah item dalam keranjang (lebih efisien dengan DB query).
     */
    public function getItemCountAttribute()
    {
        // Menggunakan caching untuk performa
        $cacheKey = 'cart_count_' . $this->id;
        return Cache::remember($cacheKey, 60, function () {
            return $this->items()->sum('jumlah');
        });
    }

    /**
     * Mendapatkan total harga dalam keranjang dengan eager loading.
     */
    public function getTotalPriceAttribute()
    {
        // Menggunakan caching untuk performa
        $cacheKey = 'cart_total_' . $this->id;
        return Cache::remember($cacheKey, 60, function () {
            // Pastikan item product sudah di-load untuk menghindari N+1 query
            if (!$this->relationLoaded('items.product')) {
                $this->load('items.product');
            }
            
            $total = 0;
            foreach ($this->items as $item) {
                if ($item->product && $item->product->aktif && $item->product->stok > 0) {
                    $total += $item->product->harga * $item->jumlah;
                }
            }
            return $total;
        });
    }

    /**
     * Mendapatkan formatted total harga.
     */
    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    /**
     * Menambahkan produk ke keranjang dengan validasi stok.
     */
    public function addProduct($productId, $quantity = 1)
    {
        // Gunakan DB transaction untuk menghindari race condition
        return DB::transaction(function () use ($productId, $quantity) {
            $product = Product::findOrFail($productId);
            
            // Validasi ketersediaan produk
            if (!$product->aktif || $product->stok <= 0) {
                return [
                    'success' => false,
                    'message' => 'Produk tidak tersedia'
                ];
            }
            
            // Validasi jumlah
            if ($quantity > $product->stok) {
                return [
                    'success' => false, 
                    'message' => 'Jumlah melebihi stok tersedia',
                    'available_stock' => $product->stok
                ];
            }
            
            $existingItem = $this->items()->where('produk_id', $productId)->first();
    
            if ($existingItem) {
                // Validasi total setelah increment
                $newQuantity = $existingItem->jumlah + $quantity;
                if ($newQuantity > $product->stok) {
                    $newQuantity = $product->stok;
                }
                
                // Update jumlah jika produk sudah ada di keranjang
                $existingItem->jumlah = $newQuantity;
                $existingItem->save();
                
                // Hapus cache
                $this->forgetCaches();
                
                return [
                    'success' => true,
                    'message' => 'Produk berhasil diperbarui dalam keranjang',
                    'item' => $existingItem->fresh(['product'])
                ];
            } else {
                // Tambahkan produk baru ke keranjang
                $cartItem = $this->items()->create([
                    'produk_id' => $productId,
                    'jumlah' => $quantity
                ]);
                
                // Hapus cache
                $this->forgetCaches();
                
                return [
                    'success' => true,
                    'message' => 'Produk berhasil ditambahkan ke keranjang',
                    'item' => $cartItem->fresh(['product'])
                ];
            }
        });
    }

    /**
     * Mengubah jumlah item dalam keranjang dengan validasi stok.
     */
    public function updateItemQuantity($itemId, $quantity)
    {
        // Gunakan DB transaction untuk menghindari race condition
        return DB::transaction(function () use ($itemId, $quantity) {
            $item = $this->items()->findOrFail($itemId);
            $product = Product::findOrFail($item->produk_id);
            
            // Validasi ketersediaan produk
            if (!$product->aktif || $product->stok <= 0) {
                return [
                    'success' => false,
                    'message' => 'Produk tidak tersedia'
                ];
            }
            
            // Validasi jumlah
            if ($quantity > $product->stok) {
                return [
                    'success' => false, 
                    'message' => 'Jumlah melebihi stok tersedia',
                    'available_stock' => $product->stok
                ];
            }
            
            $item->jumlah = $quantity;
            $item->save();
            
            // Hapus cache
            $this->forgetCaches();
            
            return [
                'success' => true,
                'message' => 'Jumlah item berhasil diperbarui',
                'item' => $item->fresh(['product'])
            ];
        });
    }

    /**
     * Menghapus item dari keranjang.
     */
    public function removeItem($itemId)
    {
        $result = $this->items()->where('id', $itemId)->delete();
        
        // Hapus cache
        $this->forgetCaches();
        
        return [
            'success' => $result > 0,
            'message' => $result > 0 ? 'Item berhasil dihapus' : 'Item tidak ditemukan'
        ];
    }

    /**
     * Mengosongkan keranjang.
     */
    public function clear()
    {
        $result = $this->items()->delete();
        
        // Hapus cache
        $this->forgetCaches();
        
        return [
            'success' => true,
            'message' => 'Keranjang berhasil dikosongkan',
            'items_deleted' => $result
        ];
    }
    
    /**
     * Membersihkan item tidak valid dari keranjang (produk tidak aktif/stok habis).
     */
    public function cleanInvalidItems()
    {
        $invalidItems = [];
        
        foreach ($this->items as $item) {
            if (!$item->product || !$item->product->aktif || $item->product->stok <= 0) {
                $invalidItems[] = [
                    'id' => $item->id,
                    'product_id' => $item->produk_id,
                    'reason' => !$item->product ? 'Produk tidak ditemukan' : 
                        ($item->product->stok <= 0 ? 'Stok habis' : 'Produk tidak aktif')
                ];
                $item->delete();
            } 
            // Update jumlah jika melebihi stok
            else if ($item->jumlah > $item->product->stok) {
                $item->jumlah = $item->product->stok;
                $item->save();
            }
        }
        
        // Hapus cache
        $this->forgetCaches();
        
        return $invalidItems;
    }
    
    /**
     * Helper untuk menghapus semua cache terkait keranjang ini.
     */
    protected function forgetCaches()
    {
        Cache::forget('cart_' . $this->user_id);
        Cache::forget('cart_count_' . $this->id);
        Cache::forget('cart_total_' . $this->id);
    }
}