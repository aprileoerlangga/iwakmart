<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'messages';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pengirim_id',
        'penerima_id',
        'janji_temu_id',
        'produk_id',
        'isi',
        'dibaca_pada',
        'lampiran',
        'jenis',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lampiran' => 'array',
        'dibaca_pada' => 'datetime',
    ];

    /**
     * Mendapatkan user (pengirim) dari pesan ini.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    /**
     * Mendapatkan user (penerima) dari pesan ini.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    /**
     * Mendapatkan janji temu yang terkait dengan pesan ini.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'janji_temu_id');
    }

    /**
     * Mendapatkan produk yang terkait dengan pesan ini.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'produk_id');
    }

    /**
     * Scope untuk mendapatkan percakapan antara dua pengguna.
     */
    public function scopeConversation($query, $user1Id, $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('pengirim_id', $user1Id)
                ->where('penerima_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('pengirim_id', $user2Id)
                ->where('penerima_id', $user1Id);
        })->orderBy('created_at');
    }

    /**
     * Scope untuk mendapatkan pesan yang belum dibaca.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('dibaca_pada');
    }

    /**
     * Mendapatkan URL lampiran.
     */
    public function getAttachmentUrlsAttribute()
    {
        if (!$this->lampiran || empty($this->lampiran)) {
            return [];
        }

        return array_map(function ($attachment) {
            return asset('storage/' . $attachment);
        }, $this->lampiran);
    }

    /**
     * Memeriksa apakah pesan sudah dibaca.
     */
    public function isRead()
    {
        return $this->dibaca_pada !== null;
    }

    /**
     * Menandai pesan sebagai telah dibaca.
     */
    public function markAsRead()
    {
        if (!$this->isRead()) {
            $this->dibaca_pada = now();
            $this->save();
        }

        return $this;
    }
}