<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'deskripsi' => $this->deskripsi,
            'gambar' => $this->gambar,
            'product_count' => $this->products_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}