/**
 * 訂單表單頁面 JavaScript
 * 職責：表單驗證、共乘查詢、駕駛查詢、地標選擇
 */

/**
 * 標準化台灣地址（異體字轉換）
 * 將「臺」轉換為「台」
 * @param {string} address - 原始地址
 * @returns {string} - 標準化後的地址
 */
function normalizeAddress(address) {
    if (!address) return address;
    return address.replace(/臺/g, '台');
}

class OrderForm {
    constructor() {
        this.currentAddressType = ''; // 'pickup' 或 'dropoff'
        this.landmarkModal = null;
        this.selectedDates = [];
        this.recurringDates = [];
        this.datePicker = null;
        this.previewUpdateTimer = null;
        this.currentCategory = 'all'; // 地標分類篩選狀態
        this.datePickupWarningConfirmed = false; // 日期地點重複警告已確認標記
        this.init();
    }

    init() {
        this.initializeLandmarkModal();
        this.bindFormEvents();
        this.bindCarpoolEvents();
        this.bindDriverEvents();
        this.bindAddressEvents();
        this.initializeBatchFeatures();

        // 頁面載入時檢查日期與上車點重複（如果有預填資料）
        this.performInitialDatePickupCheck();

        // 監聽訂單類型變化，動態調整日期限制
        $('input[name="order_type"]').on('change input', this.updateDatePickerLimit.bind(this));

        // 頁面載入時執行一次日期限制更新
        this.updateDatePickerLimit();
    }

    /**
     * 初始化地標 Modal
     */
    initializeLandmarkModal() {
        const modalElement = document.getElementById('landmarkModal');
        if (modalElement) {
            this.landmarkModal = new bootstrap.Modal(modalElement);
            this.bindLandmarkEvents();
        }
    }

    /**
     * 綁定表單事件
     */
    bindFormEvents() {
        // 表單提交驗證
        $('.order-form').on('submit', this.handleFormSubmit.bind(this));

        // 即時驗證
        $('input[required]').on('blur', this.validateField.bind(this));

        // 數字輸入限制
        $('input[type="number"]').on('input', this.handleNumberInput.bind(this));

        // 時間格式驗證（保留原有的，但現在沒有 type="time" 欄位了）
        $('input[type="time"]').on('blur', this.validateTimeInput.bind(this));

        // 時間自動格式化
        $('.time-auto-format').on('input', this.handleTimeAutoFormat.bind(this));
        $('.time-auto-format').on('keydown', this.handleTimeKeydown.bind(this));

        // 歷史訂單功能
        $('#historyOrderBtn').on('click', this.handleHistoryOrderClick.bind(this));

        // 監聽客戶選擇，控制歷史訂單按鈕顯示
        this.checkHistoryOrderButtonVisibility();
        $('input[name="customer_id"]').on('change input', this.checkHistoryOrderButtonVisibility.bind(this));
        $('input[name="customer_name"]').on('change input', this.checkHistoryOrderButtonVisibility.bind(this));

        // 重複訂單檢查
        $('input[name="ride_date"], input[name="ride_time"]').on('change blur', this.checkDuplicateOrder.bind(this));

        // 日期與上車點重複檢查
        console.log('綁定日期與上車點事件');
        console.log('pickup_address 元素數量:', $('input[name="pickup_address"]').length);
        console.log('ride_date 元素數量:', $('input[name="ride_date"]').length);

        $('input[name="ride_date"], input[name="pickup_address"]').on('change blur', () => {
            console.log('日期或上車點輸入框事件觸發');
            this.checkDatePickupDuplicate();
        });

        // 訂單資訊複製功能
        $('.copyOrderInfoBtn').on('click', () => {
            console.log('複製訂單資訊按鈕被點擊');
            this.showOrderInfoModal();
        });

        // Modal 內的複製按鈕
        $('#copyToClipboardBtn').on('click', () => {
            console.log('複製到剪貼板按鈕被點擊');
            this.copyOrderInfoToClipboard();
        });

        // 去程複製按鈕
        $('#copyOutboundBtn').on('click', () => {
            console.log('複製去程資訊按鈕被點擊');
            this.copyOutboundToClipboard();
        });

        // 回程複製按鈕
        $('#copyReturnBtn').on('click', () => {
            console.log('複製回程資訊按鈕被點擊');
            this.copyReturnToClipboard();
        });

        // 複製完整資訊按鈕
        $('#copyAllBtn').on('click', () => {
            console.log('複製完整資訊按鈕被點擊');
            this.copyAllToClipboard();
        });

        // 今日按鈕功能
        $('#setTodayBtn').on('click', this.setTodayDate.bind(this));
    }

