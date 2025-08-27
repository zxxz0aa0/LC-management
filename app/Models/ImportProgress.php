<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportProgress extends Model
{
    use HasFactory;

    protected $table = 'import_progresses';

    protected $fillable = [
        'batch_id',
        'type',
        'filename',
        'total_rows',
        'processed_rows',
        'success_count',
        'error_count',
        'status',
        'error_messages',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'error_messages' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getProgressPercentageAttribute()
    {
        // 處理總行數為0或負數的情況
        if ($this->total_rows <= 0) {
            return 0;
        }

        // 處理已處理行數為負數的情況
        $processedRows = max(0, $this->processed_rows);
        
        // 確保進度不超過100%
        $percentage = min(100, ($processedRows / $this->total_rows) * 100);
        
        return round($percentage, 2);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }
}
