<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use App\Models\Order;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            
            Actions\Action::make('update_status')
                ->label('Ubah Status')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status Baru')
                        ->options(Order::$statuses)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->updateStatus($data['status']);
                    $this->notify('success', 'Status pesanan berhasil diperbarui.');
                }),
                
            Actions\Action::make('download_invoice')
                ->label('Download Invoice')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn () => route('orders.invoice.download', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}