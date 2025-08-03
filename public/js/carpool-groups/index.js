/**
 * 共乘群組管理 - 列表頁面 JavaScript
 * 處理列表頁面的互動功能，包括搜尋、篩選、批量操作、統計資訊
 */

document.addEventListener('DOMContentLoaded', function() {
    // 初始化
    initializeEventListeners();
    initializeCheckboxes();
    loadAvailableDrivers();
});

/**
 * 初始化事件監聽器
 */
function initializeEventListeners() {
    // 全選功能
    const selectAllHeader = document.getElementById('select-all-header');
    const selectAll = document.getElementById('select-all');
    
    if (selectAllHeader) {
        selectAllHeader.addEventListener('change', toggleAllCheckboxes);
    }
    
    if (selectAll) {
        selectAll.addEventListener('change', toggleAllCheckboxes);
    }
    
    // 批量操作選擇
    const batchAction = document.getElementById('batch-action');
    if (batchAction) {
        batchAction.addEventListener('change', handleBatchActionChange);
    }
    
    // 群組選擇變化
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('group-checkbox')) {
            updateBatchActionsVisibility();
        }
    });
}

/**
 * 初始化複選框狀態
 */
function initializeCheckboxes() {
    updateBatchActionsVisibility();
}

/**
 * 切換全選狀態
 */
function toggleAllCheckboxes() {
    const checkboxes = document.querySelectorAll('.group-checkbox');
    const selectAllHeader = document.getElementById('select-all-header');
    const selectAll = document.getElementById('select-all');
    
    const isChecked = selectAllHeader?.checked || selectAll?.checked;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    
    // 同步兩個全選框的狀態
    if (selectAllHeader && selectAll) {
        selectAllHeader.checked = isChecked;
        selectAll.checked = isChecked;
    }
    
    updateBatchActionsVisibility();
}

/**
 * 更新批量操作區域的顯示狀態
 */
function updateBatchActionsVisibility() {
    const selectedCheckboxes = document.querySelectorAll('.group-checkbox:checked');
    const batchActions = document.getElementById('batch-actions');
    
    if (selectedCheckboxes.length > 0) {
        batchActions.style.display = 'block';
    } else {
        batchActions.style.display = 'none';
        // 重置批量操作選項
        document.getElementById('batch-action').value = '';
        hideBatchActionInputs();
    }
}

/**
 * 處理批量操作選擇變化
 */
function handleBatchActionChange() {
    const action = document.getElementById('batch-action').value;
    const driverContainer = document.getElementById('driver-select-container');
    const reasonContainer = document.getElementById('reason-input-container');
    
    // 隱藏所有輸入容器
    hideBatchActionInputs();
    
    // 根據操作類型顯示對應的輸入欄位
    switch (action) {
        case 'assign_driver':
            driverContainer.style.display = 'block';
            break;
        case 'cancel':
        case 'dissolve':
            reasonContainer.style.display = 'block';
            break;
    }
}

/**
 * 隱藏批量操作輸入欄位
 */
function hideBatchActionInputs() {
    document.getElementById('driver-select-container').style.display = 'none';
    document.getElementById('reason-input-container').style.display = 'none';
}

/**
 * 載入可用司機列表
 */
async function loadAvailableDrivers() {
    try {
        const response = await fetch('/drivers/fleet-search', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            const drivers = await response.json();
            populateDriverSelect(drivers);
        }
    } catch (error) {
        console.error('載入司機列表失敗:', error);
    }
}

/**
 * 填充司機選擇選項
 */
function populateDriverSelect(drivers) {
    const batchDriverSelect = document.getElementById('batch-driver');
    const assignDriverSelects = document.querySelectorAll('#assignDriverSelect');
    
    // 清空現有選項（保留預設選項）
    [batchDriverSelect, ...assignDriverSelects].forEach(select => {
        if (select) {
            // 保留第一個選項（請選擇司機）
            while (select.children.length > 1) {
                select.removeChild(select.lastChild);
            }
            
            // 添加司機選項
            drivers.forEach(driver => {
                const option = document.createElement('option');
                option.value = driver.id;
                option.textContent = driver.name + (driver.fleet_number ? ` (車號：${driver.fleet_number})` : '');
                select.appendChild(option);
            });
        }
    });
}

/**
 * 執行批量操作
 */
