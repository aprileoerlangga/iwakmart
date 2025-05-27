<?php
namespace App\Filament\Resources\SellerLocationResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\SellerLocationResource;
use Illuminate\Routing\Router;


class SellerLocationApiService extends ApiService
{
    protected static string | null $resource = SellerLocationResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];

    }
}
