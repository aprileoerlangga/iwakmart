<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'cart_items';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'keranjang_id',
        'produk_id',
        'jumlah',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jumlah' => 'integer',
    ];

    /**
     * Mendapatkan keranjang yang berisi item ini.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'keranjang_id');
    }

    /**
     * Mendapatkan produk dari item keranjang ini.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produk_id');
    }

    /**
     * Mendapatkan subtotal untuk item ini.
     */
    public function getSubtotalAttribute()
    {
        return $this->product->harga * $this->jumlah;
    }

    /**
     * Mendapatkan subtotal dengan format Rupiah.
     */
    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    /**
     * Validate stock before saving
     */
    protected static function booted()
    {
        static::saving(function ($cartItem) {
            $product = $cartItem->product;
            if ($product && $cartItem->jumlah > $product->stok) {
                $cartItem->jumlah = $product->stok;
            }
        });
    }
}