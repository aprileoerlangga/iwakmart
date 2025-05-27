<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Manajemen Pesanan';
    
    protected static ?string $recordTitleAttribute = 'nomor_pesanan';
    
    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Pesanan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Pesanan';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informasi Pesanan')
                            ->schema([
                                Forms\Components\TextInput::make('nomor_pesanan')
                                    ->label('Nomor Pesanan')
                                    ->required()
                                    ->disabled()
                                    ->unique(Order::class, 'nomor_pesanan', ignoreRecord: true),
                                
                                Forms\Components\Select::make('user_id')
                                    ->label('Pelanggan')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->required()
                                    ->disabled(),
                                
                                Forms\Components\Select::make('status')
                                    ->label('Status Pesanan')
                                    ->options(Order::$statuses)
                                    ->required(),
                                
                                Forms\Components\Select::make('status_pembayaran')
                                    ->label('Status Pembayaran')
                                    ->options(Order::$paymentStatuses)
                                    ->required(),
                                
                                Forms\Components\TextInput::make('metode_pembayaran')
                                    ->label('Metode Pembayaran')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('id_pembayaran')
                                    ->label('ID Pembayaran')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('metode_pengiriman')
                                    ->label('Metode Pengiriman')
                                    ->maxLength(255),
                                
                                Forms\Components\Textarea::make('catatan')
                                    ->label('Catatan Pesanan')
                                    ->maxLength(65535)
                                    ->columnSpan('full'),
                            ])
                            ->columns([
                                'sm' => 2,
                            ]),
                        
                        Forms\Components\Section::make('Informasi Biaya')
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal (Rp)')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('Rp'),
                                
                                Forms\Components\TextInput::make('biaya_kirim')
                                    ->label('Biaya Pengiriman (Rp)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp'),
                                
                                Forms\Components\TextInput::make('pajak')
                                    ->label('Pajak (Rp)')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp'),
                                
                                Forms\Components\TextInput::make('total')
                                    ->label('Total (Rp)')
                                    ->numeric()
                                    ->disabled()
                                    ->prefix('Rp'),
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
                        Forms\Components\Section::make('Alamat Pengiriman')
                            ->schema([
                                Forms\Components\Select::make('alamat_id')
                                    ->label('Alamat')
                                    ->relationship('address', 'nama_penerima')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                                        "{$record->nama_penerima} - {$record->alamat_lengkap}")
                                    ->searchable()
                                    ->disabled(),
                                
                                Forms\Components\Placeholder::make('info_alamat')
                                    ->label('Informasi Alamat')
                                    ->content(function ($record) {
                                        if (!$record || !$record->address) {
                                            return 'Pilih alamat terlebih dahulu';
                                        }
                    
                                        $address = $record->address;
                                        return "
                                            Penerima: {$address->nama_penerima} <br>
                                            Telepon: {$address->telepon} <br>
                                            Alamat: {$address->alamat_lengkap}, {$address->kecamatan}, {$address->kota}, {$address->provinsi} {$address->kode_pos}
                                        ";
                                    }),
                            ]),
                        
                        Forms\Components\Section::make('Tanggal')
                            ->schema([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label('Tanggal Pemesanan')
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
                Tables\Columns\TextColumn::make('nomor_pesanan')
                    ->label('Nomor Pesanan')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Order::$statuses[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning',
                        'dibayar' => 'info',
                        'diproses' => 'primary',
                        'dikirim' => 'purple',
                        'selesai' => 'success',
                        'dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Order::$paymentStatuses[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning',
                        'dibayar' => 'success',
                        'gagal' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Pemesanan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Pesanan')
                    ->options(Order::$statuses),
                
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->options(Order::$paymentStatuses),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            // ->actions([
            //     Tables\Actions\EditAction::make(),
            //     Tables\Actions\Action::make('download_invoice')
            //         ->label('Invoice')
            //         ->icon('heroicon-o-document-arrow-down')
            //         ->url(fn (Order $record) => route('orders.invoice.download', $record))
            //         ->openUrlInNewTab(),
            // ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('update_status')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status Baru')
                                ->options(Order::$statuses)
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
            RelationManagers\OrderItemsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }    
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNotIn('status', ['selesai', 'dibatalkan'])->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereNotIn('status', ['selesai', 'dibatalkan'])->count() > 0
            ? 'warning'
            : 'primary';
    }
}