<?php

namespace App\Filament\Resources\AppointmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';
    
    protected static ?string $title = 'Pesan';
    
    protected static ?string $recordTitleAttribute = 'isi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pengirim_id')
                    ->label('Pengirim')
                    ->relationship('sender', 'name')
                    ->searchable()
                    ->required(),
                
                Forms\Components\Select::make('penerima_id')
                    ->label('Penerima')
                    ->relationship('recipient', 'name')
                    ->searchable()
                    ->required(),
                
                Forms\Components\Select::make('jenis')
                    ->label('Jenis Pesan')
                    ->options([
                        'teks' => 'Teks',
                        'gambar' => 'Gambar',
                        'lokasi' => 'Lokasi',
                    ])
                    ->required()
                    ->default('teks'),
                
                Forms\Components\Textarea::make('isi')
                    ->label('Isi Pesan')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpan('full'),
                
                Forms\Components\FileUpload::make('lampiran')
                    ->label('Lampiran')
                    ->multiple()
                    ->maxFiles(5)
                    ->directory('messages')
                    ->visible(fn (callable $get) => $get('jenis') === 'gambar')
                    ->columnSpan('full'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('isi')
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Pengirim')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('recipient.name')
                    ->label('Penerima')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'teks' => 'primary',
                        'gambar' => 'success',
                        'lokasi' => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('isi')
                    ->label('Pesan')
                    ->wrap()
                    ->limit(100)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('dibaca_pada')
                    ->label('Dibaca')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-mark')
                    ->getStateUsing(fn ($record) => $record->dibaca_pada !== null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis')
                    ->label('Jenis Pesan')
                    ->options([
                        'teks' => 'Teks',
                        'gambar' => 'Gambar',
                        'lokasi' => 'Lokasi',
                    ]),
                
                Tables\Filters\Filter::make('dibaca')
                    ->label('Status Dibaca')
                    ->form([
                        Forms\Components\Select::make('status_dibaca')
                            ->label('Status Dibaca')
                            ->options([
                                'dibaca' => 'Sudah Dibaca',
                                'belum_dibaca' => 'Belum Dibaca',
                            ])
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['status_dibaca'] === 'dibaca',
                            fn (Builder $query): Builder => $query->whereNotNull('dibaca_pada'),
                            fn (Builder $query): Builder => $query->whereNull('dibaca_pada'),
                        );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('mark_as_read')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn ($record) => $record->markAsRead())
                    ->visible(fn ($record) => $record->dibaca_pada === null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_as_read')
                        ->label('Tandai Dibaca')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->markAsRead())
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (Collection $records) => $records->contains('dibaca_pada', null)),
                ]),
            ]);
    }
}