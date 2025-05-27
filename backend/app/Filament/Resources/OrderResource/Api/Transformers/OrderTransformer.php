<?php
namespace App\Filament\Resources\OrderResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Order;

/**
 * @property Order $resource
 */
class OrderTransformer extends JsonResource
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
