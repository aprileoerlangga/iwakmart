<?php

namespace App\Filament\Resources\SellerLocationResource\Pages;

use App\Filament\Resources\SellerLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSellerLocations extends ListRecords
{
    protected static string $resource = SellerLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}