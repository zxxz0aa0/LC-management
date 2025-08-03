/**
 * 共乘群組詳情頁面 JavaScript
 * 處理群組詳情頁面的互動功能，包括指派司機、更新狀態、取消群組、解除群組
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadAvailableDrivers();
});

/**
 * 初始化事件監聽器
 */
function initializeEventListeners() {
    // 指派司機表單
    const assignDriverForm = document.getElementById('assignDriverForm');
    if (assignDriverForm) {
        assignDriverForm.addEventListener('submit', handleAssignDriver);
    }
    
    // 更新狀態表單
    const updateStatusForm = document.getElementById('updateStatusForm');
    if (updateStatusForm) {
        updateStatusForm.addEventListener('submit', handleUpdateStatus);
    }
    
    // 取消群組表單
    const cancelForm = document.getElementById('cancelForm');
    if (cancelForm) {
        cancelForm.addEventListener('submit', handleCancelGroup);
    }
    
    // 解除群組表單
    const dissolveForm = document.getElementById('dissolveForm');
    if (dissolveForm) {
        dissolveForm.addEventListener('submit', handleDissolveGroup);
    }
    
    // 強制解除選項變化
    const forceDissolve = document.getElementById('forceDissolve');
    if (forceDissolve) {
        forceDissolve.addEventListener('change', handleForceDissolveChange);
    }
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
    const driverSelect = document.querySelector('#assignDriverForm select[name="driver_id"]');
    
    if (driverSelect) {
        // 保留第一個選項（請選擇司機）
        while (driverSelect.children.length > 1) {
            driverSelect.removeChild(driverSelect.lastChild);
        }
        
        // 添加司機選項
        drivers.forEach(driver => {
            const option = document.createElement('option');
            option.value = driver.id;
            option.textContent = driver.name + (driver.fleet_number ? ` (車號：${driver.fleet_number})` : '');
            driverSelect.appendChild(option);
        });
    }
}

/**
 * 處理指派司機
 */
async function handleAssignDriver(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const spinner = submitButton.querySelector('.spinner-border');
    
    // 顯示載入狀態
    submitButton.disabled = true;
    spinner.classList.remove('d-none');
    
    try {
        const formData = new FormData(form);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        const response = await fetch(`/carpool-groups/${groupId}/assign-driver`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message, 'success');
            // 隱藏 Modal 並重新載入頁面
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignDriverModal'));
            modal.hide();
            
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert(result.message, 'error');
        }
        
    } catch (error) {
        console.error('指派司機失敗:', error);
        showAlert('指派司機失敗，請稍後再試', 'error');
    } finally {
        // 重置按鈕狀態
        submitButton.disabled = false;
        spinner.classList.add('d-none');
    }
}

/**
 * 處理更新狀態
 */
async function handleUpdateStatus(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    
    // 顯示載入狀態
    submitButton.disabled = true;
    
    try {
        const formData = new FormData(form);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        const response = await fetch(`/carpool-groups/${groupId}/update-status`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message, 'success');
            // 隱藏 Modal 並重新載入頁面
            const modal = bootstrap.Modal.getInstance(document.getElementById('updateStatusModal'));
            modal.hide();
            
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert(result.message, 'error');
        }
        
    } catch (error) {
        console.error('更新狀態失敗:', error);
        showAlert('更新狀態失敗，請稍後再試', 'error');
    } finally {
        // 重置按鈕狀態
        submitButton.disabled = false;
    }
}

/**
 * 處理取消群組
 */
async function handleCancelGroup(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const spinner = submitButton.querySelector('.spinner-border');
    
    // 確認操作
    if (!confirm('確定要取消此群組嗎？此操作不可復原！')) {
        return;
    }
    
    // 顯示載入狀態
    submitButton.disabled = true;
    spinner.classList.remove('d-none');
    
    try {
        const formData = new FormData(form);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        const response = await fetch(`/carpool-groups/${groupId}/cancel`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message, 'success');
            // 隱藏 Modal 並重新載入頁面
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
            modal.hide();
            
            setTimeout(() => {
                window.location.href = '/carpool-groups';
            }, 1500);
        } else {
            showAlert(result.message, 'error');
        }
        
    } catch (error) {
        console.error('取消群組失敗:', error);
        showAlert('取消群組失敗，請稍後再試', 'error');
    } finally {
        // 重置按鈕狀態
        submitButton.disabled = false;
        spinner.classList.add('d-none');
    }
}

/**
 * 處理解除群組
 */
async function handleDissolveGroup(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const spinner = submitButton.querySelector('.spinner-border');
    
    // 確認操作
    if (!confirm('確定要解除此群組嗎？此操作會將共乘訂單拆分為獨立訂單，不可復原！')) {
        return;
    }
    
    // 顯示載入狀態
    submitButton.disabled = true;
    spinner.classList.remove('d-none');
    
    try {
        const formData = new FormData(form);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        const response = await fetch(`/carpool-groups/${groupId}/dissolve`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message, 'success');
            // 隱藏 Modal 並重新載入頁面
            const modal = bootstrap.Modal.getInstance(document.getElementById('dissolveModal'));
            modal.hide();
            
            setTimeout(() => {
                window.location.href = '/carpool-groups';
            }, 1500);
        } else {
            showAlert(result.message, 'error');
        }
        
    } catch (error) {
        console.error('解除群組失敗:', error);
        showAlert('解除群組失敗，請稍後再試', 'error');
    } finally {
        // 重置按鈕狀態
        submitButton.disabled = false;
        spinner.classList.add('d-none');
    }
}

/**
 * 處理強制解除選項變化
 */
function handleForceDissolveChange() {
    const forceDissolve = document.getElementById('forceDissolve');
    const warning = document.getElementById('dissolveWarning');
    
    if (forceDissolve.checked) {
        warning.style.display = 'block';
    } else {
        warning.style.display = 'none';
    }
}

/**
 * 顯示指派司機 Modal
 */
function showAssignDriverModal() {
    const modal = new bootstrap.Modal(document.getElementById('assignDriverModal'));
    modal.show();
}

/**
 * 顯示更新狀態 Modal
 */
function showUpdateStatusModal() {
    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}

/**
 * 顯示取消 Modal
 */
function showCancelModal() {
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

/**
 * 顯示解除 Modal
 */
function showDissolveModal() {
    const modal = new bootstrap.Modal(document.getElementById('dissolveModal'));
    modal.show();
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
window.showUpdateStatusModal = showUpdateStatusModal;
window.showCancelModal = showCancelModal;
window.showDissolveModal = showDissolveModal;