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
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
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
