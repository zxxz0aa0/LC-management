// 設定 Toastr 選項
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "timeOut": "5000",
};

// 排序功能變數
let currentSort = { column: null, direction: 'asc' };

// 排序函數
function sortTable(column) {
    const tableBody = document.querySelector('#dispatch-orders-table tbody');
    const rows = Array.from(tableBody.querySelectorAll('tr')).filter(row => !row.id.includes('empty-dispatch-row'));

    if (rows.length === 0) return;

    // 確定排序方向
    if (currentSort.column === column) {
        currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        currentSort.column = column;
        currentSort.direction = 'asc';
    }

    // 排序邏輯
    rows.sort((a, b) => {
        let aVal, bVal;

        switch (column) {
            case 'date':
                // 從文本中提取日期
                aVal = a.cells[0].textContent.trim();
                bVal = b.cells[0].textContent.trim();
                // 轉換為可比較的日期格式 (假設為 MM/DD)
                aVal = new Date('2025/' + aVal);
                bVal = new Date('2025/' + bVal);
                break;
            case 'time':
                aVal = a.cells[1].textContent.trim();
                bVal = b.cells[1].textContent.trim();
                // 時間比較 (HH:MM)
                aVal = aVal.replace(':', '');
                bVal = bVal.replace(':', '');
                aVal = parseInt(aVal);
                bVal = parseInt(bVal);
                break;
            case 'passenger':
                aVal = a.cells[2].textContent.trim();
                bVal = b.cells[2].textContent.trim();
                break;
            case 'origin_area':
                aVal = a.cells[3].textContent.trim();
                bVal = b.cells[3].textContent.trim();
                break;
            case 'dest_area':
                aVal = a.cells[5].textContent.trim();
                bVal = b.cells[5].textContent.trim();
                break;
            case 'type':
                aVal = a.cells[7].textContent.trim();
                bVal = b.cells[7].textContent.trim();
                break;
            default:
                return 0;
        }

        // 排序比較
        if (column === 'date' || column === 'time') {
            return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
        } else {
            if (aVal < bVal) return currentSort.direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return currentSort.direction === 'asc' ? 1 : -1;
            return 0;
        }
    });

    // 更新表格顯示
    rows.forEach(row => tableBody.appendChild(row));

    // 更新排序圖標
    updateSortIcons(column, currentSort.direction);
}

// 更新排序圖標
function updateSortIcons(column, direction) {
    // 重置所有圖標
    document.querySelectorAll('.sortable-header').forEach(header => {
        header.classList.remove('sorted-asc', 'sorted-desc');
        const icon = header.querySelector('.sort-icon');
        if (icon) {
            icon.className = 'fas fa-sort sort-icon';
        }
    });

    // 設定當前排序欄位的圖標
    const currentHeader = document.querySelector(`[data-sort="${column}"]`);
    const currentIcon = document.querySelector(`#sort-icon-${column}`);

    if (currentHeader && currentIcon) {
        if (direction === 'asc') {
            currentHeader.classList.add('sorted-asc');
            currentIcon.className = 'fas fa-sort-up sort-icon';
        } else {
            currentHeader.classList.add('sorted-desc');
            currentIcon.className = 'fas fa-sort-down sort-icon';
        }
    }
}

// 快速日期設定函數
function setQuickDate(daysOffset) {
    try {
        const serverDate = new Date(window.serverCurrentDate + 'T00:00:00+08:00');
        serverDate.setDate(serverDate.getDate() + daysOffset);

        // 使用本地日期格式化，避免 toISOString() 的 UTC 轉換問題
        const year = serverDate.getFullYear();
        const month = String(serverDate.getMonth() + 1).padStart(2, '0');
        const day = String(serverDate.getDate()).padStart(2, '0');
        const dateString = `${year}-${month}-${day}`;

        // 只設定開始和結束日期，不執行搜尋
        document.querySelector('input[name="start_date"]').value = dateString;
        document.querySelector('input[name="end_date"]').value = dateString;

        // 顯示提示訊息
        toastr.success('已設定日期為 ' + dateString);
    } catch (error) {
        console.error('快速日期設定錯誤:', error);
        toastr.error('日期設定失敗，請手動輸入日期');
    }
}

// 新增訂單到排趟列表
function addToDispatch(orderId) {
    // 取得 CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    $.ajax({
        url: '/manual-dispatch/add',
        type: 'POST',
        data: {
            order_id: orderId,
            _token: csrfToken
        },
        success: function(response) {
            if (response.status === 'success') {
                // 移除待派遣列表中的該筆訂單
                $('#available-order-' + orderId).fadeOut(300, function() {
                    $(this).remove();

                    // 檢查是否還有可選訂單
                    if ($('#available-orders-table tbody tr:visible').length === 0) {
                        $('#available-orders-table tbody').html(
                            '<tr><td colspan="10" class="text-center text-muted">沒有更多可選擇的訂單</td></tr>'
                        );
                    }
                });

                // 新增到排趟列表
                addOrderToDispatchTable(response.order);
                updateDispatchCount();

                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            let errorMessage = '操作失敗，請稍後再試';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            toastr.error(errorMessage);
        }
    });
}

