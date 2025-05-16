<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'nama' => $this->product->nama,
                'gambar' => $this->product->gambar ? $this->product->gambar[0] : null,
            ],
            'jumlah' => $this->jumlah,
            'harga' => $this->harga,
            'subtotal' => $this->subtotal,
        ];
    }
}