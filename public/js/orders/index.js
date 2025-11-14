/**
 * 訂單列表頁面 JavaScript
 * 職責：列表顯示、搜尋、分頁、基本操作
 */
class OrderIndex {
    constructor() {
        this.dataTable = null;
        this.searchDataTable = null;
        this.init();
    }

    init() {
        this.initializeDataTable();
        this.bindEvents();

        // 將實例保存到 window 以便外部調用
        window.orderIndex = this;
    }

    /**
     * 初始化 DataTable
     */
    initializeDataTable() {
        // 確保 jQuery 和 DataTable 已載入
        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            console.error('jQuery 或 DataTable 未載入');
            return;
        }

        // 檢查表格是否存在
        const $table = $('#ordersTable');
        if (!$table.length) {
            console.log('表格 #ordersTable 不存在');
            return;
        }

        // 檢查表格結構
        const $thead = $table.find('thead');
        const $tbody = $table.find('tbody');

        if (!$thead.length || !$tbody.length) {
            console.log('表格結構不完整，缺少 thead 或 tbody');
            return;
        }

        // 檢查是否有資料行（排除空資料提示行）
        const dataRows = $tbody.find('tr').filter(function() {
            // 過濾掉 colspan 的空資料行和標記為 no-data-row 的行
            return $(this).find('td[colspan]').length === 0 && !$(this).hasClass('no-data-row');
        });

        console.log('檢測到資料行數量:', dataRows.length);

        // 如果沒有資料行，不初始化 DataTable
        if (dataRows.length === 0) {
            console.log('無資料行，跳過 DataTable 初始化');
            return;
        }

        // 先銷毀已存在的 DataTable
        if ($.fn.DataTable.isDataTable('#ordersTable')) {
            $table.DataTable().destroy();
        }

        // 檢查表格欄位數量
        const columnCount = $thead.find('th').length;
        console.log('檢測到表格欄位數量:', columnCount);

        // 確保欄位數量正確
        if (columnCount === 0) {
            console.error('表格欄位數量為 0，無法初始化 DataTable');
            return;
        }

