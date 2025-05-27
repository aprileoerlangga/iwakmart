<?php
namespace App\Filament\Resources\ReviewResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\ReviewResource;
use App\Filament\Resources\ReviewResource\Api\Requests\CreateReviewRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = ReviewResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Review
     *
     * @param CreateReviewRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateReviewRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}