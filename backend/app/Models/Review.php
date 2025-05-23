<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'reviews';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'produk_id',
        'item_pesanan_id',
        'rating',
        'komentar',
        'gambar',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'gambar' => 'array',
    ];

    /**
     * Mendapatkan user (pembeli) yang memberikan ulasan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan produk yang diulas.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produk_id');
    }

    /**
     * Mendapatkan item pesanan yang diulas.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'item_pesanan_id');
    }

    /**
     * Mendapatkan balasan atas ulasan ini.
     */
    public function reviewReply(): HasOne
    {
        return $this->hasOne(ReviewReply::class, 'review_id');
    }

    /**
     * Scope untuk filter berdasarkan rating.
     */
    public function scopeRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope untuk mengambil ulasan dengan balasan.
     */
    public function scopeWithReplies($query)
    {
        return $query->has('reviewReply');
    }

    /**
     * Scope untuk mengambil ulasan tanpa balasan.
     */
    public function scopeWithoutReplies($query)
    {
        return $query->doesntHave('reviewReply');
    }

    /**
     * Mendapatkan teks rating sebagai bintang.
     */
    public function getStarRatingAttribute()
    {
        return str_repeat('⭐', $this->rating);
    }

    /**
     * Mendapatkan URL gambar ulasan.
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
     * Memeriksa apakah ulasan memiliki balasan.
     */
    public function hasReply()
    {
        return $this->reviewReply()->exists();
    }

    /**
     * Update rating produk setelah ulasan disimpan atau dihapus
     */
    protected static function booted()
    {
        // Update rating produk setelah ulasan disimpan
        static::saved(function ($review) {
            $review->product->updateRating();
        });

        // Update rating produk setelah ulasan dihapus
        static::deleted(function ($review) {
            $review->product->updateRating();
        });
    }
}