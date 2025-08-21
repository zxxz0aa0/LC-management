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

    private $backTime;

    public function __construct($customerId, $rideDate, $backTime = null, $orderId = null)
    {
        $this->customerId = $customerId;
        $this->rideDate = $rideDate;
        $this->backTime = $backTime; // 回程時間，用於判斷是否為往返訂單
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
        $existingOrder = $query->first();

        if ($existingOrder) {
            // 如果有回程時間，並且當前驗證的時間與回程時間相同，則允許（這是往返訂單的第二筆）
            $isReturnOrderTime = ! empty($this->backTime) && $value === $this->backTime;

            if (! $isReturnOrderTime) {
                $fail("該客戶在此日期時間已有訂單（{$existingOrder->order_number}），請選擇其他時間或檢查是否重複建立。");
            }
        }
    }
}
