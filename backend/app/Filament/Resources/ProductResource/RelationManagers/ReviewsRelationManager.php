<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';
    
    protected static ?string $title = 'Ulasan Produk';
    
    protected static ?string $recordTitleAttribute = 'komentar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('rating')
                    ->label('Rating')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->step(1),
                
                Forms\Components\Textarea::make('komentar')
                    ->label('Komentar')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('komentar')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('komentar')
                    ->label('Komentar')
                    ->wrap()
                    ->limit(100)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reviewReply.comment')
                    ->label('Balasan')
                    ->wrap()
                    ->placeholder('Belum dibalas')
                    ->limit(100),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        '1' => '1 Bintang',
                        '2' => '2 Bintang',
                        '3' => '3 Bintang',
                        '4' => '4 Bintang',
                        '5' => '5 Bintang',
                    ]),
                
                Tables\Filters\Filter::make('belum_dibalas')
                    ->label('Belum Dibalas')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('reviewReply'))
                    ->toggle(),
            ])
            ->headerActions([
                // Tidak perlu aksi create karena ulasan dibuat oleh pelanggan
            ])
            ->actions([
                Tables\Actions\Action::make('balas')
                    ->label('Balas')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Balasan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->reviewReply()->updateOrCreate(
                            [], // where clause
                            [
                                'user_id' => Auth::id(),
                                'comment' => $data['comment'],
                            ]
                        );
                    })
                    ->visible(fn ($record) => !$record->reviewReply),
                
                Tables\Actions\Action::make('edit_balas')
                    ->label('Edit Balasan')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\Textarea::make('comment')
                            ->label('Balasan')
                            ->required()
                            ->default(fn ($record) => $record->reviewReply?->comment)
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, $record): void {
                        if ($record->reviewReply) {
                            $record->reviewReply->update([
                                'comment' => $data['comment'],
                            ]);
                        }
                    })
                    ->visible(fn ($record) => $record->reviewReply),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}