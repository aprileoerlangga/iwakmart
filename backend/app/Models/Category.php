<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'slug',
        'deskripsi',
        'induk_id',
        'gambar',
        'aktif',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'aktif' => 'boolean',
    ];

    /**
     * Mendapatkan kategori induk dari kategori ini.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'induk_id');
    }

    /**
     * Mendapatkan kategori-kategori anak dari kategori ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'induk_id');
    }

    /**
     * Mendapatkan produk-produk yang terkait dengan kategori ini.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'kategori_id');
    }

    /**
     * Scope untuk filter kategori aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope untuk filter kategori induk (yang tidak memiliki parent).
     */
    public function scopeParents($query)
    {
        return $query->whereNull('induk_id');
    }

    /**
     * Mendapatkan URL gambar kategori.
     */
    public function getImageUrlAttribute()
    {
        if (!$this->gambar) {
            return null;
        }

        return asset('storage/' . $this->gambar);
    }

    /**
     * Mendapatkan jumlah produk dalam kategori.
     */
    public function getProductCountAttribute()
    {
        return $this->products()->count();
    }

    /**
     * Get all of the categories with no parent (parents)
     */
    public static function getParentCategories()
    {
        return static::whereNull('induk_id')->where('aktif', true)->get();
    }
}