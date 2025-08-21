<!-- 地標選擇 Modal -->
<div class="modal fade" id="landmarkModal" tabindex="-1" aria-labelledby="landmarkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="landmarkModalLabel">選擇地標</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body">
                <!-- 搜尋欄位 -->
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="landmarkSearchInput" 
                               placeholder="輸入地標名稱或地址關鍵字搜尋...">
                        <button class="btn btn-primary" type="button" id="landmarkSearchBtn">搜尋</button>
                    </div>
                </div>

                <!-- 分類篩選 -->
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-select" id="landmarkCategoryFilter">
                                <option value="">所有分類</option>
                                @foreach(App\Models\Landmark::CATEGORIES as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showPopularOnly">
                                <label class="form-check-label" for="showPopularOnly">
                                    只顯示熱門地標
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 載入狀態 -->
                <div id="landmarkLoading" class="text-center py-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">載入中...</span>
                    </div>
                    <p class="mt-2">搜尋中...</p>
                </div>

                <!-- 搜尋結果 -->
                <div id="landmarkResults">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        請輸入搜尋關鍵字來查找地標
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <a href="{{ route('landmarks.create') }}" class="btn btn-success" target="_blank">
                    <i class="fas fa-plus"></i> 新增地標
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// 地標選擇相關變數
let currentTargetInput = null;
let landmarkSearchTimeout = null;

// 顯示地標選擇 Modal
function showLandmarkModal(targetInput, initialKeyword = '') {
    currentTargetInput = targetInput;
    
    // 清除之前的搜尋結果
    document.getElementById('landmarkResults').innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            請輸入搜尋關鍵字來查找地標
        </div>
    `;
    
    // 設定搜尋欄位
    document.getElementById('landmarkSearchInput').value = initialKeyword;
    document.getElementById('landmarkCategoryFilter').value = '';
    document.getElementById('showPopularOnly').checked = false;
    
    // 顯示 Modal
    const landmarkModalElement = document.getElementById('landmarkModal');
    const modal = new bootstrap.Modal(landmarkModalElement);
    modal.show();
    
    // 如果有初始關鍵字，自動搜尋
    if (initialKeyword.trim()) {
        setTimeout(() => {
            searchLandmarks();
        }, 300);
    } else {
        // 聚焦到搜尋欄位
        setTimeout(() => {
            document.getElementById('landmarkSearchInput').focus();
        }, 500);
    }
}

// 搜尋地標
function searchLandmarks() {
    const keyword = document.getElementById('landmarkSearchInput').value.trim();
    const category = document.getElementById('landmarkCategoryFilter').value;
    const popularOnly = document.getElementById('showPopularOnly').checked;
    
    if (!keyword && !category && !popularOnly) {
        document.getElementById('landmarkResults').innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                請輸入搜尋關鍵字或選擇分類
            </div>
        `;
        return;
    }
    
    // 顯示載入狀態
    document.getElementById('landmarkLoading').style.display = 'block';
    document.getElementById('landmarkResults').innerHTML = '';
    
    // 建立查詢參數
    const params = new URLSearchParams();
    if (keyword) params.append('keyword', keyword);
    if (category) params.append('category', category);
    if (popularOnly) params.append('popular', '1');
    
    // 發送搜尋請求
    fetch(`/landmarks-search?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('landmarkLoading').style.display = 'none';
            
            if (data.success && data.data.length > 0) {
                displayLandmarkResults(data.data);
            } else {
                document.getElementById('landmarkResults').innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-search"></i>
                        查無符合條件的地標
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('landmarkLoading').style.display = 'none';
            document.getElementById('landmarkResults').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    搜尋失敗，請稍後再試
                </div>
            `;
            console.error('搜尋地標錯誤:', error);
        });
}

// 顯示搜尋結果
function displayLandmarkResults(landmarks) {
    const resultsContainer = document.getElementById('landmarkResults');
    
    if (landmarks.length === 0) {
        resultsContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                未找到符合條件的地標
            </div>
        `;
        return;
    }
    
    let html = '<div class="list-group">';
    
    landmarks.forEach(landmark => {
        const categoryBadge = getCategoryBadge(landmark.category);
        const usageCount = landmark.usage_count > 0 ? `<small class="text-muted">(使用${landmark.usage_count}次)</small>` : '';
        const fullAddress = landmark.city + landmark.district + landmark.address;
        
        html += `
            <div class="list-group-item list-group-item-action landmark-item" 
                 data-landmark='${JSON.stringify(landmark)}'>
                <div class="d-flex w-100 justify-content-between">
                    <div>
                        <h6 class="mb-1">
                            <i class="fas fa-map-marker-alt text-danger"></i>
                            ${landmark.name}
                            ${categoryBadge}
                        </h6>
                        <p class="mb-1">${fullAddress}</p>
                        ${usageCount}
                    </div>
                    <div class="text-end">
                        <button class="btn btn-sm btn-success select-landmark-btn">
                            <i class="fas fa-check"></i> 選擇
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    resultsContainer.innerHTML = html;
    
    // 綁定選擇按鈕事件
    document.querySelectorAll('.select-landmark-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // 阻止事件冒泡和預設行為
            e.preventDefault();
            e.stopPropagation();
            
            const landmarkData = JSON.parse(this.closest('.landmark-item').dataset.landmark);
            selectLandmark(landmarkData);
        });
    });
}

