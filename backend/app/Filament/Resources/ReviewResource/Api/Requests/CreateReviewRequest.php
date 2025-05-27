<?php

namespace App\Filament\Resources\ReviewResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateReviewRequest extends FormRequest
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
			'produk_id' => 'required',
			'item_pesanan_id' => 'required',
			'rating' => 'required',
			'komentar' => 'required|string',
			'gambar' => 'required'
		];
    }
}
