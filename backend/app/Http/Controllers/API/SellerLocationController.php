<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\SellerLocation;
use App\Models\User;

class SellerLocationController extends Controller
{
    /**
     * Mendapatkan daftar lokasi penjual (publik).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SellerLocation::with('user')
                              ->where('aktif', true);
        
        // Filter berdasarkan jenis penjual
        if ($request->has('jenis_penjual') && in_array($request->jenis_penjual, array_keys(SellerLocation::$sellerTypes))) {
            $query->where('jenis_penjual', $request->jenis_penjual);
        }
        
        // Filter berdasarkan lokasi
        if ($request->has('provinsi')) {
            $query->where('provinsi', 'like', '%' . $request->provinsi . '%');
        }
        
        if ($request->has('kota')) {
            $query->where('kota', 'like', '%' . $request->kota . '%');
        }
        
        if ($request->has('kecamatan')) {
            $query->where('kecamatan', 'like', '%' . $request->kecamatan . '%');
        }
        
        // Pencarian
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('nama_usaha', 'like', '%' . $searchTerm . '%')
                  ->orWhere('deskripsi', 'like', '%' . $searchTerm . '%')
                  ->orWhere('alamat_lengkap', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('user', function($uq) use ($searchTerm) {
                      $uq->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }
        
        // Paginasi
        $perPage = $request->per_page ?? 10;
        $locations = $query->paginate($perPage);
        
        // Format untuk tampilan
        $locations->getCollection()->transform(function ($item) {
            $item->seller_type_text = SellerLocation::$sellerTypes[$item->jenis_penjual] ?? $item->jenis_penjual;
            
            // Pastikan foto_urls adalah array
            $item->foto_urls = [];
            if ($item->foto) {
                foreach ($item->foto as $photo) {
                    $item->foto_urls[] = asset('storage/' . $photo);
                }
            }
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * Menyimpan lokasi penjual baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Pastikan pengguna adalah penjual
        if (!$user->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya penjual yang dapat membuat lokasi usaha'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_usaha' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'alamat_lengkap' => 'required|string',
            'provinsi' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'kode_pos' => 'required|string|max:10',
            'telepon' => 'required|string|max:20',
            'jenis_penjual' => 'required|in:nelayan,pembudidaya,grosir,ritel',
            'jam_operasional' => 'nullable|array',
            'jam_operasional.*.hari' => 'required|string',
            'jam_operasional.*.jam_buka' => 'required|string',
            'jam_operasional.*.jam_tutup' => 'required|string',
            'foto.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'aktif' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Upload foto jika ada
        $photoNames = [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $photo) {
                $photoName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                $photo->storeAs('public/seller_locations', $photoName);
                $photoNames[] = 'seller_locations/' . $photoName;
            }
        }
        
        // Buat lokasi penjual
        $locationData = $request->except(['foto']);
        $locationData['user_id'] = $user->id;
        $locationData['foto'] = $photoNames;
        $locationData['aktif'] = $request->has('aktif') ? $request->aktif : true;
        
        $location = SellerLocation::create($locationData);
        $location->load('user');
        
        // Format untuk tampilan
        $location->seller_type_text = SellerLocation::$sellerTypes[$location->jenis_penjual] ?? $location->jenis_penjual;
        
        // Pastikan foto_urls adalah array
        $location->foto_urls = [];
        if ($location->foto) {
            foreach ($location->foto as $photo) {
                $location->foto_urls[] = asset('storage/' . $photo);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Lokasi penjual berhasil ditambahkan',
            'data' => $location
        ], 201);
    }

    /**
     * Menampilkan detail lokasi penjual.
     *
     * @param  \App\Models\SellerLocation  $sellerLocation
     * @return \Illuminate\Http\Response
     */
    public function show(SellerLocation $sellerLocation)
    {
        // Pastikan lokasi penjual aktif
        if (!$sellerLocation->aktif) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi penjual tidak ditemukan'
            ], 404);
        }
        
        $sellerLocation->load('user');
        
        // Format untuk tampilan
        $sellerLocation->seller_type_text = SellerLocation::$sellerTypes[$sellerLocation->jenis_penjual] ?? $sellerLocation->jenis_penjual;
        
        // Pastikan foto_urls adalah array
        $sellerLocation->foto_urls = [];
        if ($sellerLocation->foto) {
            foreach ($sellerLocation->foto as $photo) {
                $sellerLocation->foto_urls[] = asset('storage/' . $photo);
            }
        }
        
        // Dapatkan produk penjual
        $products = $sellerLocation->user->products()
                                  ->where('aktif', true)
                                  ->where('stok', '>', 0)
                                  ->with('category')
                                  ->take(6)
                                  ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'location' => $sellerLocation,
                'products' => $products
            ]
        ]);
    }

    /**
     * Memperbarui lokasi penjual.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SellerLocation  $sellerLocation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SellerLocation $sellerLocation)
    {
        $user = $request->user();
        
        // Pastikan lokasi penjual milik pengguna
        if ($sellerLocation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_usaha' => 'sometimes|string|max:255',
            'deskripsi' => 'nullable|string',
            'alamat_lengkap' => 'sometimes|string',
            'provinsi' => 'sometimes|string|max:255',
            'kota' => 'sometimes|string|max:255',
            'kecamatan' => 'sometimes|string|max:255',
            'kode_pos' => 'sometimes|string|max:10',
            'telepon' => 'sometimes|string|max:20',
            'jenis_penjual' => 'sometimes|in:nelayan,pembudidaya,grosir,ritel',
            'jam_operasional' => 'nullable|array',
            'jam_operasional.*.hari' => 'required|string',
            'jam_operasional.*.jam_buka' => 'required|string',
            'jam_operasional.*.jam_tutup' => 'required|string',
            'foto_baru.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'hapus_foto' => 'nullable|array',
            'hapus_foto.*' => 'string',
            'aktif' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Perbarui foto jika ada
        $photoNames = $sellerLocation->foto ?? [];
        
        // Hapus foto yang ditandai untuk dihapus
        if ($request->has('hapus_foto') && is_array($request->hapus_foto)) {
            foreach ($request->hapus_foto as $photoToDelete) {
                $key = array_search($photoToDelete, $photoNames);
                if ($key !== false) {
                    Storage::delete('public/' . $photoToDelete);
                    unset($photoNames[$key]);
                }
            }
            $photoNames = array_values($photoNames); // Reindex array
        }
        
        // Tambahkan foto baru
        if ($request->hasFile('foto_baru')) {
            foreach ($request->file('foto_baru') as $photo) {
                $photoName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                $photo->storeAs('public/seller_locations', $photoName);
                $photoNames[] = 'seller_locations/' . $photoName;
            }
        }
        
        // Perbarui data lokasi penjual
        $locationData = $request->except(['foto_baru', 'hapus_foto']);
        $locationData['foto'] = $photoNames;
        
        $sellerLocation->update($locationData);
        $sellerLocation->load('user');
        
        // Format untuk tampilan
        $sellerLocation->seller_type_text = SellerLocation::$sellerTypes[$sellerLocation->jenis_penjual] ?? $sellerLocation->jenis_penjual;
        
        // Pastikan foto_urls adalah array
        $sellerLocation->foto_urls = [];
        if ($sellerLocation->foto) {
            foreach ($sellerLocation->foto as $photo) {
                $sellerLocation->foto_urls[] = asset('storage/' . $photo);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Lokasi penjual berhasil diperbarui',
            'data' => $sellerLocation
        ]);
    }

    /**
     * Menghapus lokasi penjual.
     *
     * @param  \App\Models\SellerLocation  $sellerLocation
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(SellerLocation $sellerLocation, Request $request)
    {
        $user = $request->user();
        
        // Pastikan lokasi penjual milik pengguna
        if ($sellerLocation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        // Cek apakah ada janji temu aktif yang terkait dengan lokasi ini
        $activeAppointments = $sellerLocation->appointments()
                                           ->whereNotIn('status', ['selesai', 'dibatalkan'])
                                           ->exists();
                                           
        if ($activeAppointments) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus lokasi karena masih ada janji temu aktif'
            ], 400);
        }
        
        // Hapus foto
        if ($sellerLocation->foto) {
            foreach ($sellerLocation->foto as $photo) {
                Storage::delete('public/' . $photo);
            }
        }
        
        $sellerLocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lokasi penjual berhasil dihapus'
        ]);
    }
    
    /**
     * Mendapatkan daftar lokasi untuk penjual yang login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sellerLocations(Request $request)
    {
        $user = $request->user();
        
        // Pastikan pengguna adalah penjual
        if (!$user->hasRole('seller')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $locations = SellerLocation::where('user_id', $user->id)
                                 ->get();
        
        // Format untuk tampilan
        $locations->transform(function ($item) {
            $item->seller_type_text = SellerLocation::$sellerTypes[$item->jenis_penjual] ?? $item->jenis_penjual;
            
            // Pastikan foto_urls adalah array
            $item->foto_urls = [];
            if ($item->foto) {
                foreach ($item->foto as $photo) {
                    $item->foto_urls[] = asset('storage/' . $photo);
                }
            }
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }
}