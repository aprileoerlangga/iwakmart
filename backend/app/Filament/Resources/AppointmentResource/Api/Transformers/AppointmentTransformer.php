<?php
namespace App\Filament\Resources\AppointmentResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Appointment;

/**
 * @property Appointment $resource
 */
class AppointmentTransformer extends JsonResource
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
