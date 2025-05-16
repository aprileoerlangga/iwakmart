<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    /**
     * Mendapatkan daftar notifikasi untuk pengguna yang login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Notification::where('user_id', $user->id);
        
        // Filter berdasarkan jenis
        if ($request->has('jenis') && in_array($request->jenis, array_keys(Notification::$types))) {
            $query->where('jenis', $request->jenis);
        }
        
        // Filter berdasarkan status dibaca
        if ($request->has('dibaca')) {
            if ($request->boolean('dibaca')) {
                $query->whereNotNull('dibaca_pada');
            } else {
                $query->whereNull('dibaca_pada');
            }
        }
        
        // Pengurutan
        $query->orderBy('created_at', 'desc');
        
        // Paginasi
        $perPage = $request->per_page ?? 10;
        $notifications = $query->paginate($perPage);
        
        // Format untuk tampilan
        $notifications->getCollection()->transform(function ($item) {
            $item->type_text = Notification::$types[$item->jenis] ?? $item->jenis;
            $item->time_ago = $item->created_at->diffForHumans();
            $item->is_read = $item->dibaca_pada !== null;
            return $item;
        });
        
        // Hitung notifikasi yang belum dibaca
        $unreadCount = Notification::where('user_id', $user->id)
                                  ->whereNull('dibaca_pada')
                                  ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]
        ]);
    }

    /**
     * Menandai notifikasi sebagai telah dibaca.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Notification  $notification
     * @return \Illuminate\Http\Response
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $user = $request->user();
        
        // Pastikan notifikasi milik pengguna
        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Tandai sebagai telah dibaca jika belum dibaca
        if ($notification->dibaca_pada === null) {
            // Perbaikan: Jangan menggunakan method jika belum didefinisikan
            // Langsung update field dibaca_pada
            $notification->dibaca_pada = now();
            $notification->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sebagai telah dibaca',
            'data' => $notification
        ]);
    }
    
    /**
     * Menandai semua notifikasi sebagai telah dibaca.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        $affected = Notification::where('user_id', $user->id)
                              ->whereNull('dibaca_pada')
                              ->update(['dibaca_pada' => now()]);
        
        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi ditandai sebagai telah dibaca',
            'data' => [
                'affected_count' => $affected
            ]
        ]);
    }
}