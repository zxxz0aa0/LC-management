@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title">
                        <i class="fas fa-upload me-2"></i>
                        訂單資料匯入進度
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
                                    <td><strong>匯入類型：</strong></td>
                                    <td>
                                        <span class="badge bg-primary">訂單資料</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>狀態：</strong></td>
                                    <td>
                                        <span class="badge bg-{{ 
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
                            <div class="progress-bar progress-bar-striped {{ $progress->status === 'processing' ? 'progress-bar-animated' : '' }}" 
                                 id="progress-bar" 
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
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <i class="fas fa-tasks fa-2x mb-2"></i>
                                        <h6>已處理</h6>
                                        <h4 id="processed-count">{{ number_format($progress->processed_rows) }}</h4>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <i class="fas fa-check fa-2x mb-2"></i>
                                        <h6>成功</h6>
                                        <h4 id="success-count">{{ number_format($progress->success_count) }}</h4>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <i class="fas fa-times fa-2x mb-2"></i>
                                        <h6>錯誤</h6>
                                        <h4 id="error-count">{{ number_format($progress->error_count) }}</h4>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="card bg-secondary text-white">
                                    <div class="card-body">
                                        <i class="fas fa-clock fa-2x mb-2"></i>
                                        <h6>剩餘</h6>
                                        <h4 id="remaining-count">{{ number_format($progress->total_rows - $progress->processed_rows) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 錯誤訊息 -->
                    @if($progress->error_messages && count($progress->error_messages) > 0)
                    <div class="mb-4">
                        <h5>錯誤訊息 
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#errorMessages" aria-expanded="false">
                                <i class="fas fa-eye"></i> 顯示/隱藏
                            </button>
                        </h5>
                        <div class="collapse" id="errorMessages">
                            <div class="alert alert-warning">
                                <div id="error-messages" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($progress->error_messages as $error)
                                        <div class="mb-1"><i class="fas fa-exclamation-triangle text-warning me-2"></i>{{ $error }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- 操作說明 -->
                    @if($progress->status === 'pending')
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>注意事項：</h6>
                        <ul class="mb-0">
                            <li>訂單匯入包含複雜的地址解析和客戶查找，處理時間較長</li>
                            <li>系統會自動檢測檔案格式（完整格式或簡化格式）</li>
                            <li>重複的訂單編號將被跳過，不會覆蓋現有訂單</li>
                            <li>處理期間請勿關閉瀏覽器，頁面會自動更新進度</li>
                        </ul>
                    </div>
                    @endif

                    <!-- 操作按鈕 -->
                    <div class="text-center">
                        @if($progress->status === 'pending')
                            <button type="button" id="start-processing-btn" class="btn btn-success btn-lg">
                                <i class="fas fa-play me-2"></i>開始處理
                            </button>
                            <button type="button" id="processing-btn" class="btn btn-warning btn-lg" style="display: none;" disabled>
                                <i class="fas fa-spinner fa-spin me-2"></i>啟動中...
                            </button>
                        @elseif($progress->status === 'processing')
                            <button type="button" class="btn btn-warning btn-lg" disabled>
                                <i class="fas fa-cog fa-spin me-2"></i>處理中...
                            </button>
                            <small class="d-block text-muted mt-2">正在執行訂單資料匯入，請稍候...</small>
                        @elseif($progress->status === 'completed')
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>匯入完成！</h5>
                                <p class="mb-0">成功匯入 <strong>{{ number_format($progress->success_count) }}</strong> 筆訂單，失敗 <strong>{{ number_format($progress->error_count) }}</strong> 筆。</p>
                            </div>
                            <a href="{{ route('orders.index') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-list me-2"></i>查看訂單列表
                            </a>
                        @elseif($progress->status === 'failed')
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-times-circle me-2"></i>匯入失敗</h5>
                                <p class="mb-0">匯入過程中發生錯誤，請檢查錯誤訊息或聯繫系統管理員。</p>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="location.reload()">
                                <i class="fas fa-refresh me-2"></i>重新整理
                            </button>
                        @endif
                        
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-arrow-left me-2"></i>返回訂單管理
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
    let retryCount = 0;
    let maxRetries = 5;
    let baseDelay = 3000; // 基礎延遲 3 秒
    let networkErrorCount = 0;
    
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
        
        fetch('{{ route("orders.startQueueWorker") }}', {
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
                showAlert('success', data.message);
                
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
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('啟動佇列處理失敗:', error);
            startBtn.style.display = 'inline-block';
            processingBtn.style.display = 'none';
            showAlert('error', '啟動失敗，請稍後再試');
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
        
        fetch(`/api/orders/import-progress/${batchId}`)
            .then(response => response.json())
            .then(data => {
                // 成功獲取資料，重置重試計數
                retryCount = 0;
                networkErrorCount = 0;
                
                // 更新進度條
                updateProgressBar(data);
                
                // 更新統計數字
                updateStatistics(data);
                
                // 更新錯誤訊息
                updateErrorMessages(data);
                
                // 檢查是否完成
                if (data.status === 'completed' || data.status === 'failed') {
                    handleCompletion(data);
                }
            })
            .catch(error => {
                handleNetworkError(error);
            });
    }
    
    function updateProgressBar(data) {
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        
        if (progressBar && progressText) {
            const percentage = Math.min(100, Math.max(0, data.progress_percentage || 0));
            progressBar.style.width = percentage + '%';
            progressText.textContent = percentage.toFixed(1) + '%';
        }
    }
    
    function updateStatistics(data) {
        const processedCountEl = document.getElementById('processed-count');
        const successCountEl = document.getElementById('success-count');
        const errorCountEl = document.getElementById('error-count');
        const remainingCountEl = document.getElementById('remaining-count');
        
        if (processedCountEl) processedCountEl.textContent = (data.processed_rows || 0).toLocaleString();
        if (successCountEl) successCountEl.textContent = (data.success_count || 0).toLocaleString();
        if (errorCountEl) errorCountEl.textContent = (data.error_count || 0).toLocaleString();
        if (remainingCountEl) {
            const remaining = Math.max(0, (data.total_rows || 0) - (data.processed_rows || 0));
            remainingCountEl.textContent = remaining.toLocaleString();
        }
    }
    
    function updateErrorMessages(data) {
        if (data.error_messages && data.error_messages.length > 0) {
            const errorContainer = document.getElementById('error-messages');
            if (errorContainer) {
                errorContainer.innerHTML = data.error_messages.map(error => 
                    `<div class="mb-1"><i class="fas fa-exclamation-triangle text-warning me-2"></i>${error}</div>`
                ).join('');
            }
        }
    }
    
    function handleCompletion(data) {
        polling = false;
        
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        
        if (data.status === 'completed') {
            if (progressBar) {
                progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                progressBar.classList.add('bg-success');
                progressBar.style.width = '100%';
            }
            if (progressText) {
                progressText.textContent = '100%';
            }
            
            const successMsg = `訂單匯入完成！成功 ${data.success_count} 筆，錯誤 ${data.error_count} 筆。`;
            showAlert('success', successMsg);
            
            // 延遲後詢問是否返回列表
            setTimeout(() => {
                if (confirm(successMsg + ' 是否要查看訂單列表？')) {
                    window.location.href = '{{ route("orders.index") }}';
                }
            }, 2000);
            
        } else if (data.status === 'failed') {
            if (progressBar) {
                progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                progressBar.classList.add('bg-danger');
            }
            
            showAlert('error', '訂單匯入失敗！請檢查錯誤訊息或聯繫系統管理員。');
        }
        
        // 重新載入頁面以顯示最終狀態
        setTimeout(() => {
            location.reload();
        }, 3000);
    }
    
    function handleNetworkError(error) {
        networkErrorCount++;
        console.error('獲取進度失敗:', error);
        
        if (networkErrorCount < maxRetries) {
            const delay = baseDelay * Math.pow(2, retryCount);
            console.log(`網路錯誤，${delay/1000}秒後重試 (${networkErrorCount}/${maxRetries})`);
            
            setTimeout(() => {
                retryCount++;
                updateProgress();
            }, delay);
        } else {
            polling = false;
            console.error('網路連線多次失敗，停止輪詢');
            showAlert('warning', '網路連線問題：無法獲取最新進度。請重新整理頁面或聯繫系統管理員。');
        }
    }
    
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 'alert-warning';
        const icon = type === 'success' ? 'fas fa-check-circle' : 
                    type === 'error' ? 'fas fa-times-circle' : 'fas fa-exclamation-triangle';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show mt-3`;
        alertDiv.innerHTML = `
            <i class="${icon} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.card-body').appendChild(alertDiv);
        
        // 自動移除警告訊息
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    // 只有在處理中才進行輪詢
    @if($progress->status === 'processing')
        // 如果已經在處理中，立即開始輪詢
        startPolling();
    @endif
});
</script>
@endsection