<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Appointment;
use App\Models\SellerLocation;
use App\Models\User;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Mendapatkan daftar janji temu untuk pengguna yang login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Appointment::with(['seller', 'buyer', 'sellerLocation'])
                           ->where(function($q) use ($user) {
                               $q->where('pembeli_id', $user->id)
                                 ->orWhere('penjual_id', $user->id);
                           });
        
        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter berdasarkan tanggal (akan datang atau sudah lewat)
        if ($request->has('upcoming') && $request->boolean('upcoming')) {
            $query->where('tanggal_janji', '>=', Carbon::now())
                  ->whereIn('status', ['menunggu', 'dikonfirmasi']);
        } elseif ($request->has('past') && $request->boolean('past')) {
            $query->where(function($q) {
                $q->where('tanggal_janji', '<', Carbon::now())
                  ->orWhereIn('status', ['selesai', 'dibatalkan']);
            });
        }
        
        // Pengurutan
        $sortBy = $request->sort_by ?? 'tanggal_janji';
        $sortDir = $request->sort_dir ?? 'asc';
        
        if ($sortBy === 'tanggal_janji') {
            $query->orderBy($sortBy, $sortDir);
        }
        
        // Paginasi
        $perPage = $request->per_page ?? 10;
        $appointments = $query->paginate($perPage);
        
        // Format untuk tampilan
        $appointments->getCollection()->transform(function ($item) {
            $item->formatted_date = Carbon::parse($item->tanggal_janji)->format('d M Y');
            $item->formatted_time = Carbon::parse($item->tanggal_janji)->format('H:i');
            $item->status_text = Appointment::$statuses[$item->status] ?? $item->status;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }

    /**
     * Menyimpan janji temu baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'penjual_id' => 'required|exists:users,id',
            'lokasi_penjual_id' => 'required|exists:seller_locations,id',
            'tanggal_janji' => 'required|date|after:now',
            'tujuan' => 'nullable|string|max:255',
            'catatan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        // Verifikasi penjual dan lokasi
        $seller = User::find($request->penjual_id);
        if (!$seller || !$seller->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Penjual tidak valid'
            ], 400);
        }
        
        $location = SellerLocation::find($request->lokasi_penjual_id);
        if (!$location || $location->user_id != $request->penjual_id || !$location->aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi penjual tidak valid'
            ], 400);
        }
        
        // Verifikasi waktu (jam operasional)
        $appointmentDateTime = Carbon::parse($request->tanggal_janji);
        $appointmentDayName = $appointmentDateTime->translatedFormat('l'); // nama hari dalam bahasa yang sesuai
        $appointmentTime = $appointmentDateTime->format('H:i:s');
        
        // Konversi nama hari ke bahasa Indonesia (sesuaikan dengan format yang disimpan)
        $dayMapping = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];
        
        $dayName = $dayMapping[$appointmentDayName] ?? $appointmentDayName;
        
        // Cek jam operasional (asumsikan jam_operasional berisi array dengan elemen 'hari', 'jam_buka', dan 'jam_tutup')
        $operatingHours = collect($location->jam_operasional ?? []);
        $daySchedule = $operatingHours->firstWhere('hari', $dayName);
        
        if (!$daySchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak beroperasi pada hari ' . $dayName
            ], 400);
        }
        
        if (isset($daySchedule['jam_buka']) && isset($daySchedule['jam_tutup'])) {
            $openTime = Carbon::parse($daySchedule['jam_buka']);
            $closeTime = Carbon::parse($daySchedule['jam_tutup']);
            $appointmentTimeObj = Carbon::parse($appointmentTime);
            
            if ($appointmentTimeObj->lt($openTime) || $appointmentTimeObj->gt($closeTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Waktu di luar jam operasional (' . $daySchedule['jam_buka'] . ' - ' . $daySchedule['jam_tutup'] . ')'
                ], 400);
            }
        }
        
        // Buat janji temu
        $appointment = Appointment::create([
            'penjual_id' => $request->penjual_id,
            'pembeli_id' => $user->id,
            'lokasi_penjual_id' => $request->lokasi_penjual_id,
            'tanggal_janji' => $request->tanggal_janji,
            'status' => 'menunggu',
            'tujuan' => $request->tujuan,
            'catatan' => $request->catatan
        ]);
        
        // Buat notifikasi untuk pembeli
        $user->notifications()->create([
            'judul' => 'Janji Temu Baru',
            'isi' => "Janji temu dengan {$seller->name} telah dibuat dan menunggu konfirmasi.",
            'jenis' => 'janji_temu',
            'janji_temu_id' => $appointment->id,
            'tautan' => '/janji-temu/' . $appointment->id,
        ]);
        
        // Buat notifikasi untuk penjual
        $seller->notifications()->create([
            'judul' => 'Janji Temu Baru',
            'isi' => "{$user->name} mengajukan janji temu dengan Anda.",
            'jenis' => 'janji_temu',
            'janji_temu_id' => $appointment->id,
            'tautan' => '/janji-temu/' . $appointment->id,
        ]);
        
        $appointment->load(['seller', 'buyer', 'sellerLocation']);
        
        // Format untuk tampilan
        $appointment->formatted_date = Carbon::parse($appointment->tanggal_janji)->format('d M Y');
        $appointment->formatted_time = Carbon::parse($appointment->tanggal_janji)->format('H:i');
        $appointment->status_text = Appointment::$statuses[$appointment->status] ?? $appointment->status;

        return response()->json([
            'success' => true,
            'message' => 'Janji temu berhasil dibuat dan menunggu konfirmasi',
            'data' => $appointment
        ], 201);
    }

    /**
     * Menampilkan detail janji temu.
     *
     * @param  \App\Models\Appointment  $appointment
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Appointment $appointment, Request $request)
    {
        $user = $request->user();
        
        // Pastikan janji temu terkait dengan pengguna
        if ($appointment->pembeli_id !== $user->id && $appointment->penjual_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $appointment->load(['seller', 'buyer', 'sellerLocation', 'messages']);
        
        // Format untuk tampilan
        $appointment->formatted_date = Carbon::parse($appointment->tanggal_janji)->format('d M Y');
        $appointment->formatted_time = Carbon::parse($appointment->tanggal_janji)->format('H:i');
        $appointment->status_text = Appointment::$statuses[$appointment->status] ?? $appointment->status;

        return response()->json([
            'success' => true,
            'data' => $appointment
        ]);
    }

    /**
     * Memperbarui janji temu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Appointment  $appointment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Appointment $appointment)
    {
        $user = $request->user();
        
        // Hanya pembuat janji temu (pembeli) yang dapat mengubah janji
        if ($appointment->pembeli_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Periksa apakah janji temu masih bisa diubah
        if (in_array($appointment->status, ['selesai', 'dibatalkan'])) {
            return response()->json([
                'success' => false,
                'message' => 'Janji temu tidak dapat diubah'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'tanggal_janji' => 'sometimes|date|after:now',
            'tujuan' => 'nullable|string|max:255',
            'catatan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Jika ada perubahan tanggal, validasi ulang jam operasional
        if ($request->has('tanggal_janji')) {
            $location = $appointment->sellerLocation;
            
            // Verifikasi waktu (jam operasional)
            $appointmentDateTime = Carbon::parse($request->tanggal_janji);
            $appointmentDayName = $appointmentDateTime->translatedFormat('l');
            $appointmentTime = $appointmentDateTime->format('H:i:s');
            
            // Konversi nama hari ke bahasa Indonesia
            $dayMapping = [
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu',
                'Sunday' => 'Minggu',
            ];
            
            $dayName = $dayMapping[$appointmentDayName] ?? $appointmentDayName;
            
            // Cek jam operasional
            $operatingHours = collect($location->jam_operasional ?? []);
            $daySchedule = $operatingHours->firstWhere('hari', $dayName);
            
            if (!$daySchedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi tidak beroperasi pada hari ' . $dayName
                ], 400);
            }
            
            if (isset($daySchedule['jam_buka']) && isset($daySchedule['jam_tutup'])) {
                $openTime = Carbon::parse($daySchedule['jam_buka']);
                $closeTime = Carbon::parse($daySchedule['jam_tutup']);
                $appointmentTimeObj = Carbon::parse($appointmentTime);
                
                if ($appointmentTimeObj->lt($openTime) || $appointmentTimeObj->gt($closeTime)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Waktu di luar jam operasional (' . $daySchedule['jam_buka'] . ' - ' . $daySchedule['jam_tutup'] . ')'
                    ], 400);
                }
            }
            
            // Reset status menjadi menunggu jika tanggal berubah
            $appointment->status = 'menunggu';
        }
        
        // Update data janji temu
        $appointment->fill($request->only(['tanggal_janji', 'tujuan', 'catatan']));
        $appointment->save();
        
        // Buat notifikasi untuk penjual
        $appointment->seller->notifications()->create([
            'judul' => 'Janji Temu Diperbarui',
            'isi' => "{$user->name} telah memperbarui janji temu.",
            'jenis' => 'janji_temu',
            'janji_temu_id' => $appointment->id,
            'tautan' => '/janji-temu/' . $appointment->id,
        ]);
        
        $appointment->load(['seller', 'buyer', 'sellerLocation']);
        
        // Format untuk tampilan
        $appointment->formatted_date = Carbon::parse($appointment->tanggal_janji)->format('d M Y');
        $appointment->formatted_time = Carbon::parse($appointment->tanggal_janji)->format('H:i');
        $appointment->status_text = Appointment::$statuses[$appointment->status] ?? $appointment->status;

        return response()->json([
            'success' => true,
            'message' => 'Janji temu berhasil diperbarui',
            'data' => $appointment
        ]);
    }

    /**
     * Menghapus janji temu.
     *
     * @param  \App\Models\Appointment  $appointment
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Appointment $appointment, Request $request)
    {
        $user = $request->user();
        
        // Hanya pembuat janji temu (pembeli) yang dapat menghapus janji
        if ($appointment->pembeli_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Periksa apakah janji temu masih bisa dihapus (hanya yang masih menunggu)
        if ($appointment->status !== 'menunggu') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya janji temu dengan status menunggu yang dapat dihapus'
            ], 400);
        }
        
        // Simpan data untuk notifikasi
        $sellerId = $appointment->penjual_id;
        $seller = $appointment->seller;
        
        // Hapus janji temu
        $appointment->delete();
        
        // Buat notifikasi untuk penjual
        if ($seller) {
            $seller->notifications()->create([
                'judul' => 'Janji Temu Dibatalkan',
                'isi' => "{$user->name} telah membatalkan janji temu.",
                'jenis' => 'janji_temu',
                'tautan' => '/janji-temu',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Janji temu berhasil dihapus'
        ]);
    }
    
    /**
     * Memperbarui status janji temu.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Appointment  $appointment
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:menunggu,dikonfirmasi,selesai,dibatalkan'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = $request->user();
        $newStatus = $request->status;
        
        // Validasi otorisasi berdasarkan status dan peran
        if ($appointment->penjual_id === $user->id) {
            // Penjual dapat mengubah status menjadi: dikonfirmasi, selesai, dibatalkan
            if (!in_array($newStatus, ['dikonfirmasi', 'selesai', 'dibatalkan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penjual hanya dapat mengubah status menjadi dikonfirmasi, selesai, atau dibatalkan'
                ], 400);
            }
        } elseif ($appointment->pembeli_id === $user->id) {
            // Pembeli hanya dapat mengubah status menjadi: dibatalkan, selesai
            if (!in_array($newStatus, ['dibatalkan', 'selesai'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembeli hanya dapat mengubah status menjadi dibatalkan atau selesai'
                ], 400);
            }
            
            // Pembeli hanya dapat menyelesaikan janji temu yang sudah dikonfirmasi
            if ($newStatus === 'selesai' && $appointment->status !== 'dikonfirmasi') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya janji temu yang sudah dikonfirmasi yang dapat diselesaikan'
                ], 400);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Update status
        $oldStatus = $appointment->status;
        $appointment->updateStatus($newStatus);
        
        // Format untuk tampilan
        $appointment->load(['seller', 'buyer', 'sellerLocation']);
        $appointment->formatted_date = Carbon::parse($appointment->tanggal_janji)->format('d M Y');
        $appointment->formatted_time = Carbon::parse($appointment->tanggal_janji)->format('H:i');
        $appointment->status_text = Appointment::$statuses[$appointment->status] ?? $appointment->status;

        return response()->json([
            'success' => true,
            'message' => 'Status janji temu berhasil diperbarui',
            'data' => $appointment
        ]);
    }
    
    /**
     * Mendapatkan daftar janji temu untuk penjual.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sellerAppointments(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $query = Appointment::with(['buyer', 'sellerLocation'])
                           ->where('penjual_id', $user->id);
        
        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter berdasarkan lokasi
        if ($request->has('lokasi_id')) {
            $query->where('lokasi_penjual_id', $request->lokasi_id);
        }
        
        // Filter berdasarkan tanggal (akan datang atau sudah lewat)
        if ($request->has('upcoming') && $request->boolean('upcoming')) {
            $query->where('tanggal_janji', '>=', Carbon::now())
                  ->whereIn('status', ['menunggu', 'dikonfirmasi']);
        } elseif ($request->has('past') && $request->boolean('past')) {
            $query->where(function($q) {
                $q->where('tanggal_janji', '<', Carbon::now())
                  ->orWhereIn('status', ['selesai', 'dibatalkan']);
            });
        }
        
        // Filter berdasarkan rentang tanggal
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            
            $query->whereBetween('tanggal_janji', [$startDate, $endDate]);
        }
        
        // Pengurutan
        $sortBy = $request->sort_by ?? 'tanggal_janji';
        $sortDir = $request->sort_dir ?? 'asc';
        
        if ($sortBy === 'tanggal_janji') {
            $query->orderBy($sortBy, $sortDir);
        }
        
        // Paginasi
        $perPage = $request->per_page ?? 10;
        $appointments = $query->paginate($perPage);
        
        // Format untuk tampilan
        $appointments->getCollection()->transform(function ($item) {
            $item->formatted_date = Carbon::parse($item->tanggal_janji)->format('d M Y');
            $item->formatted_time = Carbon::parse($item->tanggal_janji)->format('H:i');
            $item->status_text = Appointment::$statuses[$item->status] ?? $item->status;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }
}