<?php
namespace App\Filament\Resources\ReviewResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\ReviewResource;
use Illuminate\Routing\Router;


class ReviewApiService extends ApiService
{
    protected static string | null $resource = ReviewResource::class;

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
