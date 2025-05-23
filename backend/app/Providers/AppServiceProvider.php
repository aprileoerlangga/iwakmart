<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Format response konsisten untuk API
        Response::macro('apiSuccess', function ($data, $message = 'Operasi berhasil', $statusCode = 200) {
            return Response::json([
                'success' => true,
                'message' => $message,
                'data' => $data
            ], $statusCode);
        });

        Response::macro('apiError', function ($message, $errors = null, $statusCode = 400) {
            $response = [
                'success' => false,
                'message' => $message,
            ];

            if (!is_null($errors)) {
                $response['errors'] = $errors;
            }

            return Response::json($response, $statusCode);
        });
    }
}