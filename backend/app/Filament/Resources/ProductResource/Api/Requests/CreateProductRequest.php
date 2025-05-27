<?php

namespace App\Filament\Resources\ProductResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
			'nama' => 'required',
			'slug' => 'required',
			'deskripsi' => 'required|string',
			'harga' => 'required|numeric',
			'stok' => 'required',
			'kategori_id' => 'required',
			'penjual_id' => 'required',
			'gambar' => 'required',
			'berat' => 'required|numeric',
			'jenis_ikan' => 'required',
			'spesies_ikan' => 'required',
			'rating_rata' => 'required|numeric',
			'jumlah_ulasan' => 'required',
			'aktif' => 'required',
			'unggulan' => 'required'
		];
    }
}
