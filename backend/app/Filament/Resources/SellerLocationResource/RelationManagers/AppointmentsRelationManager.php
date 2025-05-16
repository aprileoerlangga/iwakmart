<?php

namespace App\Filament\Resources\SellerLocationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Appointment;
use Illuminate\Support\Collection;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';
    
    protected static ?string $title = 'Janji Temu';
    
    protected static ?string $recordTitleAttribute = 'tanggal_janji';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('penjual_id')
                    ->label('Penjual')
                    ->relationship('seller', 'name')
                    ->searchable()
                    ->required(),
                
                Forms\Components\Select::make('pembeli_id')
                    ->label('Pembeli')
                    ->relationship('buyer', 'name')
                    ->searchable()
                    ->required(),
                
                Forms\Components\DateTimePicker::make('tanggal_janji')
                    ->label('Tanggal dan Waktu')
                    ->required(),
                
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(Appointment::$statuses)
                    ->required(),
                
                Forms\Components\TextInput::make('tujuan')
                    ->label('Tujuan')
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('catatan')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->columnSpan('full'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tanggal_janji')
            ->columns([
                Tables\Columns\TextColumn::make('buyer.name')
                    ->label('Pembeli')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Penjual')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('tanggal_janji')
                    ->label('Tanggal & Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Appointment::$statuses[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning',
                        'dikonfirmasi' => 'success',
                        'selesai' => 'primary',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Tujuan')
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Appointment::$statuses),
                
                Tables\Filters\Filter::make('tanggal_janji')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_janji', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_janji', '<=', $date),
                            );
                    }),
                
                Tables\Filters\Filter::make('upcoming')
                    ->label('Akan Datang')
                    ->query(fn (Builder $query): Builder => $query->where('tanggal_janji', '>=', now()))
                    ->toggle(),
                
                Tables\Filters\Filter::make('past')
                    ->label('Sudah Lewat')
                    ->query(fn (Builder $query): Builder => $query->where('tanggal_janji', '<', now()))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('update_status')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-arrows-right-left')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Baru')
                            ->options(Appointment::$statuses)
                            ->required(),
                    ])
                    ->action(function (Appointment $record, array $data): void {
                        $record->updateStatus($data['status']);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('update_status')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-arrows-right-left')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status Baru')
                                ->options(Appointment::$statuses)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->updateStatus($data['status']);
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}