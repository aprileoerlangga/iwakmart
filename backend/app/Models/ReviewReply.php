<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReply extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'review_replies';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'review_id',
        'user_id',
        'comment',
    ];

    /**
     * Mendapatkan ulasan yang dibalas.
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Mendapatkan user (penjual) yang memberikan balasan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Memeriksa apakah user adalah pemilik produk yang diulas.
     */
    public function isProductOwner()
    {
        $productOwnerId = $this->review->product->penjual_id;
        return $this->user_id === $productOwnerId;
    }

    /**
     * Mendapatkan waktu relatif untuk balasan.
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}