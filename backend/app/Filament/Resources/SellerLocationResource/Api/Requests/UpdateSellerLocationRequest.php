<?php

namespace App\Filament\Resources\SellerLocationResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSellerLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'user_id' => 'required',
			'nama_usaha' => 'required',
			'deskripsi' => 'required|string',
			'alamat_lengkap' => 'required|string',
			'provinsi' => 'required',
			'kota' => 'required',
			'kecamatan' => 'required',
			'kode_pos' => 'required',
			'aktif' => 'required',
			'jam_operasional' => 'required',
			'telepon' => 'required',
			'foto' => 'required',
			'jenis_penjual' => 'required'
		];
    }
}
