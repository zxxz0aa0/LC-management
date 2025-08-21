<?php

namespace App\Services;

use App\Exceptions\ConcurrencyException;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderNumberService
{
    /**
     * 類型代碼映射
     */
    private const TYPE_CODE_MAP = [
        '新北長照' => 'NTPC',
        '台北長照' => 'TPC',
        '新北復康' => 'NTFK',
        '愛接送' => 'LT',
    ];

    /**
     * 生成原子化訂單編號
     *
     * @param  string  $orderType  訂單類型
     * @param  string  $customerIdNumber  客戶身分證字號
     * @return string 訂單編號
     */
    public function generateOrderNumber($orderType, $customerIdNumber)
    {
        $maxRetries = 3;
        $retryCount = 0;

        while ($retryCount < $maxRetries) {
            try {
                $today = Carbon::now();
                $dateKey = $today->format('Ymd');
                $typeCode = self::TYPE_CODE_MAP[$orderType] ?? 'UNK';
                $idSuffix = substr($customerIdNumber, -3);
                $time = $today->format('Hi');

                // 使用資料庫鎖獲取原子化序列號
                $sequenceNumber = $this->getNextSequenceNumber($dateKey);

                // 格式化序列號為4位數
                $serial = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);

                // 組合最終編號
                $orderNumber = $typeCode.$idSuffix.$dateKey.$time.$serial;

                Log::info('生成訂單編號', [
                    'order_number' => $orderNumber,
                    'order_type' => $orderType,
                    'date_key' => $dateKey,
                    'sequence_number' => $sequenceNumber,
                    'retry_count' => $retryCount,
                ]);

                return $orderNumber;

            } catch (QueryException $e) {
                $retryCount++;

                // 如果是死鎖或鎖等待超時，重試
                if ($this->isRetryableError($e) && $retryCount < $maxRetries) {
                    Log::warning('訂單編號生成衝突，正在重試', [
                        'retry_count' => $retryCount,
                        'error' => $e->getMessage(),
                        'order_type' => $orderType,
                    ]);

                    // 短暫延遲後重試
                    usleep(100000 * $retryCount); // 100ms, 200ms, 300ms

                    continue;
                }

                // 超過重試次數或不可重試的錯誤
                Log::error('訂單編號生成失敗', [
                    'order_type' => $orderType,
                    'customer_id_number' => $customerIdNumber,
                    'retry_count' => $retryCount,
                    'error' => $e->getMessage(),
                ]);

                throw new ConcurrencyException(
                    ConcurrencyException::ORDER_NUMBER_CONFLICT,
                    ['order_type' => $orderType, 'retry_count' => $retryCount],
                    '訂單編號生成失敗，請重試',
                    0,
                    $e
                );
            }
        }

        throw new ConcurrencyException(
            ConcurrencyException::ORDER_NUMBER_CONFLICT,
            ['order_type' => $orderType, 'max_retries_exceeded' => true]
        );
    }

    /**
     * 獲取下一個序列號（原子化操作）
     *
     * @param  string  $dateKey  日期鍵值
     * @return int 序列號
     */
    private function getNextSequenceNumber($dateKey)
    {
        return DB::transaction(function () use ($dateKey) {
            // 使用 SELECT FOR UPDATE 鎖定行，確保原子性
            $sequence = DB::table('order_sequences')
                ->where('date_key', $dateKey)
                ->lockForUpdate()
                ->first();

            if ($sequence) {
                // 更新現有序列
                $newSequenceNumber = $sequence->sequence_number + 1;

                DB::table('order_sequences')
                    ->where('date_key', $dateKey)
                    ->update([
                        'sequence_number' => $newSequenceNumber,
                        'updated_at' => now(),
                    ]);

                return $newSequenceNumber;
            } else {
                // 創建新的日期序列
                DB::table('order_sequences')->insert([
                    'date_key' => $dateKey,
                    'sequence_number' => 1,
                    'updated_at' => now(),
                ]);

                return 1;
            }
        });
    }

    /**
     * 生成共乘訂單編號組
     *
     * @param  string  $orderType  訂單類型
     * @param  string  $mainCustomerIdNumber  主客戶身分證字號
     * @param  string  $carpoolCustomerIdNumber  共乘客戶身分證字號
     * @param  bool  $hasReturn  是否有回程
     * @return array 編號組
     */
    public function generateCarpoolOrderNumbers($orderType, $mainCustomerIdNumber, $carpoolCustomerIdNumber, $hasReturn = false)
    {
        $today = Carbon::now();
        $dateKey = $today->format('Ymd');
        $typeCode = self::TYPE_CODE_MAP[$orderType] ?? 'UNK';
        $mainIdSuffix = substr($mainCustomerIdNumber, -3);
        $time = $today->format('Hi');

        // 計算需要的序列號數量
        $requiredNumbers = $hasReturn ? 4 : 2; // 去程2個，回程2個

        // 批量獲取序列號
        $sequenceNumbers = $this->getMultipleSequenceNumbers($dateKey, $requiredNumbers);

        $orderNumbers = [];

        // 主訂單編號
        $mainSerial = str_pad($sequenceNumbers[0], 4, '0', STR_PAD_LEFT);
        $mainOrderNumber = $typeCode.$mainIdSuffix.$dateKey.$time.$mainSerial;
        $orderNumbers['main'] = $mainOrderNumber;

        // 共乘成員編號（主訂單編號 + M2後綴）
        $orderNumbers['carpool'] = $mainOrderNumber.'-M2';

        // 如果有回程
        if ($hasReturn) {
            $returnSerial = str_pad($sequenceNumbers[1], 4, '0', STR_PAD_LEFT);
            $returnMainNumber = $typeCode.$mainIdSuffix.$dateKey.$time.$returnSerial;
            $orderNumbers['return_main'] = $returnMainNumber;
            $orderNumbers['return_carpool'] = $returnMainNumber.'-M2';
        }

        Log::info('生成共乘訂單編號組', [
            'order_numbers' => $orderNumbers,
            'date_key' => $dateKey,
            'sequence_numbers' => $sequenceNumbers,
            'has_return' => $hasReturn,
        ]);

        return $orderNumbers;
    }

    /**
     * 批量獲取多個序列號（原子化操作）
     *
     * @param  string  $dateKey  日期鍵值
     * @param  int  $count  需要的數量
     * @return array 序列號陣列
     */
    private function getMultipleSequenceNumbers($dateKey, $count)
    {
        return DB::transaction(function () use ($dateKey, $count) {
            // 使用 SELECT FOR UPDATE 鎖定行
            $sequence = DB::table('order_sequences')
                ->where('date_key', $dateKey)
                ->lockForUpdate()
                ->first();

            $startNumber = 1;

            if ($sequence) {
                $startNumber = $sequence->sequence_number + 1;
                $newSequenceNumber = $sequence->sequence_number + $count;

                DB::table('order_sequences')
                    ->where('date_key', $dateKey)
                    ->update([
                        'sequence_number' => $newSequenceNumber,
                        'updated_at' => now(),
                    ]);
            } else {
                // 創建新的日期序列
                DB::table('order_sequences')->insert([
                    'date_key' => $dateKey,
                    'sequence_number' => $count,
                    'updated_at' => now(),
                ]);
            }

            // 返回序列號陣列
            $numbers = [];
            for ($i = 0; $i < $count; $i++) {
                $numbers[] = $startNumber + $i;
            }

            return $numbers;
        });
    }

    /**
     * 檢查是否為可重試的資料庫錯誤
     */
    private function isRetryableError(QueryException $e): bool
    {
        $errorCode = $e->errorInfo[1] ?? null;

        // MySQL 錯誤代碼：
        // 1213: Deadlock found when trying to get lock
        // 1205: Lock wait timeout exceeded
        // 1062: Duplicate entry (可能是併發時的唯一約束衝突)
        return in_array($errorCode, [1213, 1205, 1062]);
    }

    /**
     * 獲取今日已用序列號數量
     *
     * @return int 序列號數量
     */
    public function getTodaySequenceCount()
    {
        $dateKey = Carbon::now()->format('Ymd');

        $sequence = DB::table('order_sequences')
            ->where('date_key', $dateKey)
            ->first();

        return $sequence ? $sequence->sequence_number : 0;
    }

    /**
     * 重置特定日期的序列號（管理功能，謹慎使用）
     *
     * @param  string|null  $dateKey  日期鍵值，null為今天
     * @return bool 重置結果
     */
    public function resetSequenceNumber($dateKey = null)
    {
        $dateKey = $dateKey ?? Carbon::now()->format('Ymd');

        try {
            DB::table('order_sequences')
                ->where('date_key', $dateKey)
                ->update(['sequence_number' => 0]);

            Log::warning('序列號已重置', ['date_key' => $dateKey]);

            return true;
        } catch (\Exception $e) {
            Log::error('序列號重置失敗', [
                'date_key' => $dateKey,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
