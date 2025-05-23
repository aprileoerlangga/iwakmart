<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'order_items';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pesanan_id',
        'produk_id',
        'penjual_id',
        'nama_produk',
        'jumlah',
        'harga',
        'subtotal',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jumlah' => 'integer',
        'harga' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Mendapatkan pesanan yang berisi item ini.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'pesanan_id');
    }

    /**
     * Mendapatkan produk dari item pesanan ini.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produk_id');
    }

    /**
     * Mendapatkan penjual dari item pesanan ini.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penjual_id');
    }

    /**
     * Mendapatkan ulasan untuk item pesanan ini.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'item_pesanan_id');
    }

    /**
     * Mendapatkan subtotal dengan format Rupiah.
     */
    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    /**
     * Mendapatkan harga per unit dengan format Rupiah.
     */
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }

    /**
     * Memeriksa apakah item ini dapat direview.
     */
    public function canBeReviewed()
    {
        return $this->order->status === 'selesai' && !$this->review;
    }

    /**
     * Setup for subtotal calculation
     */
    protected static function booted()
    {
        static::creating(function ($orderItem) {
            // Hitung subtotal
            if (!$orderItem->subtotal) {
                $orderItem->subtotal = $orderItem->harga * $orderItem->jumlah;
            }
        });
    }
}