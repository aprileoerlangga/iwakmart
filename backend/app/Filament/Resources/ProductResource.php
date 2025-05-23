<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Manajemen Produk';
    protected static ?string $recordTitleAttribute = 'nama';
    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Produk';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Produk';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Dasar')
                            ->schema([
                                TextInput::make('nama')
                                    ->required()
                                    ->maxLength(255)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) =>
                                        $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Product::class, 'slug', ignoreRecord: true),
                                Forms\Components\RichEditor::make('deskripsi')
                                    ->required()
                                    ->columnSpan('full'),
                                Forms\Components\FileUpload::make('gambar')
                                    ->label('Gambar Produk')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->directory('products')
                                    ->columnSpan('full')
                                    ->required(),
                            ])
                            ->columns([
                                'sm' => 2,
                            ]),
                        Forms\Components\Section::make('Harga & Inventori')
                            ->schema([
                                TextInput::make('harga')
                                    ->label('Harga (Rp)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),
                                TextInput::make('stok')
                                    ->label('Stok')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),
                                TextInput::make('berat')
                                    ->label('Berat (kg)')
                                    ->numeric()
                                    ->required()
                                    ->step(0.01)
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),
                            ])
                            ->columns([
                                'sm' => 2,
                            ]),
                        Forms\Components\Section::make('Informasi Ikan')
                            ->schema([
                                Forms\Components\Select::make('jenis_ikan')
                                    ->label('Jenis Ikan')
                                    ->options([
                                        'segar' => 'Ikan Segar',
                                        'beku' => 'Ikan Beku',
                                        'olahan' => 'Ikan Olahan',
                                        'hidup' => 'Ikan Hidup',
                                    ])
                                    ->required()
                                    ->default('segar'),
                                TextInput::make('spesies_ikan')
                                    ->label('Spesies Ikan')
                                    ->maxLength(255),
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
                        Forms\Components\Section::make('Pengaturan')
                            ->schema([
                                Forms\Components\Select::make('kategori_id')
                                    ->label('Kategori')
                                    ->relationship('category', 'nama')
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('penjual_id')
                                    ->label('Penjual')
                                    ->relationship('seller', 'name')
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Toggle::make('aktif')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->required(),
                                Forms\Components\Toggle::make('unggulan')
                                    ->label('Produk Unggulan')
                                    ->default(false)
                                    ->required(),
                            ]),
                        Forms\Components\Section::make('Rating')
                            ->schema([
                                Forms\Components\Placeholder::make('rating_rata')
                                    ->label('Rating Rata-rata')
                                    ->content(fn ($record) => $record ? number_format($record->rating_rata, 1) . ' / 5.0' : '0 / 5.0'),
                                Forms\Components\Placeholder::make('jumlah_ulasan')
                                    ->label('Jumlah Ulasan')
                                    ->content(fn ($record) => $record ? $record->jumlah_ulasan : '0'),
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
                Tables\Columns\ImageColumn::make('gambar')
                    ->label('Gambar')
                    ->circular()
                    ->getStateUsing(fn ($record) => $record->gambar ? $record->gambar[0] : null),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.nama')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_ikan')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'segar' => 'success',
                        'beku' => 'primary',
                        'olahan' => 'warning',
                        'hidup' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Penjual')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('unggulan')
                    ->label('Unggulan')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('category', 'nama')
                    ->searchable()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('jenis_ikan')
                    ->label('Jenis Ikan')
                    ->options([
                        'segar' => 'Ikan Segar',
                        'beku' => 'Ikan Beku',
                        'olahan' => 'Ikan Olahan',
                        'hidup' => 'Ikan Hidup',
                    ]),
                Tables\Filters\SelectFilter::make('penjual_id')
                    ->label('Penjual')
                    ->relationship('seller', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('aktif')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
                Tables\Filters\SelectFilter::make('unggulan')
                    ->label('Unggulan')
                    ->options([
                        '1' => 'Ya',
                        '0' => 'Tidak',
                    ]),
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
                    Tables\Actions\BulkAction::make('jadikan_unggulan')
                        ->label('Jadikan Unggulan')
                        ->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each->update(['unggulan' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('hapus_unggulan')
                        ->label('Hapus dari Unggulan')
                        ->icon('heroicon-o-no-symbol')
                        ->action(fn (Collection $records) => $records->each->update(['unggulan' => false]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReviewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('aktif', true)->count();
    }
}
