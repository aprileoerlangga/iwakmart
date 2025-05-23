<?php

namespace App\Filament\Resources\SellerLocationResource\Pages;

use App\Filament\Resources\SellerLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSellerLocation extends EditRecord
{
    protected static string $resource = SellerLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}