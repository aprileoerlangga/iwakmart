<?php

namespace App\Filament\Resources\AppointmentResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\AppointmentResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\AppointmentResource\Api\Transformers\AppointmentTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = AppointmentResource::class;


    /**
     * Show Appointment
     *
     * @param Request $request
     * @return AppointmentTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');
        
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new AppointmentTransformer($query);
    }
}
