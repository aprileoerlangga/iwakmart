<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStats extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pesanan Hari Ini', Order::whereDate('created_at', today())->count())
                ->description('Jumlah pesanan baru hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
            
            Stat::make('Pendapatan Hari Ini', 'Rp ' . number_format(
                Order::whereDate('created_at', today())
                    ->where('status_pembayaran', 'dibayar')
                    ->sum('total'), 0, ',', '.'
            ))
                ->description('Total pendapatan dari pesanan hari ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            
            Stat::make('Pesanan Perlu Diproses', Order::where('status', 'dibayar')->count())
                ->description('Pesanan yang perlu segera diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}