    /**
     * 綁定共乘相關事件
     */
    bindCarpoolEvents() {
        // 共乘搜尋
        $('#searchCarpoolBtn').on('click', this.handleCarpoolSearch.bind(this));

        // 清除共乘
        $('#clearCarpoolBtn').on('click', this.handleCarpoolClear.bind(this));

        // 共乘搜尋輸入框 Enter 鍵
        $('#carpoolSearchInput').on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleCarpoolSearch();
            }
        });

        // 監聽共乘姓名欄位變化
        $('#carpool_with').on('input change', this.handleCarpoolNameChange.bind(this));
    }

    /**
     * 綁定駕駛相關事件
     */
    bindDriverEvents() {
        // 去程駕駛搜尋
        $('#searchDriverBtn').on('click', () => this.handleDriverSearch('outbound'));

        // 去程駕駛清除
        $('#clearDriverBtn').on('click', () => this.handleDriverClear('outbound'));

        // 回程駕駛搜尋
        $('#searchReturnDriverBtn').on('click', () => this.handleDriverSearch('return'));

        // 回程駕駛清除
        $('#clearReturnDriverBtn').on('click', () => this.handleDriverClear('return'));

        // 複製去程駕駛到回程
        $('#copyOutboundDriverBtn').on('click', this.handleCopyOutboundDriver.bind(this));

        // 駕駛隊編輸入監聽
        $('#driver_fleet_number').on('input', this.handleDriverFleetInput.bind(this));
        $('#return_driver_fleet_number').on('input', this.handleReturnDriverFleetInput.bind(this));

        // 駕駛搜尋輸入框 Enter 鍵
        $('#driver_fleet_number').on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleDriverSearch('outbound');
            }
        });

        $('#return_driver_fleet_number').on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleDriverSearch('return');
            }
        });
    }

    /**
     * 綁定地址相關事件
     */
    bindAddressEvents() {
        // 地址交換
        $('#swapAddressBtn').on('click', this.handleAddressSwap.bind(this));

        // 地標輸入框星號觸發
        $('.landmark-input').on('input', this.handleLandmarkInput.bind(this));

        // 地址輸入即時標準化（將「臺」轉換為「台」）
        $('#pickup_address, #dropoff_address').on('input', function() {
            const normalized = normalizeAddress($(this).val());
            if (normalized !== $(this).val()) {
                const cursorPos = this.selectionStart; // 保存游標位置
                $(this).val(normalized);
                this.setSelectionRange(cursorPos, cursorPos); // 恢復游標位置
            }
        });

        // 【新增】地址變更時進行地址驗證
        $('#pickup_address, #dropoff_address').on('change blur', this.validateAddressByOrderType.bind(this));
    }

    /**
     * 綁定地標 Modal 事件
     */
    bindLandmarkEvents() {
        // 搜尋按鈕
        $('#searchLandmarkBtn').on('click', this.handleLandmarkSearch.bind(this));

        // 搜尋輸入框 Enter 鍵
        $('#landmarkSearchInput').on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleLandmarkSearch();
            }
        });

        // 分類篩選
        $('.category-filter').on('click', this.handleCategoryFilter.bind(this));

        // 分頁切換
        $('#popular-tab').on('click', this.loadPopularLandmarks.bind(this));
        $('#recent-tab').on('click', this.loadRecentLandmarks.bind(this));

        // Modal 顯示事件
        $('#landmarkModal').on('show.bs.modal', this.handleModalShow.bind(this));

        // Modal 隱藏事件
        $('#landmarkModal').on('hidden.bs.modal', this.handleModalHide.bind(this));
    }

    /**
     * 處理表單提交
     */
    handleFormSubmit(e) {
        // 先檢查是否有日期地點重複警告（只檢查警告類型，不包含成功提示）
        const hasDatePickupWarning = $('.date-pickup-warning.alert-warning').length > 0;

        if (hasDatePickupWarning && !this.datePickupWarningConfirmed) {
            e.preventDefault();

            // 彈出確認對話框
            if (confirm('系統偵測到可能的重複訂單（相同日期與上車地點），確定要繼續建立訂單嗎？')) {
                // 使用者確認後，設定標記並重新提交
                this.datePickupWarningConfirmed = true;
                $(e.target).submit();
            }
            return false;
        }

        if (!this.validateForm()) {
            e.preventDefault();
            return false;
        }

        // 顯示提交狀態
        const submitBtn = $(e.target).find('button[type="submit"]');
        submitBtn.prop('disabled', true)
                 .html('<i class="fas fa-spinner fa-spin me-2"></i>處理中...');

        return true;
    }

    /**
     * 驗證表單
     */
    validateForm() {
        let isValid = true;
        const errors = [];

        // 必填欄位驗證
        $('input[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                const label = $(this).closest('.col-md-2, .col-md-3, .col-md-4, .col-md-6, .col-12').find('label').text();
                errors.push(`${label} 為必填欄位`);
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // 日期驗證
        const rideDate = $('#ride_date').val();
        if (rideDate && new Date(rideDate) < new Date().setHours(0,0,0,0)) {
            isValid = false;
            errors.push('用車日期不能早於今天');
            $('#ride_date').addClass('is-invalid');
        }

        // 台北長照日期限制（14天內）
        const orderType = $('input[name="order_type"]').val();
        if (orderType === '台北長照' && rideDate) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const maxDate = new Date(today);
            maxDate.setDate(maxDate.getDate() + 14);
            const selectedDate = new Date(rideDate);
            selectedDate.setHours(0, 0, 0, 0);

            if (selectedDate > maxDate) {
                isValid = false;
                errors.push('台北長照訂單的用車日期僅能建立 14 天內（含今天）');
                $('#ride_date').addClass('is-invalid');
            }
        }

        // 時間驗證
        const rideTime = $('#ride_time').val();
        const backTime = $('#back_time').val();
        if (rideTime && backTime && rideTime >= backTime) {
            isValid = false;
            errors.push('回程時間必須晚於用車時間');
            $('#back_time').addClass('is-invalid');
        }

        // 地址驗證
        const pickupAddress = $('#pickup_address').val();
        const dropoffAddress = $('#dropoff_address').val();
        if (pickupAddress && dropoffAddress && pickupAddress === dropoffAddress) {
            isValid = false;
            errors.push('上車地址和下車地址不能相同');
            $('#dropoff_address').addClass('is-invalid');
        }

        // 長照地址限制驗證（orderType 已在上方宣告過）
        if (orderType === '新北長照') {
            const hasNewTaipei = pickupAddress.startsWith('新北市') || dropoffAddress.startsWith('新北市');
            if (!hasNewTaipei) {
                isValid = false;
                errors.push('新北長照訂單的上車或下車地址至少一個必須位於新北市');
                $('#pickup_address').addClass('is-invalid');
                $('#dropoff_address').addClass('is-invalid');
            }
        } else if (orderType === '台北長照') {
            const hasTaipei = pickupAddress.startsWith('台北市') || dropoffAddress.startsWith('台北市');
            if (!hasTaipei) {
                isValid = false;
                errors.push('台北長照訂單的上車或下車地址至少一個必須位於台北市');
                $('#pickup_address').addClass('is-invalid');
                $('#dropoff_address').addClass('is-invalid');
            }
        }

        // 顯示錯誤訊息
        if (!isValid) {
            this.showErrorMessages(errors);
        }

        return isValid;
    }

    /**
     * 根據訂單類型動態調整日期選擇器的最大日期限制
     * 台北長照訂單僅能選擇 14 天內的日期
     */
    updateDatePickerLimit() {
        const orderType = $('input[name="order_type"]').val();
        const dateInput = $('#ride_date');

        if (orderType === '台北長照') {
            // 計算今天 + 14 天
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const maxDate = new Date(today);
            maxDate.setDate(maxDate.getDate() + 14);

            // 格式化為 YYYY-MM-DD
            const year = maxDate.getFullYear();
            const month = String(maxDate.getMonth() + 1).padStart(2, '0');
            const day = String(maxDate.getDate()).padStart(2, '0');
            const maxDateString = `${year}-${month}-${day}`;

            dateInput.attr('max', maxDateString);

            // 如果已選日期超過限制，清空並提示
            const currentValue = dateInput.val();
            if (currentValue && new Date(currentValue) > maxDate) {
                dateInput.val('');
                alert('⚠️ 台北長照訂單僅能建立 14 天內的日期，已清空您的選擇，請重新選擇');
            }
        } else {
            // 移除限制
            dateInput.removeAttr('max');
        }
    }

    /**
     * 處理共乘搜尋
     */
    handleCarpoolSearch() {
        const keyword = $('#carpoolSearchInput').val().trim();
        if (!keyword) {
            alert('請輸入搜尋關鍵字');
            return;
        }

        $('#carpoolResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');

        fetch(`/carpool-search?keyword=${encodeURIComponent(keyword)}`)
            .then(response => response.json())
            .then(data => {
                this.displayCarpoolResults(data);
            })
            .catch(error => {
                console.error('查詢錯誤：', error);
                $('#carpoolResults').html('<div class="alert alert-danger">搜尋失敗，請稍後再試</div>');
            });
    }

    /**
     * 顯示共乘搜尋結果
     */
    displayCarpoolResults(data) {
        if (data.length === 0) {
            $('#carpoolResults').html('<div class="alert alert-warning">查無相符的客戶資料</div>');
            return;
        }

        if (data.length === 1 && data[0].id_number === $('#carpoolSearchInput').val()) {
            // 精確匹配，直接填入
            this.selectCarpoolCustomer(data[0]);
            return;
        }

        // 顯示選擇列表
        let html = '<div class="list-group">';
        data.forEach(customer => {
            html += `
                <div class="list-group-item list-group-item-action">
                    <div class="justify-content-between align-items-center">
                        <div class="row">
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary" onclick="orderForm.selectCarpoolCustomer(${JSON.stringify(customer).replace(/"/g, '&quot;')})">
                                    選擇
                                </button>
                            </div>
                            <div class="col-md-11">
                                <strong>${customer.name}</strong> / ${customer.id_number}<br>
                                <small class="text-muted">${customer.phone_number} / ${customer.addresses}</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        $('#carpoolResults').html(html);
    }

    /**
     * 選擇共乘客戶
     */
    selectCarpoolCustomer(customer) {
        $('#carpool_with').val(customer.name);
        $('#carpool_id_number').val(customer.id_number);
        $('#carpool_phone_number').val(Array.isArray(customer.phone_number) ? customer.phone_number[0] : customer.phone_number);
        $('#carpool_addresses').val(Array.isArray(customer.addresses) ? customer.addresses[0] : customer.addresses);
        $('#carpool_customer_id').val(customer.id);
        $('#carpoolResults').empty();

        // 自動設定特殊狀態為共乘單
        $('select[name="special_status"]').val('共乘單');

        // 顯示成功訊息
        this.showSuccessMessage('已選擇共乘客戶：' + customer.name + '，特殊狀態已自動設為共乘單');
    }

    /**
     * 清除共乘資料
     */
    handleCarpoolClear() {
        $('#carpoolSearchInput').val('');
        $('#carpool_with').val('');
        $('#carpool_id_number').val('');
        $('#carpool_phone_number').val('');
        $('#carpool_addresses').val('');
        $('#carpool_customer_id').val('');
        $('#carpoolResults').empty();

        // 自動重設特殊狀態為一般
        $('select[name="special_status"]').val('一般');
    }

    /**
     * 處理共乘姓名欄位變化
     */
    handleCarpoolNameChange() {
        const carpoolName = $('#carpool_with').val().trim();

        if (carpoolName) {
            // 有共乘姓名，設定為共乘單
            $('select[name="special_status"]').val('共乘單');
        } else {
            // 無共乘姓名，設定為一般
            $('select[name="special_status"]').val('一般');
        }
    }

    /**
     * 處理駕駛搜尋
     */
    handleDriverSearch(type = 'outbound') {
        const prefix = type === 'return' ? 'return_' : '';
        const fleetNumber = $(`#${prefix}driver_fleet_number`).val().trim();

        if (!fleetNumber) {
            alert('請輸入駕駛隊編');
            return;
        }

        fetch(`/drivers/fleet-search?fleet_number=${encodeURIComponent(fleetNumber)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    $(`#${prefix}driver_id`).val(data.id);
                    $(`#${prefix}driver_name`).val(data.name);
                    $(`#${prefix}driver_plate_number`).val(data.plate_number);

                    // 只有去程駕駛時才自動設定為已指派
                    if (type === 'outbound') {
                        $('select[name="status"]').val('assigned');
                    }

                    const driverType = type === 'return' ? '回程' : '去程';
                    this.showSuccessMessage(`已找到${driverType}駕駛：` + data.name);
                }
            })
            .catch(() => {
                alert('查詢失敗，請稍後再試');
            });
    }

    /**
     * 清除駕駛資料
     */
    handleDriverClear(type = 'outbound') {
        const prefix = type === 'return' ? 'return_' : '';

        $(`#${prefix}driver_fleet_number`).val('');
        $(`#${prefix}driver_id`).val('');
        $(`#${prefix}driver_name`).val('');
        $(`#${prefix}driver_plate_number`).val('');

        // 只有清除去程駕駛時才恢復為可派遣狀態
        if (type === 'outbound') {
            $('select[name="status"]').val('open');
        }

        const driverType = type === 'return' ? '回程' : '去程';
        this.showSuccessMessage(`${driverType}駕駛資料已清除`);
    }

    /**
     * 複製去程駕駛到回程
     */
    handleCopyOutboundDriver() {
        const outboundDriverId = $('#driver_id').val();
        const outboundDriverName = $('#driver_name').val();
        const outboundDriverPlate = $('#driver_plate_number').val();
        const outboundDriverFleet = $('#driver_fleet_number').val();

        if (!outboundDriverFleet && !outboundDriverName) {
            alert('請先填入去程駕駛資訊');
            return;
        }

        $('#return_driver_id').val(outboundDriverId);
        $('#return_driver_name').val(outboundDriverName);
        $('#return_driver_plate_number').val(outboundDriverPlate);
        $('#return_driver_fleet_number').val(outboundDriverFleet);

        this.showSuccessMessage('已複製去程駕駛至回程');
    }

    /**
     * 處理駕駛隊編輸入
     */
    handleDriverFleetInput(e) {
        const fleetNumber = e.target.value.trim();
        const statusSelect = $('select[name="status"]');

        if (fleetNumber) {
            statusSelect.val('assigned');
        } else {
            statusSelect.val('open');
        }
    }

    /**
     * 處理回程駕駛隊編輸入
     */
    handleReturnDriverFleetInput(e) {
        // 回程駕駛輸入不影響訂單狀態，只是清除相關駕駛資訊
        const fleetNumber = e.target.value.trim();
        if (!fleetNumber) {
            $('#return_driver_id').val('');
            $('#return_driver_name').val('');
            $('#return_driver_plate_number').val('');
        }
    }

    /**
     * 處理地址交換
     */
    handleAddressSwap() {
        const pickupAddress = $('#pickup_address').val();
        const dropoffAddress = $('#dropoff_address').val();

        $('#pickup_address').val(dropoffAddress);
        $('#dropoff_address').val(pickupAddress);

        // 交換地標 ID
        const pickupLandmarkId = $('#pickup_address').attr('data-landmark-id');
        const dropoffLandmarkId = $('#dropoff_address').attr('data-landmark-id');

        $('#pickup_address').attr('data-landmark-id', dropoffLandmarkId || '');
        $('#dropoff_address').attr('data-landmark-id', pickupLandmarkId || '');

        /**this.showSuccessMessage('已交換上下車地址'); 目前是關掉成功提示**/
    }

    /**
     * 設定用車日期為今天
     */
    setTodayDate() {
        const today = new Date().toISOString().split('T')[0]; // 格式：YYYY-MM-DD
        const rideDateInput = $('#ride_date');

        rideDateInput.val(today);

        // 觸發 change 事件，執行相關驗證
        rideDateInput.trigger('change');

        // 視覺回饋
        rideDateInput.addClass('highlight-change');
        setTimeout(() => {
            rideDateInput.removeClass('highlight-change');
        }, 600);
    }

    /**
     * 處理地標輸入
     */
    handleLandmarkInput(e) {
        const inputValue = e.target.value;
        if (inputValue.includes('*')) {
            const keyword = inputValue.replace('*', '').trim();
            e.target.value = keyword;

            // 判斷地址類型
            this.currentAddressType = e.target.name === 'pickup_address' ? 'pickup' : 'dropoff';

            // 開啟地標 Modal
            this.openLandmarkModal();

            // 自動搜尋
            setTimeout(() => {
                $('#landmarkSearchInput').val(keyword);
                this.handleLandmarkSearch();
            }, 300);
        }
    }

    /**
     * 開啟地標 Modal
     */
    openLandmarkModal(addressType = null) {
        if (addressType) {
            this.currentAddressType = addressType;
        }

        if (this.landmarkModal) {
            this.landmarkModal.show();
        }
    }

    /**
     * 處理 Modal 顯示
     */
    handleModalShow() {
        // 設定標題
        const title = this.currentAddressType === 'pickup' ? '選擇上車地標' : '選擇下車地標';
        const color = this.currentAddressType === 'pickup' ? 'bg-success' : 'bg-danger';

        $('#landmarkModalHeader').removeClass('bg-success bg-danger').addClass(color);
        $('#landmarkModalLabel').text(title);

        // 清空搜尋
        $('#landmarkSearchInput').val('');
        $('#landmarkSearchResults').html('<div class="text-center py-4"><p class="text-muted">請輸入關鍵字搜尋地標</p></div>');

        // 重設到搜尋頁面
        $('#search-tab').tab('show');
    }

    /**
     * 處理 Modal 隱藏
     */
    handleModalHide() {
        this.currentAddressType = '';
    }

    /**
     * 處理地標搜尋
     */
    handleLandmarkSearch(page = 1, category = null) {
        const keyword = $('#landmarkSearchInput').val().trim();
        if (!keyword) {
            alert('請輸入搜尋關鍵字');
            return;
        }

        // 如果沒有指定分類，使用當前分類
        if (category === null) {
            category = this.currentCategory;
        }

        $('#landmarkSearchResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');
        $('#landmarkPagination').hide();

        let url = `/landmarks-search?keyword=${encodeURIComponent(keyword)}&page=${page}`;
        if (category && category !== 'all') {
            url += `&category=${encodeURIComponent(category)}`;
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.data && data.data.data.length > 0) {
                    this.displayLandmarkResults(data.data.data, '#landmarkSearchResults');
                    this.displayLandmarkPagination(data.data);
                } else {
                    $('#landmarkSearchResults').html('<div class="text-center py-4"><p class="text-muted">查無符合條件的地標</p></div>');
                    $('#landmarkPagination').hide();
                }
            })
            .catch(error => {
                console.error('搜尋地標錯誤:', error);
                $('#landmarkSearchResults').html('<div class="alert alert-danger">搜尋失敗，請稍後再試</div>');
                $('#landmarkPagination').hide();
            });
    }

    /**
     * 顯示地標搜尋結果
     */
    displayLandmarkResults(landmarks, container) {
        let html = '';

        landmarks.forEach(landmark => {
            const fullAddress = `${landmark.city}${landmark.district}${landmark.address}`;
            const categoryBadge = this.getCategoryBadge(landmark.category);
            const categoryIcon = this.getCategoryIcon(landmark.category);

            html += `
                <div class="landmark-item border rounded-3 mb-2 p-3" style="cursor: pointer;"
                     data-category="${landmark.category}"
                     onclick="orderForm.selectLandmark('${fullAddress}', ${landmark.id}, '${landmark.name.replace(/'/g, "\\'")}')">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="${categoryIcon} text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h5 class="mb-1">${landmark.name}：${fullAddress}</h5>
                                <div>${categoryBadge}</div>
                            </div>
                            <!--<p class="text-muted mb-0" style="font-size: 20px;">${fullAddress}</p>-->
                        </div>
                    </div>
                </div>
            `;
        });

        $(container).html(html);
    }

    /**
     * 顯示地標搜尋分頁
     */
    displayLandmarkPagination(paginationData) {
        const { current_page, last_page, per_page, total } = paginationData;

        if (last_page <= 1) {
            $('#landmarkPagination').hide();
            return;
        }

        let paginationHtml = '';

        // 上一頁
        if (current_page > 1) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="orderForm.handleLandmarkSearch(${current_page - 1}); return false;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        // 頁碼
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(last_page, current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === current_page ? 'active' : '';
            paginationHtml += `
                <li class="page-item ${activeClass}">
                    <a class="page-link" href="#" onclick="orderForm.handleLandmarkSearch(${i}); return false;">${i}</a>
                </li>
            `;
        }

        // 下一頁
        if (current_page < last_page) {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="orderForm.handleLandmarkSearch(${current_page + 1}); return false;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
        }

        $('#landmarkPaginationList').html(paginationHtml);

        // 清除舊的資訊並顯示新的總數資訊
        $('#landmarkPagination').find('.pagination-info').remove();
        const info = `<small class="text-muted">共 ${total} 個地標，第 ${current_page}/${last_page} 頁</small>`;
        $('#landmarkPagination').prepend(`<div class="text-center mb-2 pagination-info">${info}</div>`);

        $('#landmarkPagination').show();
    }

    /**
     * 選擇地標
     */
    selectLandmark(address, landmarkId, landmarkName) {
        const targetInput = $(`#${this.currentAddressType}_address`);
        const fullAddress = `${address}(${landmarkName})`;
        targetInput.val(fullAddress);
        targetInput.attr('data-landmark-id', landmarkId);

        // 手動觸發 change 事件，以執行地址驗證
        targetInput.trigger('change');

        // 關閉 Modal
        this.landmarkModal.hide();

        // 更新使用次數
        this.updateLandmarkUsage(landmarkId);

        // 保存到最近使用
        this.saveToRecentLandmarks(landmarkId, fullAddress);

        this.showSuccessMessage('已選擇地標：' + fullAddress);
    }

    /**
     * 更新地標使用次數
     */
    updateLandmarkUsage(landmarkId) {
        fetch('/landmarks-usage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ landmark_id: landmarkId })
        }).catch(console.error);
    }

    /**
     * 保存到最近使用
     */
    saveToRecentLandmarks(landmarkId, address) {
        let recent = JSON.parse(localStorage.getItem('recentLandmarks') || '[]');

        // 移除重複
        recent = recent.filter(item => item.id !== landmarkId);

        // 添加到開頭
        recent.unshift({
            id: landmarkId,
            address: address,
            timestamp: Date.now()
        });

        // 只保留最近 20 個
        recent = recent.slice(0, 20);

        localStorage.setItem('recentLandmarks', JSON.stringify(recent));
    }

    /**
     * 載入熱門地標
     */
    loadPopularLandmarks() {
        $('#landmarkPopularResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');

        fetch('/landmarks-popular')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data.length > 0) {
                    this.displayLandmarkResults(data.data, '#landmarkPopularResults');
                } else {
                    $('#landmarkPopularResults').html('<div class="text-center py-4"><p class="text-muted">暫無熱門地標</p></div>');
                }
            })
            .catch(error => {
                console.error('熱門地標載入錯誤:', error);
                $('#landmarkPopularResults').html(`<div class="alert alert-danger">載入失敗: ${error.message}</div>`);
            });
    }

    /**
     * 載入最近使用地標
     */
    loadRecentLandmarks() {
        const recent = JSON.parse(localStorage.getItem('recentLandmarks') || '[]');

        if (recent.length === 0) {
            $('#landmarkRecentResults').html('<div class="text-center py-4"><p class="text-muted">暫無最近使用記錄</p></div>');
            return;
        }

        $('#landmarkRecentResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');

        const landmarkIds = recent.map(item => item.id);

        fetch('/landmarks-by-ids', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ ids: landmarkIds })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data.length > 0) {
                    this.displayLandmarkResults(data.data, '#landmarkRecentResults');
                } else {
                    $('#landmarkRecentResults').html('<div class="text-center py-4"><p class="text-muted">無法載入最近使用記錄</p></div>');
                }
            })
            .catch(error => {
                console.error('最近使用地標載入錯誤:', error);
                $('#landmarkRecentResults').html(`<div class="alert alert-danger">載入失敗: ${error.message}</div>`);
            });
    }

    /**
     * 處理分類篩選
     */
    handleCategoryFilter(e) {
        const category = e.target.dataset.category;
        const button = e.target;

        // 保存當前分類狀態
        this.currentCategory = category;

        // 更新按鈕狀態
        $('.category-filter').removeClass('active');
        $(button).addClass('active');

        // 如果有關鍵字，重新搜尋該分類；否則不執行任何操作
        const keyword = $('#landmarkSearchInput').val().trim();
        if (keyword) {
            this.handleLandmarkSearch(1, category);
        }
    }

    /**
     * 套用分類篩選
     */
    applyCategoryFilter(category) {
        const allItems = $('.landmark-item');

        if (category === 'all') {
            allItems.show();
        } else {
            allItems.each(function() {
                const itemCategory = $(this).data('category');
                if (itemCategory === category) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    }

    /**
     * 獲取分類標籤
     */
    getCategoryBadge(category) {
        const categories = {
            'hospital': { text: '醫院', class: 'bg-danger' },
            'clinic': { text: '診所', class: 'bg-warning' },
            'transport': { text: '交通', class: 'bg-primary' },
            'education': { text: '教育', class: 'bg-success' },
            'government': { text: '政府', class: 'bg-warning' },
            'commercial': { text: '商業', class: 'bg-info' }
        };

        const cat = categories[category] || { text: '一般', class: 'bg-secondary' };
        return `<span class="badge ${cat.class}">${cat.text}</span>`;
    }

    /**
     * 獲取分類圖標
     */
    getCategoryIcon(category) {
        const icons = {
            'hospital': 'fas fa-hospital',
            'clinic': 'fas fa-clinic-medical',
            'transport': 'fas fa-bus',
            'education': 'fas fa-school',
            'government': 'fas fa-building',
            'commercial': 'fas fa-store'
        };

        return icons[category] || 'fas fa-map-marker-alt';
    }

    /**
     * 顯示成功訊息
     */
    showSuccessMessage(message) {
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
    showErrorMessages(errors) {
        const errorHtml = errors.map(error => `<li>${error}</li>`).join('');
        const alert = $(`
            <div class="alert alert-danger alert-dismissible fade show">
                <h6>請修正以下錯誤：</h6>
                <ul class="mb-0">${errorHtml}</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

        // 在表單頂部顯示錯誤訊息
        $('.order-form').prepend(alert);

        // 滾動到頂部
        $('html, body').animate({ scrollTop: 0 }, 500);
    }

    /**
     * 欄位驗證
     */
    validateField(e) {
        const field = $(e.target);
        const value = field.val().trim();

        if (field.prop('required') && !value) {
            field.addClass('is-invalid');
        } else {
            field.removeClass('is-invalid');
        }
    }

    /**
     * 處理數字輸入
     */
    handleNumberInput(e) {
        const field = $(e.target);
        const value = parseInt(field.val());
        const min = parseInt(field.attr('min') || 0);
        const max = parseInt(field.attr('max') || 999);

        if (value < min) {
            field.val(min);
        } else if (value > max) {
            field.val(max);
        }
    }

    /**
     * 驗證時間輸入
     */
    validateTimeInput(e) {
        const field = $(e.target);
        const value = field.val();

        if (value && !/^([01]\d|2[0-3]):[0-5]\d$/.test(value)) {
            field.addClass('is-invalid');
        } else {
            field.removeClass('is-invalid');
        }
    }

    /**
     * 處理時間自動格式化
     */
    handleTimeAutoFormat(e) {
        const input = e.target;
        let value = input.value.replace(/[^\d]/g, ''); // 只保留數字

        // 限制最多4位數字
        if (value.length > 4) {
            value = value.substring(0, 4);
        }

        let formattedValue = '';

        if (value.length >= 1) {
            let hours = value.substring(0, 2);
            let minutes = value.substring(2, 4);

            // 處理小時部分
            if (value.length === 1) {
                // 第一位數字如果是3-9，自動補0
                if (parseInt(value) >= 3) {
                    hours = '0' + value;
                    formattedValue = hours + ':';
                    // 設定游標位置到冒號後
                    setTimeout(() => {
                        input.setSelectionRange(3, 3);
                    }, 0);
                } else {
                    formattedValue = value;
                }
            } else if (value.length === 2) {
                // 驗證小時範圍
                let hourNum = parseInt(hours);
                if (hourNum > 23) {
                    hours = '23';
                }
                formattedValue = hours + ':';
                // 設定游標位置到冒號後
                setTimeout(() => {
                    input.setSelectionRange(3, 3);
                }, 0);
            } else if (value.length >= 3) {
                // 處理分鐘部分
                let hourNum = parseInt(hours);
                if (hourNum > 23) {
                    hours = '23';
                }

                if (minutes.length === 1) {
                    formattedValue = hours + ':' + minutes;
                } else {
                    let minNum = parseInt(minutes);
                    if (minNum > 59) {
                        minutes = '59';
                    }
                    formattedValue = hours + ':' + minutes;
                }
            }
        }

        input.value = formattedValue;
    }

    /**
     * 處理時間輸入的特殊按鍵
     */
    handleTimeKeydown(e) {
        const input = e.target;

        // 允許的按鍵：數字、退格鍵、刪除鍵、Tab、方向鍵
        const allowedKeys = [
            'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
            'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'
        ];

        // 如果是允許的按鍵或者是數字鍵，則允許
        if (allowedKeys.includes(e.key) ||
            (e.key >= '0' && e.key <= '9') ||
            (e.ctrlKey || e.metaKey)) { // 允許 Ctrl+A, Ctrl+C 等

            // 處理退格鍵
            if (e.key === 'Backspace') {
                const cursorPos = input.selectionStart;
                const value = input.value;

                // 如果游標在冒號後且冒號前是完整的小時，刪除冒號和前一位數字
                if (cursorPos === 3 && value.length === 3 && value[2] === ':') {
                    e.preventDefault();
                    input.value = value.substring(0, 1);
                    input.setSelectionRange(1, 1);
                }
            }

            return;
        }

        // 阻止其他按鍵
        e.preventDefault();
    }

    /**
     * 處理歷史訂單按鈕點擊
     */
    handleHistoryOrderClick() {
        const customerId = $('input[name="customer_id"]').val();

        if (!customerId) {
            alert('請先選擇客戶');
            return;
        }

        // 顯示 Modal
        const modal = new bootstrap.Modal(document.getElementById('historyOrderModal'));
        modal.show();

        // 載入歷史訂單
        this.loadHistoryOrders(customerId);
    }

    /**
     * 載入客戶歷史訂單
     */
    loadHistoryOrders(customerId) {
        // 顯示載入狀態
        $('#historyOrderLoading').show();
        $('#historyOrderContent').hide();
        $('#historyOrderEmpty').hide();
        $('#historyOrderError').hide();

        // AJAX 請求歷史訂單
        $.ajax({
            url: `/customers/${customerId}/history-orders`,
            method: 'GET',
            success: (response) => {
                this.renderHistoryOrders(response);
            },
            error: (xhr) => {
                console.error('載入歷史訂單失敗:', xhr);
                $('#historyOrderLoading').hide();
                $('#historyOrderError').show();
            }
        });
    }

    /**
     * 渲染歷史訂單列表
     */
    renderHistoryOrders(orders) {
        $('#historyOrderLoading').hide();

        if (orders.length === 0) {
            $('#historyOrderEmpty').show();
            return;
        }

        let html = '';
        orders.forEach(order => {
            const statusClass = `status-${order.status}`;
            const statusText = this.getStatusText(order.status);

            html += `
                <tr class="history-order-row">
                    <td>${this.formatDate(order.ride_date)}</td>
                    <td>${order.ride_time ? order.ride_time.substring(0, 5) : '-'}</td>
                    <td>${(order.customer_phone)}</td>
                    <td>
                        <small class="h6" title="${order.pickup_address}">
                            ${this.truncateText(`${order.pickup_address}`, 30)}
                        </small>
                    </td>
                    <td>
                        <small class="h6" title="${order.dropoff_address}">
                            ${this.truncateText(`${order.dropoff_address}`, 30)}
                        </small>
                    </td>
                    <td class="text-center">${order.companions || 0}</td>
                    <td class="text-center">${order.wheelchair }</td>
                    <td class="text-center">${order.stair_machine}</td>
                    <td>
                        <span class="badge status-badge ${statusClass}">${statusText}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm"
                                onclick="orderForm.selectHistoryOrder(${order.id})">
                            <i class="fas fa-check me-1"></i>選擇
                        </button>
                    </td>
                </tr>
            `;
        });

        $('#historyOrderList').html(html);
        $('#historyOrderContent').show();
    }

    /**
     * 選擇歷史訂單並填入表單
     */
    selectHistoryOrder(orderId) {
        // 從當前顯示的列表中找到對應的訂單
        const customerId = $('input[name="customer_id"]').val();

        $.ajax({
            url: `/customers/${customerId}/history-orders`,
            method: 'GET',
            success: (orders) => {
                const selectedOrder = orders.find(order => order.id === orderId);
                if (selectedOrder) {
                    this.fillFormWithHistoryOrder(selectedOrder);

                    // 關閉 Modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('historyOrderModal'));
                    modal.hide();
                }
            }
        });
    }

    /**
     * 用歷史訂單資料填入表單
     */
    fillFormWithHistoryOrder(order) {
        // 填入時間欄位
        if (order.ride_time) {
            const timeInput = $('input[name="ride_time"]');
            timeInput.val(order.ride_time);
            // 觸發自動格式化
            timeInput.trigger('input');
        }

        // 填入電話欄位
        if (order.customer_phone) {
            $('input[name="customer_phone"]').val(order.customer_phone);
        }

        // 填入其他欄位
        $('input[name="companions"]').val(order.companions || 0);
        $('select[name="wheelchair"]').val(order.wheelchair);
        $('select[name="stair_machine"]').val(order.stair_machine);

        // 填入地址欄位（直接使用完整地址）
        if (order.pickup_address) {
            $('input[name="pickup_address"]').val(order.pickup_address);
        }

        if (order.dropoff_address) {
            $('input[name="dropoff_address"]').val(order.dropoff_address);
        }

        // 顯示成功訊息
        this.showSuccessMessage('已成功填入歷史訂單資料');
    }

    /**
     * 顯示成功訊息
     */
    showSuccessMessage(message) {
        // 創建臨時成功訊息
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // 在表單頂部插入訊息
        $('.order-form').prepend(alertHtml);

        // 3秒後自動移除
        setTimeout(() => {
            $('.alert-success').fadeOut(() => {
                $('.alert-success').remove();
            });
        }, 3000);
    }

    /**
     * 格式化日期
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('zh-TW', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }

    /**
     * 截斷文字
     */
    truncateText(text, maxLength) {
        if (!text) return '-';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    /**
     * 取得狀態文字
     */
    getStatusText(status) {
        const statusMap = {
            'open': '可派遣',
            'assigned': '已指派',
            'replacement': '候補',
            'cancelled': '已取消',
            'no_send': '不派遣',
            'cancelledOOC': '9999',
            'cancelledNOC': '取消！',
            'cancelledCOTD': '取消 X',
            'blacklist': '黑名單'
        };
        return statusMap[status] || status;
    }

    /**
     * 檢查歷史訂單按鈕是否應該顯示
     */
    checkHistoryOrderButtonVisibility() {
        const customerId = $('input[name="customer_id"]').val();
        const customerName = $('input[name="customer_name"]').val();

        if (customerId && customerName) {
            $('#historyOrderBtn').show();
        } else {
            $('#historyOrderBtn').hide();
        }
    }

    /**
     * 檢查重複訂單
     */
    checkDuplicateOrder() {
        const customerId = $('input[name="customer_id"]').val();
        const rideDate = $('input[name="ride_date"]').val();
        const rideTime = $('input[name="ride_time"]').val();

        // 檢查必要欄位是否已填寫
        if (!customerId || !rideDate || !rideTime) {
            this.clearDuplicateWarning();
            return;
        }

        // 檢查時間格式是否正確
        if (!/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(rideTime)) {
            this.clearDuplicateWarning();
            return;
        }

        // 取得當前訂單 ID（編輯模式用）
        const orderId = window.location.pathname.includes('/edit') ?
            window.location.pathname.split('/').slice(-2, -1)[0] : null;

        // 發送 AJAX 請求檢查重複
        $.ajax({
            url: '/orders/check-duplicate',
            method: 'POST',
            data: {
                customer_id: customerId,
                ride_date: rideDate,
                ride_time: rideTime,
                order_id: orderId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.isDuplicate) {
                    this.showDuplicateWarning(response.message, response.existingOrder);
                } else {
                    this.showDuplicateSuccess(response.message);
                }
            },
            error: (xhr) => {
                console.error('檢查重複訂單時發生錯誤:', xhr);
                this.clearDuplicateWarning();
            }
        });
    }

    /**
     * 檢查日期與上車點重複
     */
    checkDatePickupDuplicate() {
        console.log('=== 開始檢查日期與上車點重複 ===');

        const customerId = $('input[name="customer_id"]').val();
        const rideDate = $('input[name="ride_date"]').val();
        const pickupAddress = $('input[name="pickup_address"]').val();

        console.log('檢查參數:', {
            customerId: customerId,
            rideDate: rideDate,
            pickupAddress: pickupAddress
        });

        // 檢查必要欄位是否已填寫
        if (!customerId || !rideDate || !pickupAddress) {
            console.log('缺少必要欄位，清除警告');
            this.clearDatePickupWarning();
            return;
        }

        // 檢查地址是否有基本內容
        if (pickupAddress.trim().length < 3) {
            console.log('地址內容太短，清除警告');
            this.clearDatePickupWarning();
            return;
        }

        // 取得當前訂單 ID（編輯模式用）
        const orderId = window.location.pathname.includes('/edit') ?
            window.location.pathname.split('/').slice(-2, -1)[0] : null;

        console.log('準備發送 AJAX 請求:', {
            url: '/orders/check-date-pickup-duplicate',
            orderId: orderId
        });

        // 發送 AJAX 請求檢查重複
        $.ajax({
            url: '/orders/check-date-pickup-duplicate',
            method: 'POST',
            data: {
                customer_id: customerId,
                ride_date: rideDate,
                pickup_address: pickupAddress,
                order_id: orderId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                console.log('AJAX 請求成功，回應:', response);
                if (response.isDuplicate) {
                    console.log('發現重複，顯示警告');
                    this.showDatePickupWarning(response.message, response.existingOrder);
                } else {
                    console.log('無重複，顯示成功');
                    this.showDatePickupSuccess(response.message);
                }
            },
            error: (xhr) => {
                console.error('檢查日期地點重複時發生錯誤:', xhr);
                console.error('錯誤詳細:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText
                });
                this.clearDatePickupWarning();
            }
        });
    }

    /**
     * 顯示重複訂單警告
     */
    showDuplicateWarning(message, existingOrder) {
        this.clearDuplicateWarning();

        const rideTimeInput = $('input[name="ride_time"]');
        const warningHtml = `
            <div class="duplicate-warning alert alert-warning mt-2" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>重複訂單提醒：</strong>${message}
                        ${existingOrder ? `
                        <br><small class="text-muted">
                            上車：${existingOrder.pickup_address} → 下車：${existingOrder.dropoff_address}
                            <br>建立時間：${existingOrder.created_at}
                        </small>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        const errormsgInput = $('select[name="stair_machine"]'); //顯示在.....

        errormsgInput.parent().after(warningHtml);
        rideTimeInput.addClass('is-invalid');
    }

    /**
     * 顯示時間可用提示
     */
    showDuplicateSuccess(message) {
        this.clearDuplicateWarning();

        const okmsgInput = $('select[name="stair_machine"]'); //顯示在.....
        const rideTimeInput = $('input[name="ride_time"]');
        const successHtml = `
            <div class="duplicate-warning alert alert-success mt-2" role="alert">
                <i class="fas fa-check me-2"></i>${message}
            </div>
        `;

        okmsgInput.parent().after(successHtml);
        rideTimeInput.removeClass('is-invalid').addClass('is-valid');

        // 3秒後自動隱藏成功提示
        setTimeout(() => {
            $('.duplicate-warning.alert-success').fadeOut(300);
            rideTimeInput.removeClass('is-valid');
        }, 3000);
    }

    /**
     * 清除重複訂單警告
     */
    clearDuplicateWarning() {
        $('.duplicate-warning').remove();
        $('input[name="ride_time"]').removeClass('is-invalid is-valid');
    }

    /**
     * 顯示日期地點重複警告
     */
    showDatePickupWarning(message, existingOrder) {
        console.log('顯示日期地點重複警告:', message);
        this.clearDatePickupWarning();

        const dropoffAddressInput = $('input[name="dropoff_address"]');
        console.log('下車地址輸入框:', dropoffAddressInput.length);

        const warningHtml = `
            <div class="date-pickup-warning alert alert-warning mt-2" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>日期地點重複提醒：</strong>${message}
                        ${existingOrder ? `
                        <br><small class="text-muted">
                            用車時間：${existingOrder.ride_time} → 下車地址：${existingOrder.dropoff_address}
                            <br>建立時間：${existingOrder.created_at}
                        </small>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        dropoffAddressInput.parent().after(warningHtml);
        $('input[name="pickup_address"]').addClass('is-invalid');
        console.log('警告已插入 DOM');
    }

    /**
     * 顯示日期地點可用提示
     */
    showDatePickupSuccess(message) {
        console.log('顯示日期地點可用提示:', message);
        this.clearDatePickupWarning();

        const dropoffAddressInput = $('input[name="dropoff_address"]');
        const successHtml = `
            <div class="date-pickup-warning alert alert-success mt-2" role="alert">
                <i class="fas fa-check me-2"></i>${message}
            </div>
        `;

        dropoffAddressInput.parent().after(successHtml);
        $('input[name="pickup_address"]').removeClass('is-invalid').addClass('is-valid');

        // 3秒後自動隱藏成功提示
        setTimeout(() => {
            $('.date-pickup-warning.alert-success').fadeOut(300);
        }, 3000);
    }

    /**
     * 清除日期地點重複警告
     */
    clearDatePickupWarning() {
        $('.date-pickup-warning').remove();
        $('input[name="pickup_address"]').removeClass('is-invalid is-valid');
        this.datePickupWarningConfirmed = false; // 重置確認標記
    }

    /**
     * 頁面載入時執行初始檢查（如果有預填資料）
     */
    performInitialDatePickupCheck() {
        console.log('執行初始日期地點檢查');

        // 短暫延遲確保 DOM 完全載入
        setTimeout(() => {
            const customerId = $('input[name="customer_id"]').val();
            const rideDate = $('input[name="ride_date"]').val();
            const pickupAddress = $('input[name="pickup_address"]').val();

            console.log('初始檢查參數:', {
                customerId: customerId,
                rideDate: rideDate,
                pickupAddress: pickupAddress
            });

            // 只有在所有必要欄位都有值時才執行檢查
            if (customerId && rideDate && pickupAddress && pickupAddress.trim().length >= 3) {
                console.log('有完整資料，執行初始檢查');
                this.checkDatePickupDuplicate();
            } else {
                console.log('資料不完整，跳過初始檢查');
            }
        }, 100); // 100ms 延遲確保表單完全載入
    }

    // ========== 批量訂單功能 ==========

    /**
     * 初始化批量功能
     */
    initializeBatchFeatures() {
        this.bindDateModeEvents();
        this.initializeFlatpickr();
        this.bindRecurringEvents();
        this.bindBatchPreviewEvents();

        // 恢復週期性模式的 old() 資料
        this.restoreRecurringDates();
    }

    /**
     * 綁定日期模式切換事件
     */
    bindDateModeEvents() {
        // 日期模式切換
        $('input[name="date_mode"]').on('change', this.handleDateModeChange.bind(this));

        // 初始化時設定預設狀態
        this.handleDateModeChange();
    }

    /**
     * 初始化 Flatpickr
     */
    initializeFlatpickr() {
        const multipleDatePicker = document.getElementById('multiple-date-picker');
        if (multipleDatePicker) {
            // 根據訂單類型決定最大日期限制
            const orderType = $('input[name="order_type"]').val();
            let maxDate;

            if (orderType === '台北長照') {
                // 台北長照：今天 + 14 天
                const today = new Date();
                maxDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 14);
            } else {
                // 其他訂單類型：一年內
                maxDate = new Date().fp_incr(365);
            }

            // 準備日曆選項
            const flatpickrOptions = {
                mode: 'multiple',
                dateFormat: 'Y-m-d',
                minDate: 'today',
                maxDate: maxDate,
                static: true, // 防止日曆自動滾動到視窗頂部
                disableMobile: true, // 在行動裝置上也使用桌面版本
                onChange: this.handleDatePickerChange.bind(this),
                onReady: function(selectedDates, dateStr, instance) {
                    // 設定中文星期顯示
                    instance.calendarContainer.style.fontSize = '14px';
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    // 防止開啟時自動滾動
                    instance.calendarContainer.style.position = 'absolute';
                }
            };

            // 如果繁體中文語言包已載入，則使用繁體中文
            console.log('可用的語言包:', Object.keys(flatpickr.l10ns));
            console.log('檢查 flatpickr.l10ns:', typeof flatpickr.l10ns);
            console.log('檢查 zh-tw:', flatpickr.l10ns ? flatpickr.l10ns['zh-tw'] : 'undefined');
            console.log('檢查 zh_tw:', flatpickr.l10ns ? flatpickr.l10ns['zh_tw'] : 'undefined');
            console.log('檢查 zh:', flatpickr.l10ns ? flatpickr.l10ns['zh'] : 'undefined');

            // 嘗試各種可能的鍵名
            const locale = flatpickr.l10ns['zh-tw'] || flatpickr.l10ns['zh_tw'] || flatpickr.l10ns['zh'] || flatpickr.l10ns['mandarin'];

            if (locale) {
                flatpickrOptions.locale = locale;
                console.log('已設定繁體中文 locale:', locale);
            } else {
                console.warn('繁體中文語言包未找到，使用預設語言');
            }

            this.datePicker = flatpickr(multipleDatePicker, flatpickrOptions);

            // 恢復 old() 資料（表單驗證錯誤返回時）
            this.restoreOldSelectedDates();
        }
    }

    /**
     * 恢復表單驗證錯誤時的已選擇日期
     */
    restoreOldSelectedDates() {
        const oldDatesInput = document.getElementById('old-selected-dates');
        if (oldDatesInput && oldDatesInput.value) {
            try {
                const oldDates = JSON.parse(oldDatesInput.value);
                if (Array.isArray(oldDates) && oldDates.length > 0) {
                    // 轉換日期字串為 Date 物件
                    const dateObjects = oldDates.map(dateStr => new Date(dateStr));

                    // 設定到 Flatpickr
                    if (this.datePicker) {
                        this.datePicker.setDate(dateObjects, false); // false 表示不觸發 onChange
                    }

                    // 更新內部狀態
                    this.selectedDates = oldDates;
                    this.updateSelectedDatesList();

                    console.log('Restored selected dates from old():', this.selectedDates);

                    // 恢復完成後，自動生成批量預覽
                    setTimeout(() => {
                        this.generateBatchPreview();
                    }, 100);
                }
            } catch (e) {
                console.error('Failed to restore old selected dates:', e);
            }
        }
    }

    /**
     * 恢復週期性模式的 old() 資料
     */
    restoreRecurringDates() {
        // 檢查是否有週期性欄位的 old() 資料
        const startDate = $('#recurring-dates-section input[name="start_date"]').val();
        const endDate = $('#recurring-dates-section input[name="end_date"]').val();
        const checkedWeekdays = $('input[name="weekdays[]"]:checked').length;

        // 如果有週期性資料，自動產生日期並顯示預覽
        if (startDate && endDate && checkedWeekdays > 0) {
            console.log('Restoring recurring dates from old()');

            // 延遲執行，確保頁面完全載入
            setTimeout(() => {
                // 觸發產生日期
                this.handleGenerateRecurringDates();
            }, 200);
        }
    }

    /**
     * 綁定週期性選擇事件
     */
    bindRecurringEvents() {
        // 快速選擇模板
        $('.quick-select-templates button').on('click', this.handleQuickSelectTemplate.bind(this));

        // 產生日期預覽按鈕
        $('#generate-recurring-dates').on('click', this.handleGenerateRecurringDates.bind(this));

        // 星期幾選擇變更
        $('input[name="weekdays[]"]').on('change', this.handleWeekdayChange.bind(this));

        // 日期範圍變更
        $('input[name="start_date"], input[name="end_date"], select[name="recurrence_type"]')
            .on('change', this.clearRecurringPreview.bind(this));
    }

    /**
     * 綁定批量預覽事件
     */
    bindBatchPreviewEvents() {
        // 建立批量訂單按鈕
        $('#create-batch-btn').on('click', this.handleCreateBatch.bind(this));

        // 取消批量按鈕
        $('#cancel-batch-btn').on('click', this.handleCancelBatch.bind(this));

        // 手動多日預覽按鈕
        $('#generate-manual-preview').on('click', this.handleManualPreview.bind(this));

        // 監聽用車資訊變更，自動更新批量預覽
        $('input[name="ride_time"], input[name="back_time"], input[name="pickup_address"], input[name="dropoff_address"]')
            .on('input change', this.handleBasicInfoChange.bind(this));
    }

    /**
     * 處理基本資訊變更
     */
    handleBasicInfoChange() {
        // 延遲執行，避免頻繁更新
        clearTimeout(this.previewUpdateTimer);
        this.previewUpdateTimer = setTimeout(() => {
            const currentMode = $('input[name="date_mode"]:checked').val();

            // 只有在多日模式且已有選擇的日期時才更新預覽
            if (currentMode === 'manual' && this.selectedDates.length > 0) {
                this.generateBatchPreview();
            } else if (currentMode === 'recurring' && this.recurringDates.length > 0) {
                this.generateBatchPreview();
            }
        }, 500); // 延遲500ms更新
    }

    /**
     * 處理日期模式切換
     */
    handleDateModeChange() {
        const selectedMode = $('input[name="date_mode"]:checked').val() || 'single';

        // 隱藏所有模式區域
        $('#single-date-section').toggle(selectedMode === 'single');
        $('#manual-dates-section').toggle(selectedMode === 'manual');
        $('#recurring-dates-section').toggle(selectedMode === 'recurring');
        $('#batch-preview-section').hide();

        // 動態調整 ride_date 的 required 屬性
        const rideDateField = $('input[name="ride_date"]');
        if (selectedMode === 'single') {
            rideDateField.prop('required', true);
        } else {
            rideDateField.prop('required', false);
        }

        // 控制單日訂單按鈕顯示/隱藏
        $('#singleOrderActions').toggle(selectedMode === 'single');

        // 清空相關資料
        if (selectedMode !== 'manual') {
            this.selectedDates = [];
            if (this.datePicker) {
                this.datePicker.clear();
            }
            this.updateSelectedDatesList();
        }

        if (selectedMode !== 'recurring') {
            this.recurringDates = [];
            this.clearRecurringPreview();
            this.clearWeekdaySelection();
        }

        // 更新表單提交目標
        this.updateFormAction(selectedMode);
    }

    /**
     * 更新表單提交目標
     */
    updateFormAction(mode) {
        const form = $('.order-form');
        const currentAction = form.attr('action');

        if (mode === 'single') {
            // 單日模式使用原本的路由
            if (currentAction.includes('/batch')) {
                form.attr('action', currentAction.replace('/batch', ''));
            }
        } else {
            // 多日模式使用批量路由
            if (!currentAction.includes('/batch')) {
                // 檢查是否為編輯模式
                if (currentAction.includes('/orders/') && !currentAction.endsWith('/orders/store')) {
                    // 編輯模式，保持原路由
                    return;
                } else {
                    form.attr('action', '/orders/batch');
                }
            }
        }
    }

    /**
     * 處理日期選擇器變更
     */
    handleDatePickerChange(selectedDates, dateStr, instance) {
        this.selectedDates = selectedDates.map(date => this.formatDateForBackend(date));
        console.log('Date picker changed, selected dates:', this.selectedDates);
        this.updateSelectedDatesList();

        // 移除自動生成預覽，改為手動觸發
        // 只有當預覽區域已經顯示時才更新預覽
        if (selectedDates.length > 0 && $('#batch-preview-section').is(':visible')) {
            this.generateBatchPreview();
        } else if (selectedDates.length === 0) {
            $('#batch-preview-section').hide();
        }
    }

    /**
     * 更新已選擇日期列表顯示
     */
    updateSelectedDatesList() {
        const container = $('#selected-dates-list');

        if (this.selectedDates.length === 0) {
            container.html('<div class="text-muted">尚未選擇任何日期</div>');
            return;
        }

        let html = '<div class="selected-dates-tags">';
        this.selectedDates.forEach((dateStr, index) => {
            const date = new Date(dateStr);
            const weekday = this.getChineseWeekday(date.getDay());
            const formattedDate = this.formatDate(dateStr);

            html += `
                <span class="badge bg-primary me-2 mb-2 fs-6 py-2">
                    ${formattedDate} (${weekday})
                    <button type="button" class="btn-close btn-close-white ms-2"
                            onclick="orderForm.removeSelectedDate(${index})"
                            style="font-size: 0.7em;"></button>
                </span>
            `;
        });
        html += '</div>';

        container.html(html);
    }

    /**
     * 移除選中的日期
     */
    removeSelectedDate(index) {
        this.selectedDates.splice(index, 1);

        // 更新 Flatpickr
        if (this.datePicker) {
            const dates = this.selectedDates.map(dateStr => new Date(dateStr));
            this.datePicker.setDate(dates);
        }

        this.updateSelectedDatesList();

        if (this.selectedDates.length > 0) {
            this.generateBatchPreview();
        } else {
            $('#batch-preview-section').hide();
        }
    }

    /**
     * 處理快速選擇模板
     */
    handleQuickSelectTemplate(e) {
        const template = e.target.dataset.template;

        // 清除所有選擇
        $('input[name="weekdays[]"]').prop('checked', false);

        if (template === 'clear') {
            this.clearRecurringPreview();
            return;
        }

        // 根據模板選擇星期幾
        const weekdays = {
            '12345': [1, 2, 3, 4, 5], // 模式一 (一至五)
            '246': [2, 4, 6], // 洗腎模式 (二、四、六)
            '135': [1, 3, 5], // 復健模式 (一、三、五)
            '15': [1, 5]      // 週末模式 (一、五)
        };

        if (weekdays[template]) {
            weekdays[template].forEach(day => {
                $(`input[name="weekdays[]"][value="${day}"]`).prop('checked', true);
            });

            // 觸發變更事件
            this.handleWeekdayChange();
        }
    }

    /**
     * 處理星期幾選擇變更
     */
    handleWeekdayChange() {
        const selected = $('input[name="weekdays[]"]:checked').length;

        if (selected > 0) {
            // 清除之前的預覽
            this.clearRecurringPreview();

            // 顯示產生按鈕
            $('#generate-recurring-dates').removeClass('d-none');
        } else {
            $('#generate-recurring-dates').addClass('d-none');
            this.clearRecurringPreview();
        }
    }

    /**
     * 處理產生週期性日期
     */
    handleGenerateRecurringDates() {
        // 明確選擇週期性區域內的日期輸入框，避免與隱藏的搜尋參數衝突
        const startDate = $('#recurring-dates-section input[name="start_date"]').val();
        const endDate = $('#recurring-dates-section input[name="end_date"]').val();
        const weekdays = $('input[name="weekdays[]"]:checked').map(function() {
            return parseInt($(this).val());
        }).get();
        const recurrenceType = $('select[name="recurrence_type"]').val();

        if (!startDate || !endDate) {
            alert('請選擇開始和結束日期');
            return;
        }

        if (weekdays.length === 0) {
            alert('請至少選擇一個星期幾');
            return;
        }

        // 驗證日期範圍
        if (new Date(startDate) >= new Date(endDate)) {
            alert('結束日期必須晚於開始日期');
            return;
        }

        // 產生週期性日期
        this.recurringDates = this.generateRecurringDates(startDate, endDate, weekdays, recurrenceType);

        if (this.recurringDates.length === 0) {
            alert('在指定範圍內沒有符合條件的日期');
            return;
        }

        if (this.recurringDates.length > 50) {
            alert(`產生的日期過多（${this.recurringDates.length} 個），請縮小日期範圍或調整重複週期`);
            return;
        }

        this.displayRecurringPreview();
        this.generateBatchPreview();
    }

    /**
     * 產生週期性日期（JavaScript 版本）
     * 注意：此方法主要用於前端預覽，實際日期生成由後端 BatchOrderService 處理
     */
    generateRecurringDates(startDate, endDate, weekdays, recurrenceType) {
        const dates = [];
        
        // 使用更可靠的日期解析方式，避免時區問題
        const start = this.parseLocalDate(startDate);
        const end = this.parseLocalDate(endDate);


        // 將 weekdays 轉換為 Set 提高查詢效率
        const targetWeekdays = new Set(weekdays.map(w => parseInt(w)));

        // 簡單的逐日遍歷，確保不會產生範圍外的日期
        const currentDate = new Date(start);

        while (currentDate <= end) {
            // 檢查當前日期是否為目標星期幾
            const currentWeekday = currentDate.getDay(); // 0=週日, 1=週一, ..., 6=週六

            if (targetWeekdays.has(currentWeekday)) {
                // 檢查是否符合週期性條件
                if (this.isDateInRecurrencePattern(currentDate, start, recurrenceType)) {
                    const dateStr = this.formatDateForBackend(currentDate);
                    if (!dates.includes(dateStr)) {
                        dates.push(dateStr);
                    }
                }
            }

            // 移動到下一天
            currentDate.setDate(currentDate.getDate() + 1);
        }
        return dates.sort();
    }

    /**
     * 解析本地日期，避免時區轉換問題
     */
    parseLocalDate(dateString) {
        if (!dateString) return null;
        
        // 將 YYYY-MM-DD 格式轉換為本地日期物件
        const parts = dateString.split('-');
        if (parts.length !== 3) return new Date(dateString);
        
        const year = parseInt(parts[0]);
        const month = parseInt(parts[1]) - 1; // JavaScript 月份從 0 開始
        const day = parseInt(parts[2]);
        
        return new Date(year, month, day);
    }

    /**
     * 檢查日期是否符合週期性模式
     */
    isDateInRecurrencePattern(currentDate, startDate, recurrenceType) {
        if (recurrenceType === 'weekly') {
            return true; // 每週都符合
        }

        // 計算從開始日期到當前日期的天數差
        const diffTime = currentDate.getTime() - startDate.getTime();
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        const diffWeeks = Math.floor(diffDays / 7);

        if (recurrenceType === 'biweekly') {
            return diffWeeks % 2 === 0; // 偶數週
        }

        if (recurrenceType === 'monthly') {
            return diffWeeks % 4 === 0; // 每4週
        }

        return true;
    }

    /**
     * 顯示週期性日期預覽
     */
    displayRecurringPreview() {
        let html = '<div class="alert alert-info">';
        html += `<h6><i class="fas fa-calendar-check me-2"></i>產生了 ${this.recurringDates.length} 個日期</h6>`;
        html += '<div class="recurring-dates-preview">';

        this.recurringDates.forEach((dateStr, index) => {
            const date = new Date(dateStr);
            const weekday = this.getChineseWeekday(date.getDay());
            const formattedDate = this.formatDate(dateStr);

            html += `
                <span class="badge bg-secondary me-2 mb-2 fs-6 py-2">
                    ${formattedDate} (${weekday})
                </span>
            `;

            // 每 5 個換行
            if ((index + 1) % 5 === 0) {
                html += '<br>';
            }
        });

        html += '</div></div>';

        $('#recurring-dates-preview').html(html);
    }

    /**
     * 產生批量訂單預覽
     */
    generateBatchPreview() {
        const currentMode = $('input[name="date_mode"]:checked').val();
        let dates = [];

        if (currentMode === 'manual') {
            dates = this.selectedDates;
        } else if (currentMode === 'recurring') {
            dates = this.recurringDates;
        }

        if (dates.length === 0) {
            $('#batch-preview-section').hide();
            return;
        }

        // 檢查必要欄位是否已填寫
        const rideTime = $('input[name="ride_time"]').val();
        const pickupAddress = $('input[name="pickup_address"]').val();
        const dropoffAddress = $('input[name="dropoff_address"]').val();

        if (!rideTime || !pickupAddress || !dropoffAddress) {
            // 顯示提示訊息
            const missingFields = [];
            if (!rideTime) missingFields.push('用車時間');
            if (!pickupAddress) missingFields.push('上車地址');
            if (!dropoffAddress) missingFields.push('下車地址');

            $('#batch-orders-preview').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    請先完成以下基本資訊後再預覽批量訂單：
                    <strong>${missingFields.join('、')}</strong>
                </div>
            `);
            $('#batch-preview-section').show();
            return;
        }

        // 檢查是否有回程
        const backTime = $('input[name="back_time"]').val();
        const hasReturn = backTime && backTime.trim();
        const totalOrders = dates.length * (hasReturn ? 2 : 1);

        // 更新總訂單數
        $('#total-orders').text(totalOrders);

        // 檢查重複訂單（先檢查客戶是否已選）
        const customerId = $('input[name="customer_id"]').val();
        if (customerId && rideTime) {
            this.checkBatchDuplicateOrders(dates, customerId, rideTime);
            return; // 檢查結果會在回調中處理預覽
        }

        // 產生預覽表格
        let html = '<table class="table table-sm table-hover">';
        html += `
            <thead class="table-light">
                <tr>
                    <th>日期</th>
                    <th>星期</th>
                    <th>去程時間</th>
                    ${hasReturn ? '<th>回程時間</th>' : ''}
                    <th>地址</th>
                </tr>
            </thead>
            <tbody>
        `;

        dates.forEach(dateStr => {
            const date = new Date(dateStr);
            const weekday = this.getChineseWeekday(date.getDay());
            const formattedDate = this.formatDate(dateStr);

            // 去程訂單
            html += `
                <tr>
                    <td>${formattedDate}</td>
                    <td>${weekday}</td>
                    <td><span class="badge bg-primary">${rideTime}</span></td>
                    ${hasReturn ? `<td>-</td>` : ''}
                    <td>
                        <small class="text-success">${this.truncateText(pickupAddress, 20)}</small>
                        →
                        <small class="text-danger">${this.truncateText(dropoffAddress, 20)}</small>
                    </td>
                </tr>
            `;

            // 回程訂單
            if (hasReturn) {
                html += `
                    <tr class="table-secondary">
                        <td>${formattedDate}</td>
                        <td>${weekday}</td>
                        <td>-</td>
                        <td><span class="badge bg-warning">${backTime}</span></td>
                        <td>
                            <small class="text-danger">${this.truncateText(dropoffAddress, 20)}</small>
                            →
                            <small class="text-success">${this.truncateText(pickupAddress, 20)}</small>
                        </td>
                    </tr>
                `;
            }
        });

        html += '</tbody></table>';

        $('#batch-orders-preview').html(html);
        $('#batch-preview-section').show();

        // 滾動到預覽區域
        $('#batch-preview-section')[0].scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * 處理建立批量訂單
     */
    handleCreateBatch() {
        if (!this.validateBatchForm()) {
            return;
        }

        // 準備表單資料
        const currentMode = $('input[name="date_mode"]:checked').val();
        const form = $('.order-form');

        // 調試資訊
        console.log('Current date mode:', currentMode);
        console.log('Selected dates:', this.selectedDates);

        if (currentMode === 'manual') {
            // 檢查是否選擇了日期
            if (this.selectedDates.length === 0) {
                alert('請先選擇至少一個日期');
                return;
            }

            // 移除現有的隱藏欄位避免重複
            form.find('input[name="selected_dates[]"]').remove();

            // 手動多日模式：添加選中的日期
            this.selectedDates.forEach(dateStr => {
                form.append(`<input type="hidden" name="selected_dates[]" value="${dateStr}">`);
            });

            console.log('Added selected_dates hidden inputs:', this.selectedDates.length);
        } else if (currentMode === 'recurring') {
            // 週期性模式不需要添加隱藏日期字段
            // 後端使用 start_date, end_date, weekdays 來生成日期
        }

        // 提交表單
        form.submit();
    }

    /**
     * 處理取消批量
     */
    handleCancelBatch() {
        $('#batch-preview-section').hide();

        // 清空資料
        const currentMode = $('input[name="date_mode"]:checked').val();

        if (currentMode === 'manual') {
            this.selectedDates = [];
            if (this.datePicker) {
                this.datePicker.clear();
            }
            this.updateSelectedDatesList();
        } else if (currentMode === 'recurring') {
            this.recurringDates = [];
            this.clearRecurringPreview();
        }
    }

    /**
     * 處理手動多日預覽
     */
    handleManualPreview() {
        if (this.selectedDates.length === 0) {
            alert('請先選擇至少一個日期');
            return;
        }

        // 直接生成預覽
        this.generateBatchPreview();
    }

    /**
     * 驗證批量表單
     */
    validateBatchForm() {
        const currentMode = $('input[name="date_mode"]:checked').val();

        if (currentMode === 'manual') {
            if (this.selectedDates.length === 0) {
                alert('請至少選擇一個日期');
                return false;
            }
        } else if (currentMode === 'recurring') {
            if (this.recurringDates.length === 0) {
                alert('請先產生週期性日期');
                return false;
            }
        }

        // 檢查基本欄位
        const requiredFields = ['customer_id', 'ride_time', 'pickup_address', 'dropoff_address'];
        for (let fieldName of requiredFields) {
            const field = $(`input[name="${fieldName}"]`);
            if (!field.val() || !field.val().trim()) {
                const label = field.closest('.col-md-2, .col-md-3, .col-md-4, .col-md-6, .col-12').find('label').text() || fieldName;
                alert(`請填寫 ${label}`);
                field.focus();
                return false;
            }
        }

        return true;
    }

    /**
     * 清除週期性預覽
     */
    clearRecurringPreview() {
        $('#recurring-dates-preview').empty();
        this.recurringDates = [];
        $('#batch-preview-section').hide();
    }

    /**
     * 清除星期幾選擇
     */
    clearWeekdaySelection() {
        $('input[name="weekdays[]"]').prop('checked', false);
        $('#generate-recurring-dates').addClass('d-none');
    }

    /**
     * 格式化日期為後端格式（避免 UTC 時區轉換問題）
     */
    formatDateForBackend(date) {
        // 使用本地時間格式化，避免 UTC 轉換造成日期相差問題
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * 批量檢查重複訂單
     */
    checkBatchDuplicateOrders(dates, customerId, rideTime) {
        $.ajax({
            url: '/orders/check-batch-duplicate',
            method: 'POST',
            data: {
                customer_id: customerId,
                dates: dates,
                ride_time: rideTime,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                this.handleBatchDuplicateResponse(response, dates);
            },
            error: (xhr) => {
                console.error('批量檢查重複訂單時發生錯誤:', xhr);
                // 發生錯誤時直接顯示預覽
                this.generateBatchPreviewTable(dates, []);
            }
        });
    }

    /**
     * 處理批量重複檢查回應
     */
    handleBatchDuplicateResponse(response, originalDates) {
        // 儲存回應資料供後續使用
        this.lastBatchCheckResponse = response;

        if (response.hasDuplicates) {
            this.showBatchDuplicateWarning(response);
        } else {
            this.showBatchSuccess(response.message);
        }

        // 生成預覽表格，並標示重複的日期
        this.generateBatchPreviewTable(originalDates, response.duplicates || []);
    }

    /**
     * 顯示批量重複警告
     */
    showBatchDuplicateWarning(response) {
        const summary = response.summary;
        const duplicates = response.duplicates;

        let duplicateDetails = duplicates.map(duplicate => {
            const existingOrder = duplicate.existing_order;
            return `
                <div class="duplicate-item mb-2">
                    <strong>${duplicate.formatted_date}</strong> -
                    已有訂單 <code>${existingOrder.order_number}</code>
                    <br>
                    <small class="text-muted">
                        ${existingOrder.pickup_address} → ${existingOrder.dropoff_address}
                        (建立於 ${existingOrder.created_at})
                    </small>
                </div>
            `;
        }).join('');

        const warningHtml = `
            <div class="batch-duplicate-warning alert alert-warning mt-3" role="alert">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <h6 class="mb-0">發現重複訂單</h6>
                </div>
                <div class="mb-3">
                    <span class="badge bg-danger me-2">${summary.duplicates} 個重複</span>
                    <span class="badge bg-success">${summary.available} 個可用</span>
                </div>
                <div class="duplicate-details mb-3" style="max-height: 200px; overflow-y: auto;">
                    ${duplicateDetails}
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm" onclick="orderForm.skipDuplicatesAndCreate()">
                        <i class="fas fa-check me-1"></i>跳過重複，建立 ${summary.available} 筆訂單
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="orderForm.cancelBatchDueToDuplicates()">
                        <i class="fas fa-times me-1"></i>取消操作
                    </button>
                </div>
            </div>
        `;

        $('#batch-orders-preview').prepend(warningHtml);
    }

    /**
     * 顯示批量成功訊息
     */
    showBatchSuccess(message) {
        const successHtml = `
            <div class="batch-duplicate-success alert alert-success mt-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
            </div>
        `;

        $('#batch-orders-preview').prepend(successHtml);

        // 3秒後自動隱藏
        setTimeout(() => {
            $('.batch-duplicate-success').fadeOut(300);
        }, 3000);
    }

    /**
     * 產生批量預覽表格（支援重複標示）
     */
    generateBatchPreviewTable(dates, duplicates) {
        const rideTime = $('input[name="ride_time"]').val();
        const pickupAddress = $('input[name="pickup_address"]').val();
        const dropoffAddress = $('input[name="dropoff_address"]').val();
        const backTime = $('input[name="back_time"]').val();
        const hasReturn = backTime && backTime.trim();

        // 建立重複日期查詢表 - 確保日期格式標準化
        const duplicateMap = {};
        duplicates.forEach(duplicate => {
            // 標準化日期格式為 YYYY-MM-DD
            const normalizedDate = this.normalizeDateFormat(duplicate.date);
            duplicateMap[normalizedDate] = duplicate;
        });

        // 除錯：顯示重複日期映射
        console.log('=== 重複檢查除錯資訊 ===');
        console.log('API 回傳的 duplicates:', duplicates);
        console.log('建立的 duplicateMap:', duplicateMap);
        console.log('要處理的 dates:', dates);
        console.log('duplicateMap keys:', Object.keys(duplicateMap));

        // 產生預覽表格
        let html = '<table class="table table-sm table-hover">';
        html += `
            <thead class="table-light">
                <tr>
                    <th>日期</th>
                    <th>星期</th>
                    <th>去程時間</th>
                    ${hasReturn ? '<th>回程時間</th>' : ''}
                    <th>地址</th>
                    <th>狀態</th>
                </tr>
            </thead>
            <tbody>
        `;

        dates.forEach(dateStr => {
            const date = new Date(dateStr);
            const weekday = this.getChineseWeekday(date.getDay());
            const formattedDate = this.formatDate(dateStr);

            // 標準化日期格式進行比對
            const normalizedDateStr = this.normalizeDateFormat(dateStr);
            const isDuplicate = duplicateMap[normalizedDateStr];

            // 除錯：顯示每個日期的檢查結果
            console.log(`檢查日期 ${dateStr}:`, {
                'original_dateStr': dateStr,
                'normalized_dateStr': normalizedDateStr,
                'isDuplicate': isDuplicate ? '✓ 重複' : '✗ 可用',
                'duplicateMap中是否存在': duplicateMap.hasOwnProperty(normalizedDateStr),
                'duplicateMap keys': Object.keys(duplicateMap)
            });

            const rowClass = isDuplicate ? 'table-danger' : '';
            const statusBadge = isDuplicate ?
                `<span class="badge bg-danger">重複</span>` :
                `<span class="badge bg-success">可用</span>`;

            // 去程訂單
            html += `
                <tr class="${rowClass}">
                    <td>${formattedDate}</td>
                    <td>${weekday}</td>
                    <td><span class="badge bg-primary">${rideTime}</span></td>
                    ${hasReturn ? `<td>-</td>` : ''}
                    <td>
                        <small class="text-success">${this.truncateText(pickupAddress, 20)}</small>
                        →
                        <small class="text-danger">${this.truncateText(dropoffAddress, 20)}</small>
                    </td>
                    <td>${statusBadge}</td>
                </tr>
            `;

            // 回程訂單
            if (hasReturn) {
                html += `
                    <tr class="table-secondary ${isDuplicate ? 'table-danger' : ''}">
                        <td>${formattedDate}</td>
                        <td>${weekday}</td>
                        <td>-</td>
                        <td><span class="badge bg-warning">${backTime}</span></td>
                        <td>
                            <small class="text-danger">${this.truncateText(dropoffAddress, 20)}</small>
                            →
                            <small class="text-success">${this.truncateText(pickupAddress, 20)}</small>
                        </td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            }
        });

        html += '</tbody></table>';

        // 清除舊的警告並新增新的表格
        $('.batch-duplicate-warning, .batch-duplicate-success').remove();
        $('#batch-orders-preview').html(html);
        $('#batch-preview-section').show();

        // 滾動到預覽區域
        $('#batch-preview-section')[0].scrollIntoView({ behavior: 'smooth' });
    }

    /**
     * 跳過重複日期並建立訂單
     */
    skipDuplicatesAndCreate() {
        // 準備只包含非重複日期的表單
        const form = $('.order-form');
        const currentMode = $('input[name="date_mode"]:checked').val();

        // 調試資訊
        console.log('Skip duplicates - Current mode:', currentMode);
        console.log('Available dates:', this.lastBatchCheckResponse?.available_dates);

        // 移除現有的 hidden inputs
        form.find('input[name="selected_dates[]"]').remove();

        if (this.lastBatchCheckResponse && this.lastBatchCheckResponse.available_dates.length > 0) {
            if (currentMode === 'manual') {
                // 手動多日模式：使用可用日期
                this.lastBatchCheckResponse.available_dates.forEach(dateStr => {
                    form.append(`<input type="hidden" name="selected_dates[]" value="${dateStr}">`);
                });
                console.log('Added available dates to form:', this.lastBatchCheckResponse.available_dates.length);
            } else if (currentMode === 'recurring') {
                // 週期性模式：需要修改後端邏輯來處理可用日期過濾
                // 暫時轉換為手動模式提交
                form.append(`<input type="hidden" name="date_mode" value="manual">`);
                this.lastBatchCheckResponse.available_dates.forEach(dateStr => {
                    form.append(`<input type="hidden" name="selected_dates[]" value="${dateStr}">`);
                });
                console.log('Converted recurring to manual mode with available dates:', this.lastBatchCheckResponse.available_dates.length);
            }

            // 提交表單
            form.submit();
        } else {
            alert('沒有可用的日期可以建立訂單');
        }
    }

    /**
     * 因重複問題取消批量操作
     */
    cancelBatchDueToDuplicates() {
        $('#batch-preview-section').hide();
        $('.batch-duplicate-warning').remove();

        // 可以選擇性地清除日期選擇
        // this.handleCancelBatch();
    }

    /**
     * 取得中文星期幾
     */
    getChineseWeekday(dayIndex) {
        const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        return weekdays[dayIndex];
    }

    /**
     * 標準化日期格式為 YYYY-MM-DD
     */
    normalizeDateFormat(dateStr) {
        if (!dateStr) return '';

        try {
            // 處理各種可能的日期格式
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) {
                console.warn('Invalid date format:', dateStr);
                return dateStr; // 回傳原始字串
            }

            // 轉換為 YYYY-MM-DD 格式
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        } catch (error) {
            console.error('Date normalization error:', error, 'for date:', dateStr);
            return dateStr; // 回傳原始字串
        }
    }

    // ========== 訂單資訊複製功能 ==========

    /**
     * 顯示訂單資訊複製 Modal
     */
    showOrderInfoModal() {
        console.log('顯示訂單資訊複製 Modal');

        // 生成訂單資訊文字
        const orderInfo = this.generateOrderInfoText();

        if (orderInfo.single) {
            // 單程顯示
            $('#singleTripArea').show();
            $('#roundTripArea').hide();
            $('#copyToClipboardBtn').show();
            $('#copyAllBtn').hide();

            // 設置單程內容
            $('#orderInfoText').val(orderInfo.single);
        } else {
            // 去回程分離顯示
            $('#singleTripArea').hide();
            $('#roundTripArea').show();
            $('#copyToClipboardBtn').hide();
            $('#copyAllBtn').show();

            // 設置去程和回程內容
            $('#outboundInfoText').val(orderInfo.outbound);
            $('#returnInfoText').val(orderInfo.return);

            // 儲存完整資訊到 modal 的 data 屬性，供複製完整資訊使用
            const fullText = `=== 去程 ===\n${orderInfo.outbound}\n\n=== 回程 ===\n${orderInfo.return}`;
            $('#orderInfoModal').data('fullText', fullText);
        }

        // 顯示 Modal
        const modal = new bootstrap.Modal(document.getElementById('orderInfoModal'));
        modal.show();
    }

    /**
     * 生成格式化的訂單資訊文字
     */
    generateOrderInfoText() {
        const rideDate = $('input[name="ride_date"]').val();
        const rideTime = $('input[name="ride_time"]').val();
        const backTime = $('input[name="back_time"]').val();
        const pickupAddress = $('input[name="pickup_address"]').val();
        const dropoffAddress = $('input[name="dropoff_address"]').val();

        // 生成日期文字（只有不是今天才顯示）
        let dateText = '';
        if (rideDate) {
            const date = new Date(rideDate);
            const today = new Date();

            // 比較日期（忽略時間）
            const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());

            if (dateOnly.getTime() !== todayOnly.getTime()) {
                const formattedDate = date.toLocaleDateString('zh-TW', {
                    month: 'numeric',
                    day: 'numeric'
                });
                dateText = `${formattedDate}\n`;
            }
        }

        // 檢查是否有回程時間
        if (backTime && backTime.trim()) {
            // 有回程時間，分成去程和回程
            let outboundText = dateText;
            let returnText = dateText;

            // 去程資訊
            if (rideTime) {
                outboundText += `${rideTime}\n`;
            }
            if (pickupAddress) {
                outboundText += `${pickupAddress} >\n`;
            }
            if (dropoffAddress) {
                outboundText += `${dropoffAddress}`;
            }

            // 回程資訊
            returnText += `${backTime}\n`;
            if (dropoffAddress) {
                returnText += `${dropoffAddress} >\n`;
            }
            if (pickupAddress) {
                returnText += `${pickupAddress}`;
            }

            // 檢查是否有資訊
            if (!outboundText.replace(dateText, '').trim() && !returnText.replace(dateText, '').trim()) {
                return { single: '請先填寫訂單資訊' };
            }

            return {
                outbound: outboundText || '去程資訊不完整',
                return: returnText || '回程資訊不完整'
            };
        } else {
            // 沒有回程時間，單程顯示
            let singleText = dateText;

            if (rideTime) {
                singleText += `${rideTime}\n`;
            }
            if (pickupAddress) {
                singleText += `${pickupAddress} >\n`;
            }
            if (dropoffAddress) {
                singleText += `${dropoffAddress}`;
            }

            // 如果沒有任何資訊
            if (!singleText.replace(dateText, '').trim()) {
                singleText = '請先填寫訂單資訊';
            }

            return { single: singleText };
        }
    }

    /**
     * 複製訂單資訊到剪貼板
     */
    copyOrderInfoToClipboard() {
        const orderText = $('#orderInfoText').val();

        if (!orderText || orderText === '請先填寫訂單資訊') {
            alert('沒有訂單資訊可以複製');
            return;
        }

        // 使用現代 Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(orderText).then(() => {
                // 顯示成功提示
                const btn = $('#copyToClipboardBtn');
                const originalText = btn.html();
                btn.html('<i class="fas fa-check me-2"></i>已複製！');
                btn.removeClass('btn-primary').addClass('btn-success');

                // 3秒後恢復原狀
                setTimeout(() => {
                    btn.html(originalText);
                    btn.removeClass('btn-success').addClass('btn-primary');
                }, 3000);

                console.log('訂單資訊已複製到剪貼板（使用 Clipboard API）');
            }).catch(err => {
                console.error('Clipboard API 失敗:', err);
                this.fallbackCopyToClipboard(orderText, '#copyToClipboardBtn', 'btn-primary', '已複製！');
            });
        } else {
            // 後備方案（HTTP 環境或舊瀏覽器）
            console.log('使用後備複製方案（非安全環境或不支援 Clipboard API）');
            this.fallbackCopyToClipboard(orderText, '#copyToClipboardBtn', 'btn-primary', '已複製！');
        }
    }

    /**
     * 後備複製方法（適用於舊瀏覽器或非安全環境如 HTTP）
     */
    fallbackCopyToClipboard(text, buttonSelector = null, originalClass = 'btn-primary', successText = '已複製！') {
        const textArea = document.createElement('textarea');
        textArea.value = text;

        // 使用 absolute 定位移到螢幕外（比 fixed + opacity 更可靠）
        textArea.style.position = 'absolute';
        textArea.style.left = '-9999px';
        textArea.style.top = '-9999px';
        textArea.setAttribute('readonly', ''); // 避免鍵盤彈出（行動裝置）

        document.body.appendChild(textArea);

        // 確保 focus 和選取
        textArea.focus();
        textArea.select();

        // iOS 相容性：明確設定選取範圍
        try {
            textArea.setSelectionRange(0, textArea.value.length);
        } catch (err) {
            // 部分瀏覽器不支援，忽略錯誤
        }

        try {
            const successful = document.execCommand('copy');

            if (successful) {
                console.log('後備複製方法成功');

                // 如果有提供按鈕選擇器，更新按鈕狀態
                if (buttonSelector) {
                    const btn = $(buttonSelector);
                    const originalText = btn.html();
                    btn.html('<i class="fas fa-check me-2"></i>' + successText);
                    btn.removeClass(originalClass).addClass('btn-success');

                    // 3秒後恢復原狀
                    setTimeout(() => {
                        btn.html(originalText);
                        btn.removeClass('btn-success').addClass(originalClass);
                    }, 3000);
                } else {
                    // 沒有按鈕時使用 alert
                    alert('訂單資訊已複製到剪貼板');
                }
            } else {
                console.error('後備複製方法：execCommand 返回 false');
                alert('複製失敗，請手動複製');
            }
        } catch (err) {
            console.error('後備複製方法失敗:', err);
            alert('複製失敗，請手動複製');
        }

        document.body.removeChild(textArea);
    }

    /**
     * 複製去程資訊到剪貼板
     */
    copyOutboundToClipboard() {
        const outboundText = $('#outboundInfoText').val();

        if (!outboundText || outboundText === '去程資訊不完整') {
            alert('沒有去程資訊可以複製');
            return;
        }

        this.performCopy(outboundText, '#copyOutboundBtn', 'btn-primary', '已複製去程！');
    }

    /**
     * 複製回程資訊到剪貼板
     */
    copyReturnToClipboard() {
        const returnText = $('#returnInfoText').val();

        if (!returnText || returnText === '回程資訊不完整') {
            alert('沒有回程資訊可以複製');
            return;
        }

        this.performCopy(returnText, '#copyReturnBtn', 'btn-success', '已複製回程！');
    }

    /**
     * 複製完整資訊到剪貼板
     */
    copyAllToClipboard() {
        const fullText = $('#orderInfoModal').data('fullText');

        if (!fullText) {
            alert('沒有完整資訊可以複製');
            return;
        }

        this.performCopy(fullText, '#copyAllBtn', 'btn-outline-primary', '已複製完整資訊！');
    }

    /**
     * 執行複製操作的通用方法
     */
    performCopy(text, buttonSelector, originalClass, successText) {
        // 使用現代 Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                // 顯示成功提示
                const btn = $(buttonSelector);
                const originalText = btn.html();
                btn.html(`<i class="fas fa-check me-2"></i>${successText}`);
                btn.removeClass(originalClass).addClass('btn-success');

                // 3秒後恢復原狀
                setTimeout(() => {
                    btn.html(originalText);
                    btn.removeClass('btn-success').addClass(originalClass);
                }, 3000);

                console.log(`${successText} - 已複製到剪貼板（使用 Clipboard API）`);
            }).catch(err => {
                console.error('Clipboard API 失敗:', err);
                this.fallbackCopyToClipboard(text, buttonSelector, originalClass, successText);
            });
        } else {
            // 後備方案（HTTP 環境或舊瀏覽器）
            console.log('使用後備複製方案（非安全環境或不支援 Clipboard API）');
            this.fallbackCopyToClipboard(text, buttonSelector, originalClass, successText);
        }
    }

    /**
     * 【新增】從地址中提取縣市
     */
    extractCounty(address) {
        if (!address) return '';
        const match = address.match(/(.+?市|.+?縣)/);
        return match ? match[1] : '';
    }

    /**
     * 【新增】從地址中提取區域
     */
    extractDistrict(address) {
        if (!address) return '';
        const match = address.match(/(?:市|縣)(.+?區|.+?鄉|.+?鎮)/);
        return match ? match[1] : '';
    }

    /**
     * 【新增】根據訂單類型驗證地址
     */
    validateAddressByOrderType() {
        const orderType = $('input[name="order_type"]').val();
        const pickupAddress = $('#pickup_address').val();
        const dropoffAddress = $('#dropoff_address').val();

        // 如果地址未填寫完整，不進行驗證
        if (!orderType || !pickupAddress || !dropoffAddress) {
            return;
        }

        // 清除之前的警告訊息
        this.clearAddressWarnings();

        const pickupCounty = this.extractCounty(pickupAddress);
        const pickupDistrict = this.extractDistrict(pickupAddress);
        const dropoffCounty = this.extractCounty(dropoffAddress);

        let hasError = false;
        const errors = [];

        // 台北長照驗證
        if (orderType === '台北長照') {
            const allowedCounties = ['新北市', '台北市'];

            if (!allowedCounties.includes(pickupCounty)) {
                hasError = true;
                errors.push('上車地址必須在新北市或台北市');
                $('#pickup_address').addClass('is-invalid');
            }

            if (!allowedCounties.includes(dropoffCounty)) {
                hasError = true;
                errors.push('下車地址必須在新北市或台北市');
                $('#dropoff_address').addClass('is-invalid');
            }
        }

        // 新北長照驗證
        if (orderType === '新北長照') {
            const allowedCounties = ['新北市', '台北市', '桃園市', '基隆市'];

            if (!allowedCounties.includes(pickupCounty)) {
                hasError = true;
                errors.push('上車地址必須在新北市、台北市、桃園市或基隆市');
                $('#pickup_address').addClass('is-invalid');
            }

            if (!allowedCounties.includes(dropoffCounty)) {
                hasError = true;
                errors.push('下車地址必須在新北市、台北市、桃園市或基隆市');
                $('#dropoff_address').addClass('is-invalid');
            }

            // 新北長照特定區域自動設為不派遣
            const noSendDistricts = ['金山區', '鶯歌區', '三峽區', '淡水區', '五股區', '瑞芳區', '萬里區'];
            if (noSendDistricts.includes(pickupDistrict)) {
                this.showNoSendWarning(pickupDistrict);
                this.autoSetStatusToNoSend();
            }
        }

        // 顯示錯誤訊息
        if (hasError) {
            this.showAddressErrors(errors);
        }
    }

    /**
     * 【新增】顯示地址錯誤訊息
     */
    showAddressErrors(errors) {
        const errorHtml = `
            <div class="alert alert-danger alert-dismissible fade show mt-2" id="address-error-alert" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <strong>地址驗證失敗：</strong>
                <ul class="mb-0 mt-1">
                    ${errors.map(err => `<li>${err}</li>`).join('')}
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // 在下車地址欄位後顯示錯誤
        $('#dropoff_address').closest('.row').after(errorHtml);
    }

    /**
     * 【新增】顯示「不派遣」警告提示
     */
    showNoSendWarning(district) {
        const warningHtml = `
            <div class="alert alert-warning alert-dismissible fade show mt-2" id="no-send-warning-alert" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>注意：</strong>此客戶地址位於服務範圍外（${district}），訂單將自動設為「不派遣」狀態
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // 在上車地址欄位後顯示警告
        $('#pickup_address').closest('.row').after(warningHtml);
    }

    /**
     * 【新增】自動將訂單狀態設為「不派遣」
     */
    autoSetStatusToNoSend() {
        const statusSelect = $('select[name="status"]');
        if (statusSelect.length > 0) {
            statusSelect.val('no_send');
            // 視覺提示狀態已變更
            statusSelect.addClass('border-warning').addClass('border-2');
            setTimeout(() => {
                statusSelect.removeClass('border-warning').removeClass('border-2');
            }, 2000);
        }
    }

    /**
     * 【新增】清除地址警告訊息
     */
    clearAddressWarnings() {
        $('#address-error-alert').remove();
        $('#no-send-warning-alert').remove();
        $('#pickup_address, #dropoff_address').removeClass('is-invalid');
    }
}

// 全域變數，供 HTML onclick 使用
let orderForm;

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    orderForm = new OrderForm();
});

// 全域函數，供 HTML 調用
function openLandmarkModal(addressType) {
    orderForm.openLandmarkModal(addressType);
}
