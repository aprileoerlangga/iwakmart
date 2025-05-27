<?php
namespace App\Filament\Resources\AppointmentResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\AppointmentResource;
use App\Filament\Resources\AppointmentResource\Api\Requests\CreateAppointmentRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = AppointmentResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Appointment
     *
     * @param CreateAppointmentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateAppointmentRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}