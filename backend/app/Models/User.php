<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Mendapatkan alamat-alamat user.
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Mendapatkan keranjang user.
     */
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Mendapatkan produk yang dijual oleh user (penjual).
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'penjual_id');
    }

    /**
     * Mendapatkan pesanan user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Mendapatkan lokasi penjual.
     */
    public function sellerLocations()
    {
        return $this->hasMany(SellerLocation::class);
    }

    /**
     * Mendapatkan ulasan yang diberikan oleh user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Mendapatkan notifikasi yang diterima oleh user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}