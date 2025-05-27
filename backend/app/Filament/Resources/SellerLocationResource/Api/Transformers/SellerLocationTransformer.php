<?php
namespace App\Filament\Resources\SellerLocationResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\SellerLocation;

/**
 * @property SellerLocation $resource
 */
class SellerLocationTransformer extends JsonResource
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
