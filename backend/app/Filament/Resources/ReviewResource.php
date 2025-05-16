<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Filament\Resources\ReviewResource\RelationManagers;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;


class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationGroup = 'Pelanggan & Ulasan';
    
    protected static ?string $recordTitleAttribute = 'komentar';
    
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Ulasan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ulasan';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Ulasan')
                            ->schema([
                                Forms\Components\Select::make('produk_id')
                                    ->label('Produk')
                                    ->relationship('product', 'nama')
                                    ->searchable()
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null),
                                
                                Forms\Components\Select::make('user_id')
                                    ->label('Pelanggan')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null),
                                
                                Forms\Components\Select::make('item_pesanan_id')
                                    ->label('Item Pesanan')
                                    ->relationship('orderItem', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "Order #{$record->order->nomor_pesanan} - {$record->nama_produk}")
                                    ->searchable()
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null),
                                
                                Forms\Components\Radio::make('rating')
                                    ->label('Rating')
                                    ->options([
                                        1 => '1 Bintang',
                                        2 => '2 Bintang',
                                        3 => '3 Bintang',
                                        4 => '4 Bintang',
                                        5 => '5 Bintang',
                                    ])
                                    ->inline()
                                    ->required(),
                                
                                Forms\Components\Textarea::make('komentar')
                                    ->label('Komentar')
                                    ->required()
                                    ->columnSpan('full'),
                                
                                Forms\Components\FileUpload::make('gambar')
                                    ->label('Gambar')
                                    ->image()
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->directory('reviews')
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
                        Forms\Components\Section::make('Balasan')
                            ->schema([
                                Forms\Components\Placeholder::make('status_balasan')
                                    ->label('Status Balasan')
                                    ->content(fn ($record) => $record && $record->reviewReply ? 'Sudah Dibalas' : 'Belum Dibalas'),
                                
                                Forms\Components\Textarea::make('review_reply.comment')
                                    ->label('Balasan')
                                    ->placeholder('Belum ada balasan')
                                    ->disabled()
                                    ->visible(fn ($record) => $record && $record->reviewReply),
                                
                                Forms\Components\Placeholder::make('replied_by')
                                    ->label('Dibalas Oleh')
                                    ->content(fn ($record) => $record && $record->reviewReply ? $record->reviewReply->user->name : '-')
                                    ->visible(fn ($record) => $record && $record->reviewReply),
                                
                                Forms\Components\Placeholder::make('replied_at')
                                    ->label('Dibalas Pada')
                                    ->content(fn ($record) => $record && $record->reviewReply ? $record->reviewReply->created_at->format('d M Y H:i') : '-')
                                    ->visible(fn ($record) => $record && $record->reviewReply),
                                
                                Forms\Components\Textarea::make('balasan_baru')
                                    ->label('Tambahkan Balasan')
                                    ->placeholder('Tulis balasan ulasan disini')
                                    ->visible(fn ($record) => $record && !$record->reviewReply),
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
                Tables\Columns\TextColumn::make('product.nama')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (int $state): string => str_repeat('⭐', $state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('komentar')
                    ->label('Komentar')
                    ->wrap()
                    ->limit(50)
                    ->searchable(),
                
                Tables\Columns\ImageColumn::make('gambar')
                    ->label('Gambar')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->getStateUsing(fn ($record) => $record->gambar ?? []),
                
                Tables\Columns\IconColumn::make('reviewReply.id')
                    ->label('Dibalas')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-mark')
                    ->getStateUsing(fn ($record) => $record->reviewReply !== null),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Rating')
                    ->options([
                        1 => '1 Bintang',
                        2 => '2 Bintang',
                        3 => '3 Bintang',
                        4 => '4 Bintang',
                        5 => '5 Bintang',
                    ]),
                
                Tables\Filters\Filter::make('has_reply')
                    ->label('Status Balasan')
                    ->form([
                        Forms\Components\Select::make('status_balasan')
                            ->label('Status Balasan')
                            ->options([
                                'dibalas' => 'Sudah Dibalas',
                                'belum_dibalas' => 'Belum Dibalas',
                            ])
                            ->required(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['status_balasan'] === 'dibalas',
                            fn (Builder $query): Builder => $query->has('reviewReply'),
                            fn (Builder $query): Builder => $query->doesntHave('reviewReply'),
                        );
                    }),
                
                Tables\Filters\Filter::make('created_at')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('reply')
                    ->label('Balas')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Balasan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, Review $record): void {
                        $record->reviewReply()->updateOrCreate(
                            [], // where clause
                            [
                                'user_id' => Auth::id(),
                                'comment' => $data['comment'],
                            ]
                        );
                    })
                    ->visible(fn (Review $record) => !$record->reviewReply),
                
                Tables\Actions\Action::make('edit_reply')
                    ->label('Edit Balasan')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Balasan')
                            ->required()
                            ->default(fn (Review $record) => $record->reviewReply?->comment)
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, Review $record): void {
                        if ($record->reviewReply) {
                            $record->reviewReply->update([
                                'comment' => $data['comment'],
                            ]);
                        }
                    })
                    ->visible(fn (Review $record) => $record->reviewReply),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'view' => Pages\ViewReview::route('/{record}'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDoesntHave('reviewReply')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereDoesntHave('reviewReply')->count() > 0
            ? 'warning'
            : 'primary';
    }
}