// 選擇地標
function selectLandmark(landmark) {
    if (!currentTargetInput) return;
    
    // 填入地標地址
    const fullAddress = landmark.city + landmark.district + landmark.address;
    currentTargetInput.value = fullAddress;
    
    // 儲存地標 ID（用於統計使用次數）
    currentTargetInput.setAttribute('data-landmark-id', landmark.id);
    
    // 標記為地標選擇，避免觸發其他監聽器
    currentTargetInput.setAttribute('data-landmark-selected', 'true');
    
    // 更新地標使用次數
    updateLandmarkUsage(landmark.id);
    
    // 阻止任何可能的表單提交
    const form = currentTargetInput.closest('form');
    if (form) {
        // 暫時禁用表單提交
        form.setAttribute('data-landmark-selecting', 'true');
        
        // 禁用所有提交按鈕
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitButtons.forEach(btn => {
            btn.disabled = true;
            btn.setAttribute('data-landmark-disabled', 'true');
        });
        
        // 延遲重新啟用
        setTimeout(() => {
            form.removeAttribute('data-landmark-selecting');
            submitButtons.forEach(btn => {
                if (btn.hasAttribute('data-landmark-disabled')) {
                    btn.disabled = false;
                    btn.removeAttribute('data-landmark-disabled');
                }
            });
        }, 1000);
    }
    
    // 關閉 Modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('landmarkModal'));
    if (modal) {
        modal.hide();
    }
    
    // 清除目標輸入框
    currentTargetInput = null;
}

// 更新地標使用次數
function updateLandmarkUsage(landmarkId) {
    fetch('/landmarks-usage', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ landmark_id: landmarkId })
    }).catch(error => {
        console.error('更新地標使用次數失敗:', error);
    });
}

// 獲取分類標籤
function getCategoryBadge(category) {
    const categories = {
        'hospital': { text: '醫院', class: 'bg-danger' },
        'clinic': { text: '診所', class: 'bg-warning' },
        'transport': { text: '交通', class: 'bg-primary' },
        'education': { text: '教育', class: 'bg-success' },
        'government': { text: '政府機關', class: 'bg-warning' },
        'commercial': { text: '商業', class: 'bg-info' },
        'general': { text: '一般', class: 'bg-secondary' }
    };
    
    const cat = categories[category] || { text: category, class: 'bg-secondary' };
    return `<span class="badge ${cat.class}">${cat.text}</span>`;
}

// 防抖動搜尋
function debounceSearch() {
    clearTimeout(landmarkSearchTimeout);
    landmarkSearchTimeout = setTimeout(searchLandmarks, 300);
}

// 綁定事件監聽器
document.addEventListener('DOMContentLoaded', function() {
    // 搜尋按鈕
    document.getElementById('landmarkSearchBtn').addEventListener('click', searchLandmarks);
    
    // 搜尋欄位（Enter 鍵和輸入防抖動）
    const searchInput = document.getElementById('landmarkSearchInput');
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchLandmarks();
        }
    });
    searchInput.addEventListener('input', debounceSearch);
    
    // 分類篩選
    document.getElementById('landmarkCategoryFilter').addEventListener('change', searchLandmarks);
    
    // 熱門地標篩選
    document.getElementById('showPopularOnly').addEventListener('change', searchLandmarks);
});
</script>

<style>
.landmark-item:hover {
    background-color: #f8f9fa;
}

.landmark-item .btn {
    opacity: 0.7;
    transition: opacity 0.2s;
}

.landmark-item:hover .btn {
    opacity: 1;
}

#landmarkModal {
    z-index: 1060 !important;
}

#landmarkModal .modal-dialog {
    margin: 1.75rem auto;
    max-width: 800px;
}

#landmarkModal .modal-body {
    padding: 1rem;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
}

#landmarkModal .modal-content {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#landmarkResults {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.list-group-item {
    border-left: 4px solid transparent;
}

.list-group-item:hover {
    border-left-color: #007bff;
}
</style>