<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Mendapatkan jumlah item dalam keranjang.
     */
    public function getItemCountAttribute()
    {
        return $this->items()->sum('jumlah');
    }

    /**
     * Mendapatkan total harga dalam keranjang.
     */
    public function getTotalPriceAttribute()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->product->harga * $item->jumlah;
        }
        return $total;
    }

    /**
     * Menambahkan produk ke keranjang.
     */
    public function addProduct($productId, $quantity = 1)
    {
        $existingItem = $this->items()->where('produk_id', $productId)->first();

        if ($existingItem) {
            // Update jumlah jika produk sudah ada di keranjang
            $existingItem->increment('jumlah', $quantity);
            return $existingItem;
        } else {
            // Tambahkan produk baru ke keranjang
            return $this->items()->create([
                'produk_id' => $productId,
                'jumlah' => $quantity
            ]);
        }
    }

    /**
     * Mengubah jumlah item dalam keranjang.
     */
    public function updateItemQuantity($itemId, $quantity)
    {
        $item = $this->items()->findOrFail($itemId);
        $item->jumlah = $quantity;
        $item->save();
        return $item;
    }

    /**
     * Menghapus item dari keranjang.
     */
    public function removeItem($itemId)
    {
        return $this->items()->where('id', $itemId)->delete();
    }

    /**
     * Mengosongkan keranjang.
     */
    public function clear()
    {
        return $this->items()->delete();
    }
}