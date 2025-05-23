<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerLocation extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'seller_locations';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nama_usaha',
        'deskripsi',
        'alamat_lengkap',
        'provinsi',
        'kota',
        'kecamatan',
        'kode_pos',
        'aktif',
        'jam_operasional',
        'telepon',
        'foto',
        'jenis_penjual',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'jam_operasional' => 'array',
        'foto' => 'array',
        'aktif' => 'boolean',
    ];

    /**
     * Jenis penjual yang tersedia.
     *
     * @var array
     */
    public static $sellerTypes = [
        'nelayan' => 'Nelayan',
        'pembudidaya' => 'Pembudidaya',
        'grosir' => 'Grosir',
        'ritel' => 'Ritel',
    ];

    /**
     * Mendapatkan user (penjual) dari lokasi ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan janji temu yang terkait dengan lokasi ini.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'lokasi_penjual_id');
    }

    /**
     * Scope untuk filter lokasi aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope untuk filter berdasarkan jenis penjual.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('jenis_penjual', $type);
    }

    /**
     * Mendapatkan alamat lengkap dengan format.
     */
    public function getFullAddressAttribute()
    {
        return "{$this->alamat_lengkap}, {$this->kecamatan}, {$this->kota}, {$this->provinsi} {$this->kode_pos}";
    }

    /**
     * Mendapatkan alamat singkat.
     */
    public function getShortAddressAttribute()
    {
        return "{$this->kecamatan}, {$this->kota}, {$this->provinsi}";
    }

    /**
     * Mendapatkan deskripsi jenis penjual.
     */
    public function getSellerTypeTextAttribute()
    {
        return self::$sellerTypes[$this->jenis_penjual] ?? $this->jenis_penjual;
    }

    /**
     * Mendapatkan URL foto utama.
     */
    public function getMainPhotoUrlAttribute()
    {
        if (!$this->foto || empty($this->foto)) {
            return null;
        }

        return asset('storage/' . $this->foto[0]);
    }

    /**
     * Mendapatkan semua URL foto.
     */
    public function getPhotoUrlsAttribute()
    {
        if (!$this->foto || empty($this->foto)) {
            return [];
        }

        return array_map(function ($photo) {
            return asset('storage/' . $photo);
        }, $this->foto);
    }

    /**
     * Mendapatkan jam operasional dengan format yang mudah dibaca.
     */
    public function getFormattedOperatingHoursAttribute()
    {
        if (!$this->jam_operasional) {
            return 'Tidak tersedia';
        }

        $formatted = [];
        foreach ($this->jam_operasional as $schedule) {
            $day = $schedule['hari'] ?? '';
            $open = $schedule['jam_buka'] ?? '';
            $close = $schedule['jam_tutup'] ?? '';

            if ($day && $open && $close) {
                $formatted[] = "{$day}: {$open} - {$close}";
            }
        }

        return empty($formatted) ? 'Tidak tersedia' : implode(', ', $formatted);
    }
}