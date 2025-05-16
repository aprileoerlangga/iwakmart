<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nomor_pesanan' => $this->nomor_pesanan,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'total' => $this->total,
            'status' => $this->status,
            'status_pembayaran' => $this->status_pembayaran,
            'metode_pembayaran' => $this->metode_pembayaran,
            'alamat_pengiriman' => $this->alamat_pengiriman,
            'kota' => $this->kota,
            'kode_pos' => $this->kode_pos,
            'ongkos_kirim' => $this->ongkos_kirim,
            'catatan' => $this->catatan,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}