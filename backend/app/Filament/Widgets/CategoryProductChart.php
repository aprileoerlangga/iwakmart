<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Category;
use Filament\Widgets\ChartWidget;

class CategoryProductChart extends ChartWidget
{
    protected static ?string $heading = 'Produk per Kategori';
    
    protected static ?int $sort = 4;
    
    protected function getData(): array
    {
        $categories = Category::withCount('products')->orderByDesc('products_count')->take(7)->get();
        
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Produk',
                    'data' => $categories->pluck('products_count')->toArray(),
                    'backgroundColor' => [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#808080'
                    ],
                ],
            ],
            'labels' => $categories->pluck('nama')->toArray(),
        ];
    }
    
    protected function getType(): string
    {
        return 'pie';
    }
}