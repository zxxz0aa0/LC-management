<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'type',
        'filename',
        'file_path',
        'total_rows',
        'processed_rows',
        'success_count',
        'error_count',
        'error_messages',
        'status',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'error_messages' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * 計算進度百分比
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows <= 0) {
            return 0.0;
        }

        $processedRows = max(0, $this->processed_rows);
        $percentage = min(100.0, ($processedRows / $this->total_rows) * 100);

        return round($percentage, 2);
    }

    /**
     * 檢查是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * 檢查是否失敗
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * 檢查是否處理中
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * 檢查是否等待中
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * 獲取狀態顯示文字
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'pending' => '等待中',
            'processing' => '處理中',
            'completed' => '已完成',
            'failed' => '失敗',
            default => '未知狀態'
        };
    }

    /**
     * 獲取剩餘行數
     */
    public function getRemainingRowsAttribute(): int
    {
        return max(0, $this->total_rows - $this->processed_rows);
    }

    /**
     * 獲取處理耗時（秒）
     */
    public function getProcessingTimeAttribute(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * 清理舊的會話記錄
     */
    public static function cleanupOldSessions(int $daysOld = 7): int
    {
        return static::where('created_at', '<', now()->subDays($daysOld))
            ->where('status', 'completed')
            ->delete();
    }

    /**
     * 清理失敗的會話記錄
     */
    public static function cleanupFailedSessions(int $hoursOld = 24): int
    {
        return static::where('created_at', '<', now()->subHours($hoursOld))
            ->where('status', 'failed')
            ->delete();
    }
}
