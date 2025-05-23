<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Penjualan';
    
    protected static ?int $sort = 3;
    
    protected static ?string $pollingInterval = '60s';
    
    protected function getData(): array
    {
        $data = $this->getOrderData();
        
        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (Rp)',
                    'data' => $data['totals'],
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#36A2EB',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }
    
    protected function getOrderData(): array
    {
        $days = 7;
        $orders = Order::where('status_pembayaran', 'dibayar')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->get()
            ->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            });
        
        $labels = [];
        $totals = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d M');
            
            if (isset($orders[$date])) {
                $totals[] = $orders[$date]->sum('total');
            } else {
                $totals[] = 0;
            }
        }
        
        return [
            'labels' => $labels,
            'totals' => $totals,
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}