        try {
            this.dataTable = $table.DataTable({
                language: {
                    lengthMenu: "每頁顯示 _MENU_ 筆資料",
                    zeroRecords: "查無資料",
                    info: "顯示第 _START_ 到 _END_ 筆，共 _TOTAL_ 筆資料",
                    infoEmpty: "目前沒有資料",
                    infoFiltered: "(從 _MAX_ 筆資料中篩選)",
                    search: "快速搜尋：",
                    paginate: {
                        first: "第一頁",
                        last: "最後一頁",
                        next: "下一頁",
                        previous: "上一頁"
                    }
                },
                pageLength: 100,
                order: [[2, 'asc'], [3, 'asc']], // 先依日期欄(前一欄)升序，再依時間欄升序
                columnDefs: [
                    { targets: [columnCount - 1], orderable: false } // 最後一欄（操作欄）不可排序
                ],
                responsive: true,
                searching: true,
                paging: true,
                info: true,
                autoWidth: false,
                destroy: true, // 允許重新初始化
                drawCallback: function() {
                    console.log('DataTable 繪製完成');
                }
            });

            console.log('DataTable 初始化成功');
        } catch (error) {
            console.error('DataTable 初始化失敗:', error);
        }
    }

    /**
     * 初始化搜尋模式表格 (爬梯模式)
     */
    initializeSearchTable() {
        // 確保 jQuery 和 DataTable 已載入
        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            console.error('jQuery 或 DataTable 未載入');
            return;
        }

        // 檢查表格是否存在
        const $table = $('#ordersTableSearch');
        if (!$table.length) {
            console.log('表格 #ordersTableSearch 不存在');
            return;
        }

        // 檢查表格結構
        const $thead = $table.find('thead');
        const $tbody = $table.find('tbody');

        if (!$thead.length || !$tbody.length) {
            console.log('表格結構不完整，缺少 thead 或 tbody');
            return;
        }

        // 檢查是否有資料行（排除空資料提示行）
        const dataRows = $tbody.find('tr').filter(function() {
            return $(this).find('td[colspan]').length === 0 && !$(this).hasClass('no-data-row');
        });

        console.log('搜尋表格檢測到資料行數量:', dataRows.length);

        // 如果沒有資料行，不初始化 DataTable
        if (dataRows.length === 0) {
            console.log('搜尋表格無資料行，跳過 DataTable 初始化');
            return;
        }

        // 先銷毀已存在的 DataTable
        if ($.fn.DataTable.isDataTable('#ordersTableSearch')) {
            $table.DataTable().destroy();
        }

        // 檢查表格欄位數量
        const columnCount = $thead.find('th').length;
        console.log('搜尋表格檢測到欄位數量:', columnCount);

        // 確保欄位數量正確
        if (columnCount === 0) {
            console.error('搜尋表格欄位數量為 0，無法初始化 DataTable');
            return;
        }

        try {
            this.searchDataTable = $table.DataTable({
                language: {
                    lengthMenu: "每頁顯示 _MENU_ 筆資料",
                    zeroRecords: "查無資料",
                    info: "顯示第 _START_ 到 _END_ 筆，共 _TOTAL_ 筆資料",
                    infoEmpty: "目前沒有資料",
                    infoFiltered: "(從 _MAX_ 筆資料中篩選)",
                    search: "快速搜尋：",
                    paginate: {
                        first: "第一頁",
                        last: "最後一頁",
                        next: "下一頁",
                        previous: "上一頁"
                    }
                },
                pageLength: 100,
                order: [[4, 'asc'], [5, 'asc']], // 先依用車日期欄升序，再依用車時間欄升序
                columnDefs: [
                    { targets: [columnCount - 1], orderable: false } // 最後一欄（操作欄）不可排序
                ],
                responsive: true,
                searching: true,
                paging: true,
                info: true,
                autoWidth: false,
                destroy: true,
                drawCallback: function() {
                    console.log('搜尋表格 DataTable 繪製完成');
                }
            });

            console.log('搜尋表格 DataTable 初始化成功');
        } catch (error) {
            console.error('搜尋表格 DataTable 初始化失敗:', error);
        }
    }

    /**
     * 重新初始化主表格 (訂單模式)
     */
    reinitializeMainTable() {
        console.log('重新初始化主表格');
        this.initializeDataTable();
    }

    /**
     * 綁定事件
     */
    bindEvents() {
        // 搜尋表單驗證
        $('#customer-search form').on('submit', this.handleSearch.bind(this));

        // 清除搜尋按鈕
        $('#customer-search .btn-secondary').on('click', this.handleClearSearch.bind(this));

        // 新增訂單按鈕
        $('#createOrderBtn').on('click', this.handleCreateOrder.bind(this));
    }

    /**
     * 處理搜尋
     */
    handleSearch(e) {
        // 表單驗證
        const keyword = $('#keyword').val().trim();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (!keyword && !startDate && !endDate) {
            alert('請輸入搜尋條件');
            e.preventDefault();
            return false;
        }

        if (startDate && endDate && startDate > endDate) {
            alert('開始日期不能晚於結束日期');
            e.preventDefault();
            return false;
        }

        return true;
    }

    /**
     * 處理清除搜尋
     */
    handleClearSearch(e) {
        e.preventDefault();
        $('#keyword').val('');
        $('#start_date').val('');
        $('#end_date').val('');

        // 重新提交表單
        $('#customer-search form').submit();
    }

    /**
     * 處理新增訂單
     */
    handleCreateOrder(e) {
        // 檢查是否有搜尋過客戶
        const hasKeyword = this.checkSearchConditions();

        if (!hasKeyword) {
            e.preventDefault(); // 阻止導航

            // 顯示提示訊息
            this.showSearchRequiredAlert();

            return false;
        }

        // 如果有搜尋過客戶，允許正常導航
        console.log('導向新增訂單頁面');
        return true;
    }

    /**
     * 檢查搜尋條件
     */
    checkSearchConditions() {
        // 檢查 URL 參數
        const urlParams = new URLSearchParams(window.location.search);
        const hasKeywordParam = urlParams.has('keyword') && urlParams.get('keyword').trim() !== '';
        const hasCustomerIdParam = urlParams.has('customer_id') && urlParams.get('customer_id').trim() !== '';

        // 檢查搜尋輸入框
        const keywordInput = $('#keyword').val();
        const hasKeywordInput = keywordInput && keywordInput.trim() !== '';

        // 檢查是否有顯示客戶資料
        const hasCustomerCard = $('.card-header.bg-success').length > 0;

        // 檢查是否有搜尋結果列表
        const hasSearchResults = $('.list-group').length > 0 && $('.list-group .list-group-item').length > 0;

        return hasKeywordParam || hasCustomerIdParam || hasKeywordInput || hasCustomerCard || hasSearchResults;
    }

    /**
     * 顯示搜尋必要提示
     */
    showSearchRequiredAlert() {
        // 先移除現有的提示訊息
        $('.search-required-alert').remove();

        // 創建提示訊息
        const alertHtml = `
            <div class="alert alert-warning alert-dismissible fade show search-required-alert" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>請先搜尋客戶資料</strong>
                <p class="mb-0 mt-2">新增訂單前，請先使用上方的搜尋功能尋找客戶資料，以確保訂單能正確關聯到客戶。</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // 在客戶搜尋卡片後面插入提示
        $('.card.mb-4').after(alertHtml);

        // 滾動到提示訊息
        $('html, body').animate({
            scrollTop: $('.search-required-alert').offset().top - 100
        }, 500);

        // 聚焦到搜尋輸入框
        $('#keyword').focus();

        // 5秒後自動消失
        setTimeout(() => {
            $('.search-required-alert').alert('close');
        }, 5000);
    }
}

/**
 * 刪除訂單
 */
function deleteOrder(orderId) {
    if (confirm('確定要刪除這筆訂單嗎？此操作無法恢復！')) {
        // 顯示載入狀態
        const deleteBtn = document.querySelector(`button[onclick="deleteOrder(${orderId})"]`);
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteBtn.disabled = true;
        }

        $.ajax({
            url: `/orders/${orderId}`,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.success) {
                    // 顯示成功訊息
                    showSuccessMessage('訂單已成功刪除');

                    // 重新載入頁面
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showErrorMessage(response.message || '刪除失敗');
                    // 恢復按鈕狀態
                    if (deleteBtn) {
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                        deleteBtn.disabled = false;
                    }
                }
            },
            error: (xhr, status, error) => {
                console.error('刪除失敗:', error);
                showErrorMessage('刪除失敗，請稍後再試');
                // 恢復按鈕狀態
                if (deleteBtn) {
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.disabled = false;
                }
            }
        });
    }
}

/**
 * 顯示成功訊息
 */
function showSuccessMessage(message) {
    const alert = $(`
        <div class="alert alert-success alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 9999;">
            <i class="fas fa-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);

    $('body').append(alert);

    // 3秒後自動消失
    setTimeout(() => {
        alert.alert('close');
    }, 3000);
}

/**
 * 顯示錯誤訊息
 */
function showErrorMessage(message) {
    const alert = $(`
        <div class="alert alert-danger alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 9999;">
            <i class="fas fa-exclamation-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);

    $('body').append(alert);

    // 5秒後自動消失
    setTimeout(() => {
        alert.alert('close');
    }, 5000);
}

/**
 * 更新客戶備註
 */
function updateCustomerNote(customerId) {
    const noteValue = $(`#note${customerId}`).val();
    const updateBtn = $(`.modal-footer .btn-primary[onclick="updateCustomerNote(${customerId})"]`);

    // 顯示載入狀態
    const originalText = updateBtn.text();
    updateBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>儲存中...').prop('disabled', true);

    $.ajax({
        url: `/customers/${customerId}/note`,
        method: 'PATCH',
        data: {
            note: noteValue,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: (response) => {
            if (response.success) {
                // 更新頁面顯示的備註
                $(`#customer-note-${customerId}`).text(response.note || '無備註');

                // 關閉 modal
                $(`#noteModal${customerId}`).modal('hide');

                // 顯示成功訊息
                showSuccessMessage('備註已更新');
            } else {
                showErrorMessage(response.message || '更新失敗');
            }
        },
        error: (xhr, status, error) => {
            console.error('更新備註失敗:', error);
            let errorMessage = '更新失敗，請稍後再試';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                // Laravel 驗證錯誤
                const errors = Object.values(xhr.responseJSON.errors).flat();
                errorMessage = errors.join('，');
            }

            showErrorMessage(errorMessage);
        },
        complete: () => {
            // 恢復按鈕狀態
            updateBtn.text(originalText).prop('disabled', false);
        }
    });
}

// 初始化
$(document).ready(function() {
    // 確保 DataTable 正確初始化
    if (typeof $.fn.DataTable !== 'undefined') {
        console.log('DataTable 已載入');

        // 延遲初始化，確保 DOM 完全載入
        setTimeout(function() {
            new OrderIndex();
        }, 100);
    } else {
        console.error('DataTable 未載入，請檢查相關資源');
    }
});
