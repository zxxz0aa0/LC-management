<?php

namespace App\Exceptions;

use Exception;

class ConcurrencyException extends Exception
{
    /**
     * 併發衝突類型
     */
    const ORDER_NUMBER_CONFLICT = 'order_number_conflict';
    const GROUP_ID_CONFLICT = 'group_id_conflict';
    const DUPLICATE_ORDER_CONFLICT = 'duplicate_order_conflict';
    
    private string $conflictType;
    private array $conflictData;
    
    public function __construct(string $conflictType, array $conflictData = [], string $message = '', int $code = 0, Exception $previous = null)
    {
        $this->conflictType = $conflictType;
        $this->conflictData = $conflictData;
        
        if (empty($message)) {
            $message = $this->getDefaultMessage($conflictType);
        }
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * 取得衝突類型
     */
    public function getConflictType(): string
    {
        return $this->conflictType;
    }
    
    /**
     * 取得衝突資料
     */
    public function getConflictData(): array
    {
        return $this->conflictData;
    }
    
    /**
     * 取得預設錯誤訊息
     */
    private function getDefaultMessage(string $conflictType): string
    {
        return match($conflictType) {
            self::ORDER_NUMBER_CONFLICT => '訂單編號產生衝突，請重試',
            self::GROUP_ID_CONFLICT => '群組ID產生衝突，請重試',
            self::DUPLICATE_ORDER_CONFLICT => '重複訂單檢測衝突，請重試',
            default => '併發操作衝突，請重試'
        };
    }
    
    /**
     * 將異常轉換為使用者友善的訊息
     */
    public function toUserMessage(): string
    {
        return match($this->conflictType) {
            self::ORDER_NUMBER_CONFLICT => '系統繁忙，請稍後重試建立訂單',
            self::GROUP_ID_CONFLICT => '系統繁忙，請稍後重試建立共乘群組',
            self::DUPLICATE_ORDER_CONFLICT => '系統正在檢查重複訂單，請稍後重試',
            default => '系統繁忙，請稍後重試'
        };
    }
    
    /**
     * 是否建議自動重試
     */
    public function shouldRetry(): bool
    {
        return in_array($this->conflictType, [
            self::ORDER_NUMBER_CONFLICT,
            self::GROUP_ID_CONFLICT
        ]);
    }
}
