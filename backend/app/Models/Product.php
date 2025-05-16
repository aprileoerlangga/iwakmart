<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'slug',
        'deskripsi',
        'harga',
        'stok',
        'kategori_id',
        'penjual_id',
        'gambar',
        'berat',
        'jenis_ikan',
        'spesies_ikan',
        //'tanggal_tangkap',
        //'asal_ikan',
        'rating_rata',
        'jumlah_ulasan',
        'aktif',
        'unggulan',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'harga' => 'decimal:2',
        'stok' => 'integer',
        'gambar' => 'array',
        'berat' => 'decimal:2',
        'rating_rata' => 'decimal:1',
        'jumlah_ulasan' => 'integer',
        'aktif' => 'boolean',
        'unggulan' => 'boolean',
        'tanggal_tangkap' => 'date',
    ];

    /**
     * Mendapatkan kategori dari produk.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    /**
     * Mendapatkan penjual (user) dari produk.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penjual_id');
    }

    /**
     * Mendapatkan detail lokasi penjual dari produk.
     */
    public function sellerLocation()
    {
        return $this->seller->sellerLocation;
    }

    /**
     * Mendapatkan ulasan-ulasan untuk produk ini.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'produk_id');
    }

    /**
     * Mendapatkan item-item keranjang yang terkait dengan produk ini.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'produk_id');
    }

    /**
     * Mendapatkan item-item pesanan yang terkait dengan produk ini.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'produk_id');
    }

    /**
     * Mendapatkan pesan-pesan yang terkait dengan produk ini.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'produk_id');
    }

    /**
     * Scope untuk filter produk aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope untuk filter produk unggulan.
     */
    public function scopeFeatured($query)
    {
        return $query->where('unggulan', true);
    }

    /**
     * Scope untuk mencari produk berdasarkan nama.
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where('nama', 'like', "%{$keyword}%")
            ->orWhere('deskripsi', 'like', "%{$keyword}%")
            ->orWhere('spesies_ikan', 'like', "%{$keyword}%");
    }

    /**
     * Scope untuk produk dengan stok tersedia.
     */
    public function scopeInStock($query)
    {
        return $query->where('stok', '>', 0);
    }

    /**
     * Mendapatkan URL gambar utama produk.
     */
    public function getMainImageUrlAttribute()
    {
        if (!$this->gambar || empty($this->gambar)) {
            return null;
        }

        return asset('storage/' . $this->gambar[0]);
    }

    /**
     * Mendapatkan semua URL gambar produk.
     */
    public function getImageUrlsAttribute()
    {
        if (!$this->gambar || empty($this->gambar)) {
            return [];
        }

        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->gambar);
    }

    /**
     * Mendapatkan format harga produk dalam Rupiah.
     */
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }

    /**
     * Mendapatkan status stok produk.
     */
    public function getStockStatusAttribute()
    {
        if ($this->stok <= 0) {
            return 'Habis';
        }
        if ($this->stok <= 5) {
            return 'Hampir Habis';
        }
        return 'Tersedia';
    }

    /**
     * Menghitung dan memperbarui rating rata-rata produk.
     */
    public function updateRating()
    {
        $reviews = $this->reviews();
        $count = $reviews->count();
        
        if ($count > 0) {
            $avgRating = $reviews->avg('rating');
            $this->rating_rata = round($avgRating, 1);
            $this->jumlah_ulasan = $count;
            $this->save();
        }
    }
}