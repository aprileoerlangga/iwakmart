<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'judul',
        'isi',
        'jenis',
        'dibaca_pada',
        'data',
        'tautan',
        'pesanan_id',
        'janji_temu_id',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'dibaca_pada' => 'datetime',
    ];

    /**
     * Jenis notifikasi yang tersedia.
     *
     * @var array
     */
    public static $types = [
        'pesanan' => 'Pesanan',
        'pembayaran' => 'Pembayaran',
        'janji_temu' => 'Janji Temu',
        'chat' => 'Pesan',
        'sistem' => 'Sistem'
    ];

    /**
     * Mendapatkan user penerima notifikasi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan pesanan yang terkait dengan notifikasi ini.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'pesanan_id');
    }

    /**
     * Mendapatkan janji temu yang terkait dengan notifikasi ini.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'janji_temu_id');
    }

    /**
     * Scope untuk mendapatkan notifikasi yang belum dibaca.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('dibaca_pada');
    }

    /**
     * Scope untuk filter berdasarkan jenis.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('jenis', $type);
    }

    /**
     * Mendapatkan deskripsi jenis notifikasi.
     */
    public function getTypeTextAttribute()
    {
        return self::$types[$this->jenis] ?? $this->jenis;
    }

    /**
     * Mendapatkan waktu relatif untuk notifikasi.
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Memeriksa apakah notifikasi sudah dibaca.
     */
    public function isRead()
    {
        return $this->dibaca_pada !== null;
    }

    /**
     * Menandai notifikasi sebagai telah dibaca.
     */
    public function markAsRead()
    {
        if (!$this->isRead()) {
            $this->dibaca_pada = now();
            $this->save();
        }

        return $this;
    }

    /**
     * Menandai notifikasi sebagai belum dibaca.
     */
    public function markAsUnread()
    {
        $this->dibaca_pada = null;
        $this->save();

        return $this;
    }
}