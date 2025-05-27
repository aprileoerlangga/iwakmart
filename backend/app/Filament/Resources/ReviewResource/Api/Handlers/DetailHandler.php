<?php

namespace App\Filament\Resources\ReviewResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\ReviewResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\ReviewResource\Api\Transformers\ReviewTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = ReviewResource::class;


    /**
     * Show Review
     *
     * @param Request $request
     * @return ReviewTransformer
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

        return new ReviewTransformer($query);
    }
}
