<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            
            Actions\Action::make('update_status')
                ->label('Ubah Status')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Status Baru')
                        ->options(Appointment::$statuses)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->updateStatus($data['status']);
                    $this->notify('success', 'Status janji temu berhasil diperbarui.');
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}