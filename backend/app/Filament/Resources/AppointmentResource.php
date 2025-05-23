<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Filament\Resources\AppointmentResource\RelationManagers;
use App\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Filament\Tables\Columns\TextColumn;


class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationGroup = 'Manajemen Penjual';
    
    protected static ?string $recordTitleAttribute = 'id';
    
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Janji Temu';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Janji Temu';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Janji Temu')
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
                                
                                Forms\Components\Select::make('lokasi_penjual_id')
                                    ->label('Lokasi Penjual')
                                    ->relationship('sellerLocation', 'nama_usaha')
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
                            ])
                            ->columns([
                                'sm' => 2,
                            ]),
                    ])
                    ->columnSpan([
                        'sm' => 2,
                    ]),
                
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Lokasi')
                            ->schema([
                                Forms\Components\Placeholder::make('info_lokasi')
                                    ->label('Informasi Lokasi')
                                    ->content(function ($record) {
                                        if (!$record || !$record->sellerLocation) {
                                            return 'Pilih lokasi penjual terlebih dahulu';
                                        }
                    
                                        $location = $record->sellerLocation;
                                        return "
                                            Nama Usaha: {$location->nama_usaha} <br>
                                            Jenis: {$location->seller_type_text} <br>
                                            Alamat: {$location->alamat_lengkap}, {$location->kecamatan}, {$location->kota}, {$location->provinsi} {$location->kode_pos} <br>
                                            Telepon: {$location->telepon}
                                        ";
                                    }),
                                
                                Forms\Components\Placeholder::make('jam_operasional')
                                    ->label('Jam Operasional')
                                    ->content(function ($record) {
                                        if (!$record || !$record->sellerLocation) {
                                            return 'Pilih lokasi penjual terlebih dahulu';
                                        }
                    
                                        return $record->sellerLocation->formatted_operating_hours;
                                    }),
                            ]),
                        
                        Forms\Components\Section::make('Tanggal')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Dibuat Pada')
                                    ->content(fn ($record) => $record ? $record->created_at->format('d M Y H:i') : '-'),
                                
                                Forms\Components\Placeholder::make('updated_at')
                                    ->label('Terakhir Diperbarui')
                                    ->content(fn ($record) => $record ? $record->updated_at->format('d M Y H:i') : '-'),
                            ]),
                    ])
                    ->columnSpan([
                        'sm' => 1,
                    ]),
            ])
            ->columns([
                'sm' => 3,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('buyer.name')
                    ->label('Pembeli')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Penjual')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('sellerLocation.nama_usaha')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tanggal_janji')
                    ->label('Tanggal & Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => Appointment::$statuses[$state] ?? $state)
                    ->colors([
                        'warning' => 'menunggu',
                        'success' => 'dikonfirmasi',
                        'primary' => 'selesai',
                        'danger' => 'dibatalkan',
                    ]),
                
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
                
                Tables\Filters\SelectFilter::make('penjual_id')
                    ->label('Penjual')
                    ->relationship('seller', 'name')
                    ->searchable(),
                
                Tables\Filters\SelectFilter::make('pembeli_id')
                    ->label('Pembeli')
                    ->relationship('buyer', 'name')
                    ->searchable(),
                
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
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['menunggu', 'dikonfirmasi'])->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'menunggu')->exists()
            ? 'warning'
            : 'primary';
    }
}