@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload mr-2"></i>
                        客戶資料匯入進度
                    </h3>
                </div>
                
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>檔案資訊</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>檔案名稱：</strong></td>
                                    <td>{{ $progress->filename }}</td>
                                </tr>
                                <tr>
                                    <td><strong>總筆數：</strong></td>
                                    <td>{{ number_format($progress->total_rows) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>狀態：</strong></td>
                                    <td>
                                        <span class="badge badge-{{ 
                                            $progress->status === 'completed' ? 'success' : 
                                            ($progress->status === 'failed' ? 'danger' : 
                                            ($progress->status === 'processing' ? 'warning' : 'secondary'))
                                        }}">
                                            {{ 
                                                $progress->status === 'pending' ? '等待中' :
                                                ($progress->status === 'processing' ? '處理中' :
                                                ($progress->status === 'completed' ? '已完成' : '失敗'))
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>時間資訊</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>開始時間：</strong></td>
                                    <td>{{ $progress->started_at ? $progress->started_at->format('Y-m-d H:i:s') : '尚未開始' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>完成時間：</strong></td>
                                    <td>{{ $progress->completed_at ? $progress->completed_at->format('Y-m-d H:i:s') : '處理中' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>批次ID：</strong></td>
                                    <td><code>{{ $progress->batch_id }}</code></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- 進度條 -->
                    <div class="mb-4">
                        <h5>處理進度</h5>
                        <div class="progress mb-2" style="height: 25px;">
                            <div class="progress-bar" id="progress-bar" 
                                 role="progressbar" 
                                 style="width: {{ $progress->progress_percentage }}%"
                                 aria-valuenow="{{ $progress->progress_percentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <span id="progress-text">{{ $progress->progress_percentage }}%</span>
                            </div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-tasks"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">已處理</span>
                                        <span class="info-box-number" id="processed-count">{{ number_format($progress->processed_rows) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">成功</span>
                                        <span class="info-box-number" id="success-count">{{ number_format($progress->success_count) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-danger">
                                    <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">錯誤</span>
                                        <span class="info-box-number" id="error-count">{{ number_format($progress->error_count) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-secondary">
                                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">剩餘</span>
                                        <span class="info-box-number" id="remaining-count">{{ number_format($progress->total_rows - $progress->processed_rows) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 錯誤訊息 -->
                    @if($progress->error_messages && count($progress->error_messages) > 0)
                    <div class="mb-4">
                        <h5>錯誤訊息</h5>
                        <div class="alert alert-warning">
                            <div id="error-messages" style="max-height: 300px; overflow-y: auto;">
                                @foreach($progress->error_messages as $error)
                                    <div class="mb-1">{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 操作按鈕 -->
                    <div class="text-center">
                        @if($progress->status === 'pending')
                            <button type="button" id="start-processing-btn" class="btn btn-success btn-lg">
                                <i class="fas fa-play mr-2"></i>開始處理
                            </button>
                            <button type="button" id="processing-btn" class="btn btn-warning btn-lg" style="display: none;" disabled>
                                <i class="fas fa-spinner fa-spin mr-2"></i>啟動中...
                            </button>
                        @elseif($progress->status === 'processing')
                            <button type="button" class="btn btn-warning btn-lg" disabled>
                                <i class="fas fa-cog fa-spin mr-2"></i>處理中...
                            </button>
                        @elseif($progress->isCompleted())
                            <a href="{{ route('customers.index') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-list mr-2"></i>回到客戶列表
                            </a>
                        @else
                            <button type="button" class="btn btn-secondary" onclick="location.reload()">
                                <i class="fas fa-refresh mr-2"></i>重新整理
                            </button>
                        @endif
                        
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-lg ml-2">
                            <i class="fas fa-arrow-left mr-2"></i>返回客戶管理
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const batchId = '{{ $progress->batch_id }}';
    let polling = true;
    
    // 開始處理按鈕事件
    const startBtn = document.getElementById('start-processing-btn');
    const processingBtn = document.getElementById('processing-btn');
    
    if (startBtn) {
        startBtn.addEventListener('click', function() {
            startQueueProcessing();
        });
    }
    
    function startQueueProcessing() {
        // 顯示載入狀態
        startBtn.style.display = 'none';
        processingBtn.style.display = 'inline-block';
        
        fetch('{{ route("customers.startQueueWorker") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                batch_id: batchId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 成功啟動，顯示訊息
                alert('✅ ' + data.message);
                
                // 開始輪詢更新進度
                polling = true;
                setTimeout(() => {
                    updateProgress();
                }, 2000); // 2秒後開始檢查進度
                
                // 開始定期輪詢
                startPolling();
                
            } else {
                // 失敗，恢復按鈕狀態
                startBtn.style.display = 'inline-block';
                processingBtn.style.display = 'none';
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            console.error('啟動佇列處理失敗:', error);
            startBtn.style.display = 'inline-block';
            processingBtn.style.display = 'none';
            alert('❌ 啟動失敗，請稍後再試');
        });
    }
    
    function startPolling() {
        const interval = setInterval(() => {
            if (polling) {
                updateProgress();
            } else {
                clearInterval(interval);
            }
        }, 3000);
        
        // 頁面離開時停止輪詢
        window.addEventListener('beforeunload', () => {
            polling = false;
        });
    }
    
    function updateProgress() {
        if (!polling) return;
        
        fetch(`/api/customers/import-progress/${batchId}`)
            .then(response => response.json())
            .then(data => {
                // 更新進度條
                const progressBar = document.getElementById('progress-bar');
                const progressText = document.getElementById('progress-text');
                progressBar.style.width = data.progress_percentage + '%';
                progressText.textContent = data.progress_percentage + '%';
                
                // 更新統計數字
                document.getElementById('processed-count').textContent = data.processed_rows.toLocaleString();
                document.getElementById('success-count').textContent = data.success_count.toLocaleString();
                document.getElementById('error-count').textContent = data.error_count.toLocaleString();
                document.getElementById('remaining-count').textContent = (data.total_rows - data.processed_rows).toLocaleString();
                
                // 更新錯誤訊息
                if (data.error_messages && data.error_messages.length > 0) {
                    const errorContainer = document.getElementById('error-messages');
                    if (errorContainer) {
                        errorContainer.innerHTML = data.error_messages.map(error => 
                            `<div class="mb-1">${error}</div>`
                        ).join('');
                    }
                }
                
                // 檢查是否完成
                if (data.status === 'completed' || data.status === 'failed') {
                    polling = false;
                    if (data.status === 'completed') {
                        progressBar.classList.remove('progress-bar-animated');
                        progressBar.classList.add('bg-success');
                        
                        // 顯示完成訊息
                        const successMsg = `匯入完成！成功 ${data.success_count} 筆，錯誤 ${data.error_count} 筆。`;
                        
                        // 可以選擇顯示通知或重新載入頁面
                        setTimeout(() => {
                            if (confirm(successMsg + ' 是否要返回客戶列表？')) {
                                window.location.href = '{{ route("customers.index") }}';
                            }
                        }, 1000);
                    }
                }
            })
            .catch(error => {
                console.error('更新進度失敗:', error);
            });
    }
    
    // 只有在處理中才進行輪詢
    @if($progress->status === 'processing')
        // 如果已經在處理中，立即開始輪詢
        startPolling();
    @endif
});
</script>
@endsection