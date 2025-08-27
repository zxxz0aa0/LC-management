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
                    <div class="mb-4" id="error-container" style="{{ ($session->error_messages && count($session->error_messages) > 0) ? '' : 'display: none;' }}">
                        <h5>錯誤訊息</h5>
                        <div class="alert alert-warning">
                            <div id="error-messages" style="max-height: 300px; overflow-y: auto;">
                                @foreach($session->error_messages ?? [] as $error)
                                    <div class="mb-1">{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>


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
    let consecutiveFailures = 0;
    const maxRetries = 20; // 增加最大重試次數
    const maxConsecutiveFailures = 5;
    const baseDelay = 2000; // 基礎延遲時間
    const maxDelay = 30000; // 最大延遲 30 秒
    
    // 顯示用戶友善的錯誤訊息
    function showUserMessage(message, type = 'info') {
        const messageContainer = document.getElementById('user-messages');
        if (!messageContainer) {
            // 創建訊息容器
            const container = document.createElement('div');
            container.id = 'user-messages';
            container.className = 'mb-3';
            document.querySelector('.card-body').insertBefore(container, document.querySelector('.row.mb-4'));
        }
        
        const messageEl = document.createElement('div');
        messageEl.className = `alert alert-${type} alert-dismissible fade show`;
        messageEl.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.getElementById('user-messages').appendChild(messageEl);
        
        // 自動移除訊息（除非是錯誤訊息）
        if (type !== 'danger') {
            setTimeout(() => {
                if (messageEl.parentNode) {
                    messageEl.remove();
                }
            }, 5000);
        }
    }
    
    // 改善的輪詢機制
    function updateProgress() {
        if (polling) {
            console.log('輪詢已在進行中，跳過此次請求');
            return;
        }
        
        polling = true;
        console.log(`開始進度更新 - 重試次數: ${retryCount}, 連續失敗: ${consecutiveFailures}`);
        fetchProgress();
    }
    
    // 計算指數退避延遲時間
    function calculateDelay() {
        const exponentialDelay = baseDelay * Math.pow(1.5, consecutiveFailures);
        return Math.min(maxDelay, exponentialDelay);
    }
    
    function fetchProgress() {
        const startTime = Date.now();
        
        fetch(`/api/customers/import-progress/${sessionId}`, { 
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        })
        .then(response => {
            const responseTime = Date.now() - startTime;
            console.log(`API 響應時間: ${responseTime}ms, 狀態: ${response.status}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ 進度更新成功:', {
                sessionId: data.session_id,
                status: data.status,
                progress: data.progress_percentage + '%',
                processed: data.processed_rows,
                total: data.total_rows,
                success: data.success_count,
                errors: data.error_count,
                lastUpdated: data.last_updated,
                requestSessionId: sessionId,
                sessionIdMatch: data.session_id === sessionId
            });
            
            // 檢查 session_id 是否匹配
            if (data.session_id && data.session_id !== sessionId) {
                console.warn('⚠️ Session ID 不匹配:', {
                    frontend: sessionId,
                    backend: data.session_id
                });
                showUserMessage('⚠️ 會話ID不匹配，可能無法正確顯示進度', 'warning');
            }
            
            // 重置錯誤計數
            retryCount = 0;
            consecutiveFailures = 0;
            
            // 更新進度條
            updateProgressBar(data);
            
            // 更新統計數字
            updateStatistics(data);
            
            // 更新處理時間
            updateProcessingTime(data);
            
            // 更新狀態指示器
            updateStatusIndicator(data);
            
            // 更新錯誤訊息
            updateErrorMessages(data);
            
            // 檢查是否完成或需要繼續輪詢
            handleProgressStatus(data);
            
        })
        .catch(error => {
            console.error('❌ 獲取進度失敗:', {
                error: error.message,
                sessionId: sessionId,
                apiUrl: `/api/customers/import-progress/${sessionId}`,
                retryCount: retryCount + 1,
                consecutiveFailures: consecutiveFailures + 1,
                timestamp: new Date().toISOString()
            });
            retryCount++;
            consecutiveFailures++;
            
            // 根據錯誤類型給予不同的處理
            let errorMessage = '連線失敗';
            if (error.message.includes('HTTP 404')) {
                errorMessage = '找不到匯入會話';
            } else if (error.message.includes('HTTP 500')) {
                errorMessage = '伺服器內部錯誤';
            } else if (error.name === 'TypeError') {
                errorMessage = '網路連線問題';
            }
            
            if (consecutiveFailures === 3) {
                showUserMessage(`⚠️ 進度更新遇到問題: ${errorMessage}，正在重試...`, 'warning');
            }
            
            if (retryCount < maxRetries && consecutiveFailures < maxConsecutiveFailures) {
                const delay = calculateDelay();
                console.log(`${delay}ms 後重試 (${retryCount}/${maxRetries})`);
                setTimeout(() => {
                    polling = false;
                    fetchProgress();
                }, delay);
            } else {
                polling = false;
                console.error(`達到最大重試次數或連續失敗次數，停止輪詢`);
                showUserMessage(`❌ 無法獲取匯入進度，請手動刷新頁面或檢查網路連線`, 'danger');
            }
        });
    }
    
    // 更新進度條
    function updateProgressBar(data) {
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        if (progressBar && progressText) {
            let percentage = parseFloat(data.progress_percentage || 0);
            percentage = Math.min(100, Math.max(0, percentage));
            
            progressBar.style.width = percentage + '%';
            progressText.textContent = percentage.toFixed(1) + '%';
            
            // 狀態樣式
            progressBar.className = 'progress-bar progress-bar-striped';
            if (data.status === 'completed') {
                progressBar.classList.add('bg-success');
                progressBar.classList.remove('progress-bar-striped');
            } else if (data.status === 'failed') {
                progressBar.classList.add('bg-danger');
                progressBar.classList.remove('progress-bar-striped');
            } else if (data.status === 'processing') {
                progressBar.classList.add('progress-bar-animated');
            }
        }
    }
    
    // 更新統計數字
    function updateStatistics(data) {
        const elements = {
            'processed-count': data.processed_rows || 0,
            'success-count': data.success_count || 0,
            'error-count': data.error_count || 0,
            'remaining-count': data.remaining_rows || 0
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                const newValue = parseInt(value).toLocaleString();
                if (element.textContent !== newValue) {
                    element.textContent = newValue;
                    // 添加更新動畫效果
                    element.style.color = '#28a745';
                    setTimeout(() => {
                        element.style.color = '';
                    }, 1000);
                }
            }
        });
    }
    
    // 更新處理時間
    function updateProcessingTime(data) {
        const processingTimeEl = document.getElementById('processing-time');
        if (processingTimeEl && data.processing_time) {
            processingTimeEl.textContent = data.processing_time + ' 秒';
        }
    }
    
    // 更新狀態指示器
    function updateStatusIndicator(data) {
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
    }
    
    // 更新錯誤訊息
    function updateErrorMessages(data) {
        const errorWrapper = document.getElementById('error-container');
        const errorContainer = document.getElementById('error-messages');
        if (errorContainer && errorWrapper) {
            if (data.error_messages && data.error_messages.length > 0) {
                errorContainer.innerHTML = data.error_messages.map(error =>
                    `<div class="mb-1">${error}</div>`
                ).join('');
                errorWrapper.style.display = 'block';
            } else {
                errorContainer.innerHTML = '';
                errorWrapper.style.display = 'none';
            }
        }
    }
    
    // 處理進度狀態
    function handleProgressStatus(data) {
        if (data.status === 'completed' || data.status === 'failed') {
            polling = false;
            console.log(`匯入已${data.status === 'completed' ? '完成' : '失敗'}`);
            
            if (data.status === 'completed') {
                showUserMessage(`✅ 匯入完成！成功 ${data.success_count} 筆，錯誤 ${data.error_count} 筆`, 'success');
                setTimeout(() => {
                    if (confirm(`匯入完成！成功 ${data.success_count} 筆，錯誤 ${data.error_count} 筆。是否要返回客戶列表？`)) {
                        window.location.href = '{{ route("customers.index") }}';
                    }
                }, 2000);
            } else {
                showUserMessage(`❌ 匯入失敗，請檢查錯誤訊息`, 'danger');
            }
        } else {
            // 繼續輪詢，使用固定間隔
            setTimeout(() => {
                polling = false;
                updateProgress();
            }, 3000);
        }
    }
    
    // 手動刷新功能
    window.updateProgress = function() {
        console.log('手動刷新進度');
        showUserMessage('🔄 正在刷新進度...', 'info');
        polling = false;
        retryCount = 0;
        consecutiveFailures = 0;
        updateProgress();
    };
    
    // 初始化
    console.log('匯入進度頁面初始化', {
        sessionId: sessionId,
        sessionIdType: typeof sessionId,
        sessionIdLength: sessionId?.length,
        initialStatus: '{{ $session->status }}',
        apiUrl: `/api/customers/import-progress/${sessionId}`,
        currentUrl: window.location.href,
        timestamp: new Date().toISOString()
    });
    
    // 驗證 session_id 格式
    if (!sessionId || sessionId.length !== 36) {
        console.error('❌ 無效的 session_id 格式:', {
            sessionId: sessionId,
            expectedFormat: 'UUID (36 字元)',
            actualLength: sessionId?.length || 0
        });
        showUserMessage('❌ 系統錯誤：無效的會話ID格式', 'danger');
    }
    
    // 檢查是否需要啟動匯入處理
    if ('{{ $session->status }}' === 'pending') {
        console.log('啟動匯入處理');
        startImportProcess();
    } else {
        // 直接開始輪詢
        setTimeout(updateProgress, 1000);
    }
    
    // 啟動匯入處理
    function startImportProcess() {
        console.log('開始啟動匯入處理');
        
        fetch(`/api/customers/start-import/{{ $session->session_id }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('匯入已啟動:', data);
            showUserMessage('🚀 匯入處理已開始', 'success');
            
            // 啟動後開始輪詢進度
            setTimeout(updateProgress, 2000);
        })
        .catch(error => {
            console.error('啟動匯入失敗:', error);
            showUserMessage('⚠️ 啟動匯入失敗，但將嘗試檢查進度', 'warning');
            
            // 即使啟動失敗，也嘗試輪詢進度
            setTimeout(updateProgress, 3000);
        });
    }
    
    // 頁面離開時停止輪詢
    window.addEventListener('beforeunload', () => {
        console.log('頁面即將離開，停止輪詢');
        polling = false;
    });
    
    // 頁面可見性變化時的處理
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            console.log('頁面隱藏，暫停輪詢');
        } else {
            console.log('頁面重新可見，恢復輪詢');
            if (!polling && '{{ $session->status }}' !== 'completed' && '{{ $session->status }}' !== 'failed') {
                setTimeout(updateProgress, 1000);
            }
        }
    });
});
</script>
@endsection