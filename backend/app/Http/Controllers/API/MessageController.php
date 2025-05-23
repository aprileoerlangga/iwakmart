<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Message;
use App\Models\User;
use App\Models\Product;
use App\Models\Appointment;
use Carbon\Carbon;

class MessageController extends Controller
{
    /**
     * Mendapatkan percakapan antara dua pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\Response
     */
    public function getConversation(Request $request, $userId)
    {
        $user = $request->user();
        $otherUser = User::find($userId);
        
        if (!$otherUser) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan'
            ], 404);
        }
        
        // Dapatkan pesan-pesan
        $messages = Message::conversation($user->id, $otherUser->id)
                        ->with(['sender', 'recipient', 'product', 'appointment'])
                        ->orderBy('created_at', 'asc')
                        ->get();
        
        // Format untuk tampilan
        $messages->transform(function ($item) use ($user) {
            $item->is_sender = $item->pengirim_id === $user->id;
            $item->is_read = $item->dibaca_pada !== null;
            $item->time = Carbon::parse($item->created_at)->format('H:i');
            $item->date = Carbon::parse($item->created_at)->format('d M Y');
            
            // Pastikan lampiran_urls adalah array
            $item->lampiran_urls = [];
            if ($item->lampiran) {
                foreach ($item->lampiran as $attachment) {
                    $item->lampiran_urls[] = asset('storage/' . $attachment);
                }
            }
            
            return $item;
        });
        
        // Tandai pesan yang belum dibaca sebagai telah dibaca
        Message::where('pengirim_id', $otherUser->id)
             ->where('penerima_id', $user->id)
             ->whereNull('dibaca_pada')
             ->update(['dibaca_pada' => now()]);
        
        // Informasi orang yang diajak chat
        $chatPartner = [
            'id' => $otherUser->id,
            'name' => $otherUser->name,
            'is_seller' => $otherUser->hasRole('seller'),
            'avatar' => $otherUser->avatar ? asset('storage/' . $otherUser->avatar) : null,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $messages,
                'chat_partner' => $chatPartner
            ]
        ]);
    }

    /**
     * Mengirim pesan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'penerima_id' => 'required|exists:users,id',
            'isi' => 'required|string',
            'jenis' => 'required|in:teks,gambar,lokasi',
            'produk_id' => 'nullable|exists:products,id',
            'janji_temu_id' => 'nullable|exists:appointments,id',
            'lampiran.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $recipientId = $request->penerima_id;
        
        // Verifikasi penerima
        $recipient = User::find($recipientId);
        if (!$recipient) {
            return response()->json([
                'success' => false,
                'message' => 'Penerima tidak ditemukan'
            ], 404);
        }
        
        // Verifikasi produk jika ada
        if ($request->has('produk_id')) {
            $product = Product::find($request->produk_id);
            if (!$product || !$product->aktif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak valid'
                ], 400);
            }
        }
        
        // Verifikasi janji temu jika ada
        if ($request->has('janji_temu_id')) {
            $appointment = Appointment::find($request->janji_temu_id);
            if (!$appointment || ($appointment->penjual_id !== $user->id && $appointment->pembeli_id !== $user->id) 
                || ($appointment->penjual_id !== $recipientId && $appointment->pembeli_id !== $recipientId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Janji temu tidak valid'
                ], 400);
            }
        }
        
        // Upload lampiran jika ada
        $attachmentNames = [];
        if ($request->hasFile('lampiran')) {
            foreach ($request->file('lampiran') as $attachment) {
                $attachmentName = time() . '_' . uniqid() . '.' . $attachment->getClientOriginalExtension();
                $attachment->storeAs('public/messages', $attachmentName);
                $attachmentNames[] = 'messages/' . $attachmentName;
            }
        }
        
        // Buat pesan
        $message = Message::create([
            'pengirim_id' => $user->id,
            'penerima_id' => $recipientId,
            'produk_id' => $request->produk_id,
            'janji_temu_id' => $request->janji_temu_id,
            'isi' => $request->isi,
            'jenis' => $request->jenis,
            'lampiran' => $attachmentNames
        ]);
        
        // Buat notifikasi untuk penerima
        $recipient->notifications()->create([
            'judul' => 'Pesan Baru',
            'isi' => "{$user->name} mengirim pesan kepada Anda.",
            'jenis' => 'chat',
            'tautan' => '/chat/' . $user->id,
        ]);
        
        $message->load(['sender', 'recipient', 'product', 'appointment']);
        
        // Format untuk tampilan
        $message->is_sender = true;
        $message->is_read = false;
        $message->time = Carbon::parse($message->created_at)->format('H:i');
        $message->date = Carbon::parse($message->created_at)->format('d M Y');
        
        // Pastikan lampiran_urls adalah array
        $message->lampiran_urls = [];
        if ($message->lampiran) {
            foreach ($message->lampiran as $attachment) {
                $message->lampiran_urls[] = asset('storage/' . $attachment);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim',
            'data' => $message
        ], 201);
    }

    /**
     * Menandai pesan sebagai telah dibaca.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function markAsRead(Request $request, Message $message)
    {
        $user = $request->user();
        
        // Pastikan pesan dikirim kepada pengguna
        if ($message->penerima_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Tandai sebagai telah dibaca jika belum dibaca
        if ($message->dibaca_pada === null) {
            $message->markAsRead();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Pesan ditandai sebagai telah dibaca'
        ]);
    }
    
    /**
     * Mendapatkan daftar percakapan pengguna.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getConversations(Request $request)
    {
        $user = $request->user();
        
        // Dapatkan pesan terbaru untuk setiap percakapan
        $conversations = Message::where(function($query) use ($user) {
                $query->where('pengirim_id', $user->id)
                      ->orWhere('penerima_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function($message) use ($user) {
                // Grup berdasarkan ID pengguna lain dalam percakapan
                return $message->pengirim_id == $user->id ? $message->penerima_id : $message->pengirim_id;
            })
            ->map(function($messages) use ($user) {
                // Dapatkan pesan terbaru dari setiap percakapan
                $lastMessage = $messages->first();
                
                // Dapatkan pengguna lain
                $otherUserId = $lastMessage->pengirim_id == $user->id ? $lastMessage->penerima_id : $lastMessage->pengirim_id;
                $otherUser = User::find($otherUserId);
                
                // Dapatkan jumlah pesan yang belum dibaca
                $unreadCount = $messages->where('pengirim_id', $otherUserId)
                                      ->where('penerima_id', $user->id)
                                      ->whereNull('dibaca_pada')
                                      ->count();
                
                // Format untuk tampilan
                return [
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                        'is_seller' => $otherUser->hasRole('seller'),
                        'avatar' => $otherUser->avatar ? asset('storage/' . $otherUser->avatar) : null,
                    ],
                    'last_message' => [
                        'id' => $lastMessage->id,
                        'content' => $lastMessage->isi,
                        'type' => $lastMessage->jenis,
                        'is_sender' => $lastMessage->pengirim_id === $user->id,
                        'is_read' => $lastMessage->dibaca_pada !== null,
                        'time' => Carbon::parse($lastMessage->created_at)->format('H:i'),
                        'date' => Carbon::parse($lastMessage->created_at)->format('d M Y'),
                        'created_at' => $lastMessage->created_at,
                    ],
                    'unread_count' => $unreadCount
                ];
            })
            ->sortByDesc(function($conversation) {
                return $conversation['last_message']['created_at'];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $conversations
        ]);
    }
}