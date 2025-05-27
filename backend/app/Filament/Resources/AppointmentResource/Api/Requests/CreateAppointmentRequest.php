<?php

namespace App\Filament\Resources\AppointmentResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAppointmentRequest extends FormRequest
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
			'penjual_id' => 'required',
			'pembeli_id' => 'required',
			'lokasi_penjual_id' => 'required',
			'tanggal_janji' => 'required',
			'status' => 'required',
			'tujuan' => 'required',
			'catatan' => 'required|string'
		];
    }
}
