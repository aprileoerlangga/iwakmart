<?php

namespace App\Filament\Resources\SellerLocationResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\SellerLocationResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\SellerLocationResource\Api\Transformers\SellerLocationTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = SellerLocationResource::class;


    /**
     * Show SellerLocation
     *
     * @param Request $request
     * @return SellerLocationTransformer
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

        return new SellerLocationTransformer($query);
    }
}
