<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Section;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'Manajemen Produk';
    
    protected static ?string $recordTitleAttribute = 'nama';
    
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Kategori';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => 
                                $set('slug', Str::slug($state))),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Category::class, 'slug', ignoreRecord: true),
                        
                        Forms\Components\Select::make('induk_id')
                            ->label('Kategori Induk')
                            ->relationship('parent', 'nama')
                            ->searchable()
                            ->placeholder('Pilih kategori induk (opsional)'),
                        
                        Forms\Components\Textarea::make('deskripsi')
                            ->columnSpan('full')
                            ->rows(4),
                        
                        Forms\Components\FileUpload::make('gambar')
                            ->image()
                            ->maxSize(1024)
                            ->directory('categories')
                            ->columnSpan('full'),
                        
                        Forms\Components\Toggle::make('aktif')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns([
                        'sm' => 2,
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\ImageColumn::make('gambar')
                    ->defaultImageUrl(url('/images/placeholder.png'))
                    ->circular(),
                
                Tables\Columns\TextColumn::make('nama')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('parent.nama')
                    ->label('Kategori Induk')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Kategori Utama'),
                
                Tables\Columns\IconColumn::make('aktif')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('aktif')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
                
                Tables\Filters\SelectFilter::make('induk_id')
                    ->label('Kategori Induk')
                    ->relationship('parent', 'nama')
                    ->searchable()
                    ->placeholder('Semua Kategori'),
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
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }    
}