async function executeBatchAction() {
    const action = document.getElementById('batch-action').value;
    const selectedGroups = Array.from(document.querySelectorAll('.group-checkbox:checked'))
        .map(checkbox => checkbox.value);
    
    if (!action) {
        showAlert('請選擇要執行的操作', 'warning');
        return;
    }
    
    if (selectedGroups.length === 0) {
        showAlert('請選擇要操作的群組', 'warning');
        return;
    }
    
    // 驗證必要欄位
    const validationResult = validateBatchAction(action);
    if (!validationResult.valid) {
        showAlert(validationResult.message, 'warning');
        return;
    }
    
    // 確認操作
    const confirmMessage = getBatchActionConfirmMessage(action, selectedGroups.length);
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // 顯示載入狀態
    showLoadingModal('執行批量操作中...');
    
    try {
        const requestData = {
            action: action,
            group_ids: selectedGroups,
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };
        
        // 根據操作類型添加額外參數
        if (action === 'assign_driver') {
            requestData.driver_id = document.getElementById('batch-driver').value;
        } else if (action === 'cancel' || action === 'dissolve') {
            requestData.reason = document.getElementById('batch-reason').value;
        }
        
        const response = await fetch('/carpool-groups/batch-action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        const result = await response.json();
        
        hideLoadingModal();
        
        if (result.success) {
            showAlert(result.message, 'success');
            // 重新載入頁面
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert(result.message, 'error');
        }
        
    } catch (error) {
        hideLoadingModal();
        console.error('批量操作失敗:', error);
        showAlert('操作失敗，請稍後再試', 'error');
    }
}

/**
 * 驗證批量操作參數
 */
function validateBatchAction(action) {
    switch (action) {
        case 'assign_driver':
            const driverId = document.getElementById('batch-driver').value;
            if (!driverId) {
                return { valid: false, message: '請選擇司機' };
            }
            break;
        case 'cancel':
        case 'dissolve':
            // 原因是可選的，不需要驗證
            break;
    }
    
    return { valid: true };
}

/**
 * 取得批量操作確認訊息
 */
function getBatchActionConfirmMessage(action, count) {
    const actionNames = {
        assign_driver: '指派司機',
        cancel: '取消群組',
        dissolve: '解除群組'
    };
    
    const actionName = actionNames[action] || action;
    return `確定要對 ${count} 個群組執行「${actionName}」操作嗎？此操作不可復原。`;
}

/**
 * 清除選擇
 */
function clearSelection() {
    // 取消所有選擇
    document.querySelectorAll('.group-checkbox:checked').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // 取消全選狀態
    const selectAllHeader = document.getElementById('select-all-header');
    const selectAll = document.getElementById('select-all');
    if (selectAllHeader) selectAllHeader.checked = false;
    if (selectAll) selectAll.checked = false;
    
    // 隱藏批量操作區域
    updateBatchActionsVisibility();
}

/**
 * 顯示指派司機 Modal
 */
function showAssignDriverModal(groupId) {
    document.getElementById('assignGroupId').value = groupId;
    const modal = new bootstrap.Modal(document.getElementById('assignDriverModal'));
    modal.show();
}

/**
 * 顯示取消群組 Modal
 */
function showCancelModal(groupId) {
    document.getElementById('cancelGroupId').value = groupId;
    const modal = new bootstrap.Modal(document.getElementById('cancelGroupModal'));
    modal.show();
}

/**
 * 顯示解除群組 Modal
 */
function showDissolveModal(groupId) {
    document.getElementById('dissolveGroupId').value = groupId;
    const modal = new bootstrap.Modal(document.getElementById('dissolveGroupModal'));
    modal.show();
}


/**
 * 顯示載入 Modal
 */
function showLoadingModal(text = '處理中...') {
    document.getElementById('loadingText').textContent = text;
    const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
    modal.show();
}

/**
 * 隱藏載入 Modal
 */
function hideLoadingModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
    if (modal) {
        modal.hide();
    }
}

/**
 * 顯示提示訊息
 */
function showAlert(message, type = 'info') {
    // 建立 Toast 提示
    const toastContainer = document.querySelector('.toast-container') || createToastContainer();
    
    const toastId = 'toast-' + Date.now();
    const bgClass = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    }[type] || 'bg-info';
    
    const toastHtml = `
        <div class="toast ${bgClass} text-white" id="${toastId}" role="alert">
            <div class="toast-header ${bgClass} text-white border-0">
                <strong class="me-auto">系統訊息</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    // Toast 隱藏後移除元素
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * 建立 Toast 容器
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// 暴露全域函數供 HTML 調用
window.showAssignDriverModal = showAssignDriverModal;
window.showCancelModal = showCancelModal;
window.showDissolveModal = showDissolveModal;
window.executeBatchAction = executeBatchAction;
window.clearSelection = clearSelection;