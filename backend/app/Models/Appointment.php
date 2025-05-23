<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Appointment extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'appointments';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'penjual_id',
        'pembeli_id',
        'lokasi_penjual_id',
        'tanggal_janji',
        'status',
        'tujuan',
        'catatan',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_janji' => 'datetime',
    ];

    /**
     * Status janji temu yang tersedia.
     *
     * @var array
     */
    public static $statuses = [
        'menunggu' => 'Menunggu Konfirmasi',
        'dikonfirmasi' => 'Dikonfirmasi',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    /**
     * Mendapatkan user (penjual) dari janji temu ini.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penjual_id');
    }

    /**
     * Mendapatkan user (pembeli) dari janji temu ini.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembeli_id');
    }

    /**
     * Mendapatkan lokasi penjual untuk janji temu ini.
     */
    public function sellerLocation(): BelongsTo
    {
        return $this->belongsTo(SellerLocation::class, 'lokasi_penjual_id');
    }

    /**
     * Mendapatkan pesan-pesan yang terkait dengan janji temu ini.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'janji_temu_id');
    }

    /**
     * Mendapatkan notifikasi yang terkait dengan janji temu ini.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'janji_temu_id');
    }

    /**
     * Scope untuk filter berdasarkan status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter janji temu yang akan datang.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('tanggal_janji', '>=', now())
            ->whereIn('status', ['menunggu', 'dikonfirmasi']);
    }

    /**
     * Scope untuk filter janji temu yang sudah lewat.
     */
    public function scopePast($query)
    {
        return $query->where('tanggal_janji', '<', now())
            ->orWhereIn('status', ['selesai', 'dibatalkan']);
    }

    /**
     * Mendapatkan deskripsi status janji temu.
     */
    public function getStatusTextAttribute()
    {
        return self::$statuses[$this->status] ?? $this->status;
    }

    /**
     * Mendapatkan format tanggal untuk janji temu.
     */
    public function getFormattedDateAttribute()
    {
        return $this->tanggal_janji->translatedFormat('l, d F Y');
    }

    /**
     * Mendapatkan format waktu untuk janji temu.
     */
    public function getFormattedTimeAttribute()
    {
        return $this->tanggal_janji->format('H:i');
    }

    /**
     * Memeriksa apakah janji temu sudah lewat.
     */
    public function isPast()
    {
        return $this->tanggal_janji < now();
    }

    /**
     * Memperbarui status janji temu dan membuat notifikasi.
     */
    public function updateStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->save();

        // Buat notifikasi untuk penjual
        $this->seller->notifications()->create([
            'judul' => 'Status Janji Temu Diperbarui',
            'isi' => "Janji temu dengan {$this->buyer->name} telah diperbarui statusnya menjadi {$this->status_text}.",
            'jenis' => 'janji_temu',
            'janji_temu_id' => $this->id,
            'tautan' => '/janji-temu/' . $this->id,
        ]);

        // Buat notifikasi untuk pembeli
        $this->buyer->notifications()->create([
            'judul' => 'Status Janji Temu Diperbarui',
            'isi' => "Janji temu dengan {$this->seller->name} telah diperbarui statusnya menjadi {$this->status_text}.",
            'jenis' => 'janji_temu',
            'janji_temu_id' => $this->id,
            'tautan' => '/janji-temu/' . $this->id,
        ]);

        return $this;
    }
}