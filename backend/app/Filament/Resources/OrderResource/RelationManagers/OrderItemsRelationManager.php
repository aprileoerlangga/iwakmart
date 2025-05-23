<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';
    
    protected static ?string $title = 'Item Pesanan';
    
    protected static ?string $recordTitleAttribute = 'nama_produk';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_produk')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('jumlah')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                
                Forms\Components\TextInput::make('harga')
                    ->label('Harga Satuan (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                
                Forms\Components\TextInput::make('subtotal')
                    ->label('Subtotal (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_produk')
            ->columns([
                Tables\Columns\TextColumn::make('nama_produk')
                    ->label('Nama Produk')
                    ->searchable()
                    ->wrap()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('product.jenis_ikan')
                    ->label('Jenis Ikan')
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
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('harga')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('review.id')
                    ->label('Direview')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-mark')
                    ->getStateUsing(fn ($record) => $record->review !== null)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('penjual_id')
                    ->label('Penjual')
                    ->relationship('seller', 'name')
                    ->searchable(),
                
                Tables\Filters\Filter::make('review')
                    ->label('Status Review')
                    ->form([
                        Forms\Components\Select::make('reviewed')
                            ->label('Status Review')
                            ->options([
                                '1' => 'Sudah Direview',
                                '0' => 'Belum Direview',
                            ])
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['reviewed'] === '1',
                            fn (Builder $query): Builder => $query->whereHas('review'),
                            fn (Builder $query): Builder => $query->whereDoesntHave('review'),
                        );
                    }),
            ])
            ->headerActions([
                // Aksi tambah item pesanan baru (jarang diperlukan)
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('view_review')
                    ->label('Lihat Review')
                    ->icon('heroicon-o-star')
                    ->url(fn ($record) => $record->review ? route('filament.admin.resources.reviews.edit', ['record' => $record->review]) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->review !== null),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}