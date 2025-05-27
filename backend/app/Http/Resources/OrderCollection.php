<?php

// File: app/Http/Resources/OrderCollection.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($order) {
                return [
                    'id' => $order->id,
                    'nomor_pesanan' => $order->nomor_pesanan,
                    'status' => $order->status,
                    'status_label' => $this->getStatusLabel($order->status),
                    'metode_pembayaran' => $order->metode_pembayaran,
                    'status_pembayaran' => $order->status_pembayaran,
                    'total' => $order->total,
                    'total_formatted' => 'Rp ' . number_format($order->total, 0, ',', '.'),
                    'tanggal_pesan' => $order->created_at->format('Y-m-d H:i:s'),
                    'tanggal_pesan_formatted' => $order->created_at->translatedFormat('d F Y H:i'),
                    'items_count' => $order->orderItems->count(),
                    'items_preview' => $order->orderItems->take(2)->map(function($item) {
                        return [
                            'nama_produk' => $item->nama_produk,
                            'jumlah' => $item->jumlah,
                            // PERBAIKAN: Cek relasi product ada atau tidak
                            'product' => $item->relationLoaded('product') && $item->product ? [
                                'gambar' => $item->product->gambar
                            ] : null
                        ];
                    }),
                    'has_more_items' => $order->orderItems->count() > 2
                ];
            }),
            'pagination' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'links' => [
                    'next' => $this->nextPageUrl(),
                    'prev' => $this->previousPageUrl(),
                    'first' => $this->url(1),
                    'last' => $this->url($this->lastPage())
                ]
            ]
        ];
    }
    
    /**
     * Get human-readable status label.
     *
     * @param string $status
     * @return string
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'menunggu' => 'Menunggu Pembayaran',
            'dibayar' => 'Dibayar',
            'diproses' => 'Sedang Diproses',
            'dikirim' => 'Dalam Pengiriman',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan'
        ];
        
        return $labels[$status] ?? $status;
    }
}