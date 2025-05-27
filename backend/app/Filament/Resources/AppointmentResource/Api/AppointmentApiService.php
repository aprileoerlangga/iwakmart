<?php
namespace App\Filament\Resources\AppointmentResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\AppointmentResource;
use Illuminate\Routing\Router;


class AppointmentApiService extends ApiService
{
    protected static string | null $resource = AppointmentResource::class;

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
