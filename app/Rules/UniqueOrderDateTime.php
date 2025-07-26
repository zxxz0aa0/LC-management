<?php

namespace App\Rules;

use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueOrderDateTime implements ValidationRule
{
    private $customerId;
    private $rideDate;
    private $orderId;

    public function __construct($customerId, $rideDate, $orderId = null)
    {
        $this->customerId = $customerId;
        $this->rideDate = $rideDate;
        $this->orderId = $orderId; // 編輯時排除當前訂單
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Order::where('customer_id', $this->customerId)
            ->where('ride_date', $this->rideDate)
            ->where('ride_time', $value);

        // 編輯模式時排除當前訂單
        if ($this->orderId) {
            $query->where('id', '!=', $this->orderId);
        }

        // 檢查是否存在相同時間的訂單
        if ($query->exists()) {
            $fail('該客戶在此日期時間已有訂單，請選擇其他時間或檢查是否重複建立。');
        }
    }
}
