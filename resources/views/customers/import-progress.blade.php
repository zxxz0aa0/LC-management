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
                    <!-- 基本資訊 -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>檔案資訊</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>檔案名稱：</strong></td>
                                    <td>{{ $session->filename }}</td>
                                </tr>
                                <tr>
                                    <td><strong>總筆數：</strong></td>
                                    <td>{{ number_format($session->total_rows) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>狀態：</strong></td>
                                    <td>
                                        <span class="badge badge-{{ 
                                            $session->status === 'completed' ? 'success' : 
                                            ($session->status === 'failed' ? 'danger' : 
                                            ($session->status === 'processing' ? 'warning' : 'secondary'))
                                        }}">
                                            {{ $session->status_text }}
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
                                    <td>{{ $session->started_at ? $session->started_at->format('Y-m-d H:i:s') : '尚未開始' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>完成時間：</strong></td>
                                    <td>{{ $session->completed_at ? $session->completed_at->format('Y-m-d H:i:s') : '處理中' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>處理時間：</strong></td>
                                    <td id="processing-time">{{ $session->processing_time ? $session->processing_time . ' 秒' : '計算中' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>會話ID：</strong></td>
                                    <td><code>{{ $session->session_id }}</code></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- 狀態指示器 -->
                    @if($session->status === 'processing')
                    <div class="mb-3" id="status-indicator">
                        <div class="alert alert-info">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            <strong>正在處理中...</strong> 
                            <span id="processing-message">系統正在匯入您的資料，請稍候</span>
                        </div>
                    </div>
                    @endif

                    <!-- 進度條 -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5>處理進度</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="updateProgress()">
                                <i class="fas fa-sync me-1"></i>手動刷新
                            </button>
                        </div>
                        
                        <div class="progress mb-2" style="height: 25px;">
                            <div class="progress-bar{{ $session->status === 'completed' ? ' bg-success' : ($session->status === 'failed' ? ' bg-danger' : '') }}" 
                                 id="progress-bar" 
                                 role="progressbar" 
                                 style="width: {{ $session->progress_percentage }}%"
                                 aria-valuenow="{{ $session->progress_percentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <span id="progress-text">{{ $session->progress_percentage }}%</span>
                            </div>
                        </div>
                        
                        <!-- 統計卡片 -->
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="info-box bg-info">
                                    <span class="info-box-icon"><i class="fas fa-tasks"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">已處理</span>
                                        <span class="info-box-number" id="processed-count">{{ number_format($session->processed_rows) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">成功</span>
                                        <span class="info-box-number" id="success-count">{{ number_format($session->success_count) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-danger">
                                    <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">錯誤</span>
                                        <span class="info-box-number" id="error-count">{{ number_format($session->error_count) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="info-box bg-secondary">
                                    <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">剩餘</span>
                                        <span class="info-box-number" id="remaining-count">{{ number_format($session->remaining_rows) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 錯誤訊息 -->
                    @if($session->error_messages && count($session->error_messages) > 0)
                    <div class="mb-4">
                        <h5>錯誤訊息</h5>
                        <div class="alert alert-warning">
                            <div id="error-messages" style="max-height: 300px; overflow-y: auto;">
                                @foreach($session->error_messages as $error)
                                    <div class="mb-1">{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 操作按鈕 -->
                    <div class="text-center">
                        @if($session->status === 'pending')
                            <div class="alert alert-info">
                                <i class="fas fa-clock me-2"></i>
                                <strong>等待處理</strong> - 系統正在準備您的匯入任務...
                            </div>
                        @elseif($session->status === 'processing')
                            <button type="button" class="btn btn-warning btn-lg" disabled>
                                <i class="fas fa-cog fa-spin me-2"></i>處理中...
                            </button>
                        @elseif($session->isCompleted())
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>匯入完成！</strong> 
                                成功：{{ $session->success_count }} 筆，錯誤：{{ $session->error_count }} 筆
                            </div>
                            <a href="{{ route('customers.index') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-list me-2"></i>回到客戶列表
                            </a>
                        @elseif($session->isFailed())
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>匯入失敗</strong> - 請檢查錯誤訊息或重新上傳檔案
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="location.reload()">
                                <i class="fas fa-refresh me-2"></i>重新整理
                            </button>
                        @endif
                        
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary btn-lg ml-2">
                            <i class="fas fa-arrow-left me-2"></i>返回客戶管理
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionId = '{{ $session->session_id }}';
    let polling = false;
    let retryCount = 0;
    const maxRetries = 5;
    const baseDelay = 3000;
    
    // 自動更新進度函數
    function updateProgress() {
        if (!polling && ['processing', 'pending'].includes('{{ $session->status }}')) {
            polling = true;
            fetchProgress();
        }
    }
    
    function fetchProgress() {
        fetch(`/api/customers/import-progress/${sessionId}`)
            .then(response => response.json())
            .then(data => {
                retryCount = 0;
                
                // 更新進度條
                const progressBar = document.getElementById('progress-bar');
                const progressText = document.getElementById('progress-text');
                if (progressBar && progressText) {
                    let percentage = parseFloat(data.progress_percentage || 0);
                    percentage = Math.min(100, Math.max(0, percentage));
                    
                    progressBar.style.width = percentage + '%';
                    progressText.textContent = percentage.toFixed(1) + '%';
                    
                    // 狀態樣式
                    progressBar.className = 'progress-bar';
                    if (data.status === 'completed') {
                        progressBar.classList.add('bg-success');
                    } else if (data.status === 'failed') {
                        progressBar.classList.add('bg-danger');
                    }
                }
                
                // 更新統計數字
                const elements = {
                    'processed-count': data.processed_rows || 0,
                    'success-count': data.success_count || 0,
                    'error-count': data.error_count || 0,
                    'remaining-count': data.remaining_rows || 0
                };
                
                Object.entries(elements).forEach(([id, value]) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.textContent = parseInt(value).toLocaleString();
                    }
                });
                
                // 更新處理時間
                const processingTimeEl = document.getElementById('processing-time');
                if (processingTimeEl && data.processing_time) {
                    processingTimeEl.textContent = data.processing_time + ' 秒';
                }
                
                // 更新狀態指示器
                const statusIndicator = document.getElementById('status-indicator');
                const processingMessage = document.getElementById('processing-message');
                
                if (data.status === 'processing' && statusIndicator) {
                    statusIndicator.style.display = 'block';
                    if (processingMessage) {
                        const processed = parseInt(data.processed_rows || 0);
                        const total = parseInt(data.total_rows || 1);
                        const percent = total > 0 ? Math.round((processed / total) * 100) : 0;
                        processingMessage.textContent = `已處理 ${percent}% (${processed.toLocaleString()}/${total.toLocaleString()} 筆)`;
                    }
                } else if (statusIndicator) {
                    statusIndicator.style.display = 'none';
                }
                
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
                        setTimeout(() => {
                            if (confirm(`匯入完成！成功 ${data.success_count} 筆，錯誤 ${data.error_count} 筆。是否要返回客戶列表？`)) {
                                window.location.href = '{{ route("customers.index") }}';
                            }
                        }, 2000);
                    }
                } else {
                    // 繼續輪詢
                    setTimeout(fetchProgress, 3000);
                }
                
            })
            .catch(error => {
                console.error('獲取進度失敗:', error);
                retryCount++;
                
                if (retryCount < maxRetries) {
                    const delay = baseDelay * Math.pow(2, retryCount);
                    setTimeout(fetchProgress, delay);
                } else {
                    polling = false;
                    console.error('網路連線多次失敗，停止輪詢');
                }
            });
    }
    
    // 初始化
    if ('{{ $session->status }}' === 'pending') {
        console.log('啟動匯入處理');
        // 先啟動匯入處理
        startImportProcess();
    } else if (['processing', 'pending'].includes('{{ $session->status }}')) {
        console.log('開始輪詢進度更新');
        updateProgress();
    }
    
    // 啟動匯入處理
    function startImportProcess() {
        fetch(`/api/customers/start-import/{{ $session->session_id }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('匯入已啟動:', data);
            // 啟動後立即開始輪詢進度
            setTimeout(() => {
                updateProgress();
            }, 2000); // 等待 2 秒後開始輪詢，讓匯入有時間開始
        })
        .catch(error => {
            console.error('啟動匯入失敗:', error);
            // 即使啟動失敗，也嘗試輪詢進度（可能匯入已經在其他地方啟動）
            setTimeout(() => {
                updateProgress();
            }, 3000);
        });
    }
    
    // 頁面離開時停止輪詢
    window.addEventListener('beforeunload', () => {
        polling = false;
    });
});
</script>
@endsection