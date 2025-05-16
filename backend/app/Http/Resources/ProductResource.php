<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'deskripsi' => $this->deskripsi,
            'harga' => $this->harga,
            'stok' => $this->stok,
            'berat' => $this->berat,
            'jenis_ikan' => $this->jenis_ikan,
            'spesies_ikan' => $this->spesies_ikan,
            'gambar' => $this->gambar,
            'aktif' => (bool) $this->aktif,
            'unggulan' => (bool) $this->unggulan,
            'rating_rata' => $this->rating_rata,
            'jumlah_ulasan' => $this->jumlah_ulasan,
            'kategori' => [
                'id' => $this->category->id,
                'nama' => $this->category->nama,
            ],
            'penjual' => [
                'id' => $this->seller->id,
                'nama' => $this->seller->name,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}