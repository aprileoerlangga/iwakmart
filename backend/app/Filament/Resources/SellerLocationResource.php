<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SellerLocationResource\Pages;
use App\Filament\Resources\SellerLocationResource\RelationManagers;
use App\Models\SellerLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Filament\Tables\Columns\TextColumn;

class SellerLocationResource extends Resource
{
    protected static ?string $model = SellerLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationGroup = 'Manajemen Penjual';
    
    protected static ?string $recordTitleAttribute = 'nama_usaha';
    
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Lokasi Penjual';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Lokasi Penjual';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Dasar')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Penjual')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->required(),
                                
                                Forms\Components\TextInput::make('nama_usaha')
                                    ->label('Nama Usaha')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\Select::make('jenis_penjual')
                                    ->label('Jenis Penjual')
                                    ->options(SellerLocation::$sellerTypes)
                                    ->required(),
                                
                                Forms\Components\Textarea::make('deskripsi')
                                    ->label('Deskripsi')
                                    ->rows(4)
                                    ->columnSpan('full'),
                                
                                Forms\Components\TextInput::make('telepon')
                                    ->label('Nomor Telepon')
                                    ->tel()
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\Toggle::make('aktif')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->required(),
                            ])
                            ->columns([
                                'sm' => 2,
                            ]),
                        
                        Forms\Components\Section::make('Alamat')
                            ->schema([
                                Forms\Components\Textarea::make('alamat_lengkap')
                                    ->label('Alamat Lengkap')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpan('full'),
                                
                                Forms\Components\TextInput::make('provinsi')
                                    ->label('Provinsi')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('kota')
                                    ->label('Kota')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('kode_pos')
                                    ->label('Kode Pos')
                                    ->required()
                                    ->maxLength(10),
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
                        Forms\Components\Section::make('Jam Operasional')
                            ->schema([
                                Forms\Components\Repeater::make('jam_operasional')
                                    ->label('Jadwal Operasional')
                                    ->schema([
                                        Forms\Components\Select::make('hari')
                                            ->label('Hari')
                                            ->options([
                                                'Senin' => 'Senin',
                                                'Selasa' => 'Selasa',
                                                'Rabu' => 'Rabu',
                                                'Kamis' => 'Kamis',
                                                'Jumat' => 'Jumat',
                                                'Sabtu' => 'Sabtu',
                                                'Minggu' => 'Minggu',
                                            ])
                                            ->required(),
                                        
                                        Forms\Components\TimePicker::make('jam_buka')
                                            ->label('Jam Buka')
                                            ->seconds(false)
                                            ->required(),
                                        
                                        Forms\Components\TimePicker::make('jam_tutup')
                                            ->label('Jam Tutup')
                                            ->seconds(false)
                                            ->required(),
                                    ])
                                    ->columns(3)
                                    ->columnSpan('full')
                                    ->defaultItems(7),
                            ]),
                        
                        Forms\Components\Section::make('Foto Lokasi')
                            ->schema([
                                Forms\Components\FileUpload::make('foto')
                                    ->label('Foto Lokasi Usaha')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->directory('seller_locations')
                                    ->columnSpan('full'),
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
                Tables\Columns\ImageColumn::make('foto')
                    ->label('Foto')
                    ->circular()
                    ->getStateUsing(fn ($record) => $record->foto ? $record->foto[0] : null),
                
                Tables\Columns\TextColumn::make('nama_usaha')
                    ->label('Nama Usaha')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pemilik')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Jenis Penjual')
                    ->formatStateUsing(fn (string $state): string => SellerLocation::$sellerTypes[$state] ?? $state)
                    ->colors([
                        'primary' => 'nelayan',
                        'success' => 'pembudidaya',
                        'warning' => 'grosir',
                        'info' => 'ritel',
                    ]),
                
                Tables\Columns\TextColumn::make('kota')
                    ->label('Kota')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('telepon')
                    ->label('Telepon')
                    ->searchable(),
                
                Tables\Columns\IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_penjual')
                    ->label('Jenis Penjual')
                    ->options(SellerLocation::$sellerTypes),
                
                Tables\Filters\SelectFilter::make('aktif')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
                
                Tables\Filters\SelectFilter::make('provinsi')
                    ->label('Provinsi')
                    ->options(function () {
                        return SellerLocation::distinct()->pluck('provinsi', 'provinsi')->toArray();
                    }),
                
                Tables\Filters\SelectFilter::make('kota')
                    ->label('Kota')
                    ->options(function () {
                        return SellerLocation::distinct()->pluck('kota', 'kota')->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('aktifkan')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['aktif' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('nonaktifkan')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Collection $records) => $records->each->update(['aktif' => false]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\AppointmentsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSellerLocations::route('/'),
            'create' => Pages\CreateSellerLocation::route('/create'),
            'edit' => Pages\EditSellerLocation::route('/{record}/edit'),
        ];
    }    
}