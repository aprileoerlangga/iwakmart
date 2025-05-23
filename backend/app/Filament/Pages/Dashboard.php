<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CategoryProductChart;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\LatestReviews;
use App\Filament\Widgets\OrderStats;
use App\Filament\Widgets\ProductStats;
use App\Filament\Widgets\SalesChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.pages.dashboard';
    
    protected static ?int $navigationSort = -2;
    
    protected function getHeaderWidgets(): array
    {
        return [
            OrderStats::class,
            ProductStats::class,
            SalesChart::class,
            CategoryProductChart::class,
            LatestOrders::class,
            LatestReviews::class,
        ];
    }
}