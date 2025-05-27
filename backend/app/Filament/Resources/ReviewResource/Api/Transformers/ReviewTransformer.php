<?php
namespace App\Filament\Resources\ReviewResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Review;

/**
 * @property Review $resource
 */
class ReviewTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
