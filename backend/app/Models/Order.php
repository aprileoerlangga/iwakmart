<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    /**
     * Tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * Atribut yang dapat diisi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nomor_pesanan',
        'status',
        'metode_pembayaran',
        'status_pembayaran',
        'id_pembayaran',
        'alamat_id',
        'metode_pengiriman',
        'biaya_kirim',
        'subtotal',
        'pajak',
        'total',
        'catatan',
    ];

    /**
     * Atribut yang dikonversi ke tipe native.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'biaya_kirim' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'pajak' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Status pesanan yang tersedia.
     *
     * @var array
     */
    public static $statuses = [
        'menunggu' => 'Menunggu Pembayaran',
        'dibayar' => 'Pembayaran Diterima',
        'diproses' => 'Pesanan Diproses',
        'dikirim' => 'Dalam Pengiriman',
        'selesai' => 'Pesanan Selesai',
        'dibatalkan' => 'Pesanan Dibatalkan'
    ];

    /**
     * Status pembayaran yang tersedia.
     *
     * @var array
     */
    public static $paymentStatuses = [
        'menunggu' => 'Menunggu Pembayaran',
        'dibayar' => 'Pembayaran Diterima',
        'gagal' => 'Pembayaran Gagal'
    ];

    /**
     * Mendapatkan user (pembeli) dari pesanan ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mendapatkan alamat pengiriman untuk pesanan ini.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'alamat_id');
    }

    /**
     * Mendapatkan item-item dalam pesanan ini.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'pesanan_id');
    }

    /**
     * Mendapatkan notifikasi terkait pesanan ini.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'pesanan_id');
    }

    /**
     * Scope untuk filter pesanan berdasarkan status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk filter pesanan berdasarkan status pembayaran.
     */
    public function scopePaymentStatus($query, $status)
    {
        return $query->where('status_pembayaran', $status);
    }

    /**
     * Scope untuk pesanan yang siap untuk dinilai (selesai dan belum direview).
     */
    public function scopeReadyToReview($query)
    {
        return $query->where('status', 'selesai')
            ->whereHas('orderItems', function ($q) {
                $q->whereDoesntHave('review');
            });
    }

    /**
     * Mendapatkan deskripsi status pesanan.
     */
    public function getStatusTextAttribute()
    {
        return self::$statuses[$this->status] ?? $this->status;
    }

    /**
     * Mendapatkan deskripsi status pembayaran.
     */
    public function getPaymentStatusTextAttribute()
    {
        return self::$paymentStatuses[$this->status_pembayaran] ?? $this->status_pembayaran;
    }

    /**
     * Mendapatkan total harga dengan format Rupiah.
     */
    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    /**
     * Mendapatkan subtotal dengan format Rupiah.
     */
    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    /**
     * Mendapatkan biaya pengiriman dengan format Rupiah.
     */
    public function getFormattedShippingAttribute()
    {
        return 'Rp ' . number_format($this->biaya_kirim, 0, ',', '.');
    }

    /**
     * Menghasilkan nomor pesanan unik.
     */
    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $random = mt_rand(1000, 9999);
        
        $orderNumber = $prefix . $date . $random;
        
        // Pastikan nomor pesanan unik
        while (self::where('nomor_pesanan', $orderNumber)->exists()) {
            $random = mt_rand(1000, 9999);
            $orderNumber = $prefix . $date . $random;
        }
        
        return $orderNumber;
    }

    /**
     * Memperbarui status pesanan dan membuat notifikasi.
     */
    public function updateStatus($newStatus)
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();

        // Buat notifikasi untuk perubahan status
        $this->user->notifications()->create([
            'judul' => 'Status Pesanan Diperbarui',
            'isi' => "Pesanan #{$this->nomor_pesanan} telah diperbarui statusnya menjadi {$this->status_text}.",
            'jenis' => 'pesanan',
            'pesanan_id' => $this->id,
            'tautan' => '/pesanan/' . $this->id,
        ]);

        // Jika status menjadi selesai, perbarui stok produk
        if ($newStatus === 'selesai' && $oldStatus !== 'selesai') {
            // Logic for handling completion (optional)
        }

        return $this;
    }

    /**
     * Memperbarui status pembayaran dan membuat notifikasi.
     */
    public function updatePaymentStatus($newStatus)
    {
        $this->status_pembayaran = $newStatus;

        // Jika pembayaran diterima, update status pesanan menjadi dibayar
        if ($newStatus === 'dibayar') {
            $this->status = 'dibayar';
        }

        $this->save();

        // Buat notifikasi untuk perubahan status pembayaran
        $this->user->notifications()->create([
            'judul' => 'Status Pembayaran Diperbarui',
            'isi' => "Pembayaran untuk pesanan #{$this->nomor_pesanan} telah {$this->payment_status_text}.",
            'jenis' => 'pembayaran',
            'pesanan_id' => $this->id,
            'tautan' => '/pesanan/' . $this->id,
        ]);

        return $this;
    }

    /**
     * Setup for table
     */
    protected static function booted()
    {
        // Generate nomor pesanan sebelum membuat pesanan baru
        static::creating(function ($order) {
            if (!$order->nomor_pesanan) {
                $order->nomor_pesanan = self::generateOrderNumber();
            }
        });
    }
}