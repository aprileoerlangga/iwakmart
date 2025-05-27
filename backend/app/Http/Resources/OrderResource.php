<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nomor_pesanan' => $this->nomor_pesanan,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'metode_pembayaran' => $this->metode_pembayaran,
            'metode_pembayaran_label' => $this->getPaymentMethodLabel(),
            'status_pembayaran' => $this->status_pembayaran,
            'status_pembayaran_label' => $this->getPaymentStatusLabel(),
            'metode_pengiriman' => $this->metode_pengiriman,
            'subtotal' => $this->subtotal,
            'subtotal_formatted' => 'Rp ' . number_format($this->subtotal, 0, ',', '.'),
            'biaya_kirim' => $this->biaya_kirim,
            'biaya_kirim_formatted' => 'Rp ' . number_format($this->biaya_kirim, 0, ',', '.'),
            'pajak' => $this->pajak,
            'pajak_formatted' => 'Rp ' . number_format($this->pajak, 0, ',', '.'),
            'total' => $this->total,
            'total_formatted' => 'Rp ' . number_format($this->total, 0, ',', '.'),
            'catatan' => $this->catatan,
            'tanggal_pesan' => $this->created_at->format('Y-m-d H:i:s'),
            'tanggal_pesan_formatted' => $this->created_at->translatedFormat('d F Y H:i'),
            
            // User/Pembeli - PERBAIKAN: Cek relasi loaded
            'user' => $this->when($this->relationLoaded('user'), function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone
                ];
            }),
            
            // Alamat pengiriman - PERBAIKAN: Cek relasi loaded
            'address' => $this->when($this->relationLoaded('address'), function() {
                return [
                    'id' => $this->address->id,
                    'nama_penerima' => $this->address->nama_penerima,
                    'telepon' => $this->address->telepon,
                    'alamat_lengkap' => $this->address->alamat_lengkap,
                    'provinsi' => $this->address->provinsi,
                    'kota' => $this->address->kota,
                    'kecamatan' => $this->address->kecamatan,
                    'kelurahan' => $this->address->kelurahan ?? null,
                    'kode_pos' => $this->address->kode_pos,
                    'alamat_utama' => (bool) ($this->address->alamat_utama ?? false),
                    'catatan_alamat' => $this->address->catatan_alamat ?? null
                ];
            }),
            
            // Order items - PERBAIKAN: Hindari nested whenLoaded
            'items' => $this->when($this->relationLoaded('orderItems'), function() {
                return $this->orderItems->map(function($item) {
                    $itemData = [
                        'id' => $item->id,
                        'product_id' => $item->produk_id,
                        'seller_id' => $item->penjual_id,
                        'nama_produk' => $item->nama_produk,
                        'jumlah' => $item->jumlah,
                        'harga' => $item->harga,
                        'harga_formatted' => 'Rp ' . number_format($item->harga, 0, ',', '.'),
                        'subtotal' => $item->subtotal,
                        'subtotal_formatted' => 'Rp ' . number_format($item->subtotal, 0, ',', '.'),
                    ];

                    // PERBAIKAN: Cek relasi product secara manual
                    if ($item->relationLoaded('product') && $item->product) {
                        $itemData['product'] = [
                            'id' => $item->product->id,
                            'nama' => $item->product->nama,
                            'gambar' => $item->product->gambar,
                            'jenis_ikan' => $item->product->jenis_ikan
                        ];
                    } else {
                        $itemData['product'] = null;
                    }

                    // PERBAIKAN: Cek relasi seller secara manual
                    if ($item->relationLoaded('seller') && $item->seller) {
                        $itemData['seller'] = [
                            'id' => $item->seller->id,
                            'name' => $item->seller->name
                        ];
                    } else {
                        $itemData['seller'] = null;
                    }

                    return $itemData;
                });
            }),
            
            'can_cancel' => in_array($this->status, ['menunggu', 'diproses']),
            'can_complete' => $this->status === 'dikirim',
            'can_review' => $this->status === 'selesai',
            
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get human-readable status label.
     *
     * @return string
     */
    private function getStatusLabel()
    {
        $labels = [
            'menunggu' => 'Menunggu Pembayaran',
            'dibayar' => 'Dibayar',
            'diproses' => 'Sedang Diproses',
            'dikirim' => 'Dalam Pengiriman',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan'
        ];
        
        return $labels[$this->status] ?? $this->status;
    }
    
    /**
     * Get human-readable payment status label.
     *
     * @return string
     */
    private function getPaymentStatusLabel()
    {
        $labels = [
            'menunggu' => 'Menunggu Pembayaran',
            'dibayar' => 'Pembayaran Diterima',
            'gagal' => 'Pembayaran Gagal'
        ];
        
        return $labels[$this->status_pembayaran] ?? $this->status_pembayaran;
    }
    
    /**
     * Get human-readable payment method label.
     *
     * @return string
     */
    private function getPaymentMethodLabel()
    {
        $labels = [
            'transfer_bank' => 'Transfer Bank',
            'e_wallet' => 'E-Wallet',
            'cod' => 'Bayar di Tempat (COD)',
            'virtual_account' => 'Virtual Account'
        ];
        
        return $labels[$this->metode_pembayaran] ?? $this->metode_pembayaran;
    }
}