// 從排趟列表移除訂單
function removeFromDispatch(orderId) {
    // 取得 CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    $.ajax({
        url: '/manual-dispatch/remove',
        type: 'DELETE',
        data: {
            order_id: orderId,
            _token: csrfToken
        },
        success: function(response) {
            if (response.status === 'success') {
                // 從排趟列表移除
                $('#dispatch-order-' + orderId).fadeOut(300, function() {
                    $(this).remove();
                    updateDispatchCount();

                    // 如果列表為空，顯示空狀態
                    if ($('#dispatch-orders-table tbody tr:visible').length === 0) {
                        $('#dispatch-orders-table tbody').html(
                            '<tr id="empty-dispatch-row"><td colspan="11" class="text-center text-muted">尚未選擇任何訂單</td></tr>'
                        );
                    }
                });

                toastr.success(response.message);

                // 刷新頁面以重新顯示可選訂單
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error('操作失敗，請稍後再試');
        }
    });
}

// 批次指派隊員編號
function batchAssign() {
    const fleetNumber = $('#batch_fleet_number').val().trim();

    if (!fleetNumber) {
        toastr.error('請輸入隊員編號');
        $('#batch_fleet_number').focus();
        return;
    }

    // 確認對話框
    if (!confirm(`確定要將所有訂單指派給隊員 ${fleetNumber} 嗎？`)) {
        return;
    }

    // 取得 CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    $.ajax({
        url: '/manual-dispatch/batch-assign',
        type: 'POST',
        data: {
            fleet_number: fleetNumber,
            _token: csrfToken
        },
        success: function(response) {
            if (response.status === 'success') {
                // 先顯示成功訊息
                toastr.success(response.message);

                // 清空輸入框
                $('#batch_fleet_number').val('');

                // 清空排趟列表
                $('#dispatch-orders-table tbody').html(
                    '<tr id="empty-dispatch-row"><td colspan="11" class="text-center text-muted">尚未選擇任何訂單</td></tr>'
                );
                updateDispatchCount();

                // 延長等待時間，確保使用者看到訊息後再刷新頁面
                setTimeout(() => {
                    location.reload();
                }, 3000);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            let errorMessage = '批次指派失敗，請稍後再試';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            toastr.error(errorMessage);
        }
    });
}

// 清空排趟列表
function clearDispatch() {
    if ($('#dispatch-orders-table tbody tr:visible').length === 0 ||
        $('#dispatch-orders-table tbody').find('#empty-dispatch-row').length > 0) {
        toastr.info('排趟列表已經是空的');
        return;
    }

    if (!confirm('確定要清空排趟列表嗎？')) {
        return;
    }

    // 取得 CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    $.ajax({
        url: '/manual-dispatch/clear',
        type: 'POST',
        data: {
            _token: csrfToken
        },
        success: function(response) {
            if (response.status === 'success') {
                toastr.success(response.message);

                // 清空排趟列表
                $('#dispatch-orders-table tbody').html(
                    '<tr id="empty-dispatch-row"><td colspan="11" class="text-center text-muted">尚未選擇任何訂單</td></tr>'
                );
                updateDispatchCount();

                // 刷新頁面以重新顯示所有可選訂單
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error('操作失敗，請稍後再試');
        }
    });
}

// 新增訂單到排趟表格
function addOrderToDispatchTable(order) {
    // 移除空狀態行
    $('#empty-dispatch-row').remove();

    const orderDate = new Date(order.date);
    const orderTime = order.time.substring(0, 5); // 取 HH:MM 格式

    const newRow = `
        <tr id="dispatch-order-${order.id}">
            <td class="text-center">${(orderDate.getMonth() + 1).toString().padStart(2, '0')}/${orderDate.getDate().toString().padStart(2, '0')}</td>
            <td class="text-center">${orderTime}</td>
            <td class="text-center">${order.name}</td>
            <td class="text-center">${order.origin_area}</td>
            <td>${order.origin_address}</td>
            <td class="text-center">${order.dest_area}</td>
            <td>${order.dest_address}</td>
            <td class="text-center">${order.type}</td>
            <td class="text-center">
                ${order.special_status ? `<span class="badge bg-danger">${order.special_status}</span>` : '-'}
            </td>
            <td class="text-center">-</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeFromDispatch(${order.id})">
                    <i class="fas fa-times"></i> 移除
                </button>
            </td>
        </tr>
    `;

    $('#dispatch-orders-table tbody').append(newRow);
}

// 更新排趟列表計數
function updateDispatchCount() {
    // 計算已指派訂單數量 (assigned-order 開頭的 tr)
    const assignedCount = $('#dispatch-orders-table tbody tr[id^="assigned-order-"]').length;
    // 計算待指派訂單數量 (dispatch-order 開頭的 tr)
    const pendingCount = $('#dispatch-orders-table tbody tr[id^="dispatch-order-"]').length;
    // 總數 = 已指派 + 待指派
    const totalCount = assignedCount + pendingCount;

    // 如果有空狀態行，則計數為 0
    const actualCount = $('#dispatch-orders-table tbody').find('#empty-dispatch-row').length > 0 ? 0 : totalCount;
    $('#dispatch-count').text(actualCount);
}

// 頁面載入完成後的初始化
$(document).ready(function() {
    updateDispatchCount();
});
