<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'rating' => $this->rating,
            'komentar' => $this->komentar,
            'balasan' => $this->whenLoaded('reviewReply', function () {
                return [
                    'id' => $this->reviewReply->id,
                    'user' => [
                        'id' => $this->reviewReply->user->id,
                        'name' => $this->reviewReply->user->name,
                    ],
                    'comment' => $this->reviewReply->comment,
                    'created_at' => $this->reviewReply->created_at,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}