<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 我們使用 Laravel 的 policies 來做授權，這裡先設為 true
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_id_number' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:255',
            'ride_date' => 'required|date',
            'ride_time' => 'required|date_format:H:i',
            'pickup_address' => 'required|string|max:255',
            'dropoff_address' => 'required|string|max:255',
            'status' => 'required|in:open,assigned,replacement,blocked,cancelled',
            // 其他您認為需要驗證的欄位可以加在這裡
        ];
    }
}