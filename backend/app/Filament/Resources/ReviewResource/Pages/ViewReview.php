<?php

namespace App\Filament\Resources\ReviewResource\Pages;

use App\Filament\Resources\ReviewResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('reply')
                ->label('Balas Ulasan')
                ->form([
                    Forms\Components\Textarea::make('comment')
                        ->label('Balasan')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $this->record->reviewReply()->updateOrCreate(
                        [], // where clause
                        [
                            'user_id' => Auth::id(),
                            'comment' => $data['comment'],
                        ]
                    );
                    
                    $this->notify('success', 'Ulasan berhasil dibalas.');
                })
                ->visible(fn () => !$this->record->reviewReply),
            
            Actions\Action::make('edit_reply')
                ->label('Edit Balasan')
                ->form([
                    Forms\Components\Textarea::make('comment')
                        ->label('Balasan')
                        ->required()
                        ->default(fn () => $this->record->reviewReply?->comment)
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    if ($this->record->reviewReply) {
                        $this->record->reviewReply->update([
                            'comment' => $data['comment'],
                        ]);
                        
                        $this->notify('success', 'Balasan berhasil diperbarui.');
                    }
                })
                ->visible(fn () => $this->record->reviewReply),
        ];
    }
}