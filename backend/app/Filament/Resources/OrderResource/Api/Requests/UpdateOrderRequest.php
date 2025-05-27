<?php

namespace App\Filament\Resources\OrderResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
			'nomor_pesanan' => 'required',
			'status' => 'required',
			'metode_pembayaran' => 'required',
			'status_pembayaran' => 'required',
			'id_pembayaran' => 'required',
			'alamat_id' => 'required',
			'metode_pengiriman' => 'required',
			'biaya_kirim' => 'required|numeric',
			'subtotal' => 'required|numeric',
			'pajak' => 'required|numeric',
			'total' => 'required|numeric',
			'catatan' => 'required|string'
		];
    }
}
