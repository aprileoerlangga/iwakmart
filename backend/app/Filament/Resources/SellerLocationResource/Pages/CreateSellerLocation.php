<?php

namespace App\Filament\Resources\SellerLocationResource\Pages;

use App\Filament\Resources\SellerLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSellerLocation extends CreateRecord
{
    protected static string $resource = SellerLocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}