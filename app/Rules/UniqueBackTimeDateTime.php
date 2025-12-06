<?php

namespace App\Rules;

use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueBackTimeDateTime implements ValidationRule
{
    private $customerId;

    private $rideDate;

    private $orderId;

    /**
     * 建立新的驗證規則實例
     *
     * @param  int  $customerId  客戶 ID
     * @param  string  $rideDate  乘車日期
     * @param  int|null  $orderId  編輯模式時的訂單 ID（用於排除當前訂單）
     */
    public function __construct($customerId, $rideDate, $orderId = null)
    {
        $this->customerId = $customerId;
        $this->rideDate = $rideDate;
        $this->orderId = $orderId;
    }

    /**
     * 執行驗證規則
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 如果沒有填寫回程時間，不需要驗證
        if (empty($value)) {
            return;
        }

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
            $fail("回程時間與既有訂單的上車時間重複（{$existingOrder->order_number}），請選擇其他時間。");
        }
    }
}
