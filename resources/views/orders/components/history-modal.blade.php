{{-- 歷史訂單選擇 Modal --}}
<div class="modal fade" id="historyOrderModal" tabindex="-1" aria-labelledby="historyOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="historyOrderModalLabel">
                    <i class="fas fa-history me-2"></i>選擇歷史訂單
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- 載入狀態 --}}
                <div id="historyOrderLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">載入中...</span>
                    </div>
                    <div class="mt-2">載入歷史訂單中...</div>
                </div>

                {{-- 歷史訂單列表 --}}
                <div id="historyOrderContent" style="display: none;">
                    <div class="mb-3">
                        <p class="text-muted mb-2">
                            <i class="fas fa-info-circle me-1"></i>
                            顯示最近 10 筆訂單，點擊「選擇」可快速填入用車資訊
                        </p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="6%">日期</th>
                                    <th width="6%">時間</th>
                                    <th width="8%">電話</th>
                                    <th width="23%">起點</th>
                                    <th width="23%">終點</th>
                                    <th width="6%">陪同</th>
                                    <th width="6%">輪椅</th>
                                    <th width="6%">爬梯</th>
                                    <th width="6%">狀態</th>
                                    <th width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody id="historyOrderList">
                                {{-- 動態載入內容 --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 空資料狀態 --}}
                <div id="historyOrderEmpty" style="display: none;" class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">沒有歷史訂單</h5>
                    <p class="text-muted">該客戶還沒有任何訂單記錄</p>
                </div>

                {{-- 錯誤狀態 --}}
                <div id="historyOrderError" style="display: none;" class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">載入失敗</h5>
                    <p class="text-muted">無法載入歷史訂單，請稍後再試</p>
                    <button type="button" class="btn btn-outline-primary" onclick="loadHistoryOrders()">
                        <i class="fas fa-redo me-1"></i>重新載入
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>關閉
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.history-order-row {
    cursor: pointer;
    transition: background-color 0.2s;
}

.history-order-row:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.status-open { background-color: #d4edda; color: #155724; }
.status-assigned { background-color: #cce5ff; color: #004085; }
.status-replacement { background-color: #fff3cd; color: #856404; }
.status-cancelled { background-color: #f8d7da; color: #721c24; }
</style>