<?php
namespace App\Filament\Resources\OrderResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Api\Requests\CreateOrderRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = OrderResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Order
     *
     * @param CreateOrderRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateOrderRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}