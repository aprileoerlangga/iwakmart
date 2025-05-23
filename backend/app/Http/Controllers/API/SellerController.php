<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Appointment;
use App\Models\Review;
use Carbon\Carbon;

class SellerController extends Controller
{
    /**
     * Mendapatkan data dashboard untuk penjual.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Hitung total pendapatan
        $totalRevenue = OrderItem::where('penjual_id', $user->id)
                               ->whereHas('order', function($q) {
                                   $q->where('status_pembayaran', 'dibayar');
                               })
                               ->sum('subtotal');
        
        // Hitung jumlah pesanan
        $orderCount = OrderItem::where('penjual_id', $user->id)
                             ->distinct('pesanan_id')
                             ->count('pesanan_id');
        
        // Hitung jumlah produk
        $productCount = Product::where('penjual_id', $user->id)->count();
        
        // Hitung jumlah produk stok habis
        $outOfStockCount = Product::where('penjual_id', $user->id)
                                ->where('stok', 0)
                                ->count();
        
        // Hitung rating rata-rata
        $avgRating = Review::whereHas('product', function($q) use ($user) {
                           $q->where('penjual_id', $user->id);
                       })
                       ->avg('rating');
        
        $avgRating = round($avgRating, 1);
        
        // Hitung jumlah ulasan yang belum dibalas
        $unrepliedReviews = Review::whereHas('product', function($q) use ($user) {
                                 $q->where('penjual_id', $user->id);
                             })
                             ->doesntHave('reviewReply')
                             ->count();
        
        // Hitung jumlah pesanan berdasarkan status
        $orderStats = OrderItem::where('penjual_id', $user->id)
                             ->join('orders', 'order_items.pesanan_id', '=', 'orders.id')
                             ->select('orders.status', DB::raw('count(*) as count'))
                             ->groupBy('orders.status')
                             ->get()
                             ->pluck('count', 'status')
                             ->toArray();
        
        // Lengkapi dengan status yang kosong
        foreach (['menunggu', 'dibayar', 'diproses', 'dikirim', 'selesai', 'dibatalkan'] as $status) {
            if (!isset($orderStats[$status])) {
                $orderStats[$status] = 0;
            }
        }
        
        // Dapatkan data penjualan 7 hari terakhir
        $salesData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dailySales = OrderItem::where('penjual_id', $user->id)
                                 ->whereHas('order', function($q) use ($date) {
                                     $q->where('status_pembayaran', 'dibayar')
                                       ->whereDate('created_at', $date);
                                 })
                                 ->sum('subtotal');
            
            $salesData[] = [
                'date' => Carbon::now()->subDays($i)->format('d M'),
                'sales' => $dailySales
            ];
        }
        
        // Dapatkan pesanan terbaru
        $latestOrders = OrderItem::where('penjual_id', $user->id)
                              ->with(['order', 'order.user', 'product'])
                              ->latest('created_at')
                              ->take(5)
                              ->get()
                              ->map(function($item) {
                                  return [
                                      'id' => $item->id,
                                      'order_id' => $item->order->id,
                                      'order_number' => $item->order->nomor_pesanan,
                                      'product_name' => $item->nama_produk,
                                      'customer_name' => $item->order->user->name,
                                      'status' => $item->order->status,
                                      'status_text' => Order::$statuses[$item->order->status] ?? $item->order->status,
                                      'quantity' => $item->jumlah,
                                      'price' => $item->harga,
                                      'subtotal' => $item->subtotal,
                                      'formatted_subtotal' => 'Rp ' . number_format($item->subtotal, 0, ',', '.'),
                                      'created_at' => $item->created_at->format('d M Y H:i')
                                  ];
                              });
        
        // Dapatkan janji temu akan datang
        $upcomingAppointments = Appointment::where('penjual_id', $user->id)
                                       ->where('tanggal_janji', '>=', now())
                                       ->whereIn('status', ['menunggu', 'dikonfirmasi'])
                                       ->with(['buyer', 'sellerLocation'])
                                       ->orderBy('tanggal_janji')
                                       ->take(5)
                                       ->get()
                                       ->map(function($item) {
                                           return [
                                               'id' => $item->id,
                                               'customer_name' => $item->buyer->name,
                                               'location_name' => $item->sellerLocation->nama_usaha,
                                               'date' => Carbon::parse($item->tanggal_janji)->format('d M Y'),
                                               'time' => Carbon::parse($item->tanggal_janji)->format('H:i'),
                                               'status' => $item->status,
                                               'status_text' => Appointment::$statuses[$item->status] ?? $item->status,
                                               'purpose' => $item->tujuan
                                           ];
                                       });

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'formatted_total_revenue' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'order_count' => $orderCount,
                'product_count' => $productCount,
                'out_of_stock_count' => $outOfStockCount,
                'avg_rating' => $avgRating,
                'unreplied_reviews' => $unrepliedReviews,
                'order_stats' => $orderStats,
                'sales_data' => $salesData,
                'latest_orders' => $latestOrders,
                'upcoming_appointments' => $upcomingAppointments
            ]
        ]);
    }
}