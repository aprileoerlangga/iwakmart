<?php
namespace App\Filament\Resources\SellerLocationResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\SellerLocationResource;
use App\Filament\Resources\SellerLocationResource\Api\Requests\CreateSellerLocationRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = SellerLocationResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create SellerLocation
     *
     * @param CreateSellerLocationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateSellerLocationRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}