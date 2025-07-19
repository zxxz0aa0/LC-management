/**
 * 訂單表單頁面 JavaScript
 * 職責：表單驗證、共乘查詢、駕駛查詢、地標選擇
 */
class OrderForm {
    constructor() {
        this.currentAddressType = ''; // 'pickup' 或 'dropoff'
        this.landmarkModal = null;
        this.init();
    }

    init() {
        this.initializeLandmarkModal();
        this.bindFormEvents();
        this.bindCarpoolEvents();
        this.bindDriverEvents();
        this.bindAddressEvents();
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
    }

    /**
     * 綁定駕駛相關事件
     */
    bindDriverEvents() {
        // 駕駛搜尋
        $('#searchDriverBtn').on('click', this.handleDriverSearch.bind(this));

        // 清除駕駛
        $('#clearDriverBtn').on('click', this.handleDriverClear.bind(this));

        // 駕駛隊編輸入監聽
        $('#driver_fleet_number').on('input', this.handleDriverFleetInput.bind(this));

        // 駕駛搜尋輸入框 Enter 鍵
        $('#driver_fleet_number').on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleDriverSearch();
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

        // 顯示錯誤訊息
        if (!isValid) {
            this.showErrorMessages(errors);
        }

        return isValid;
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${customer.name}</strong> / ${customer.id_number}<br>
                            <small class="text-muted">${customer.phone_number} / ${customer.addresses}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" onclick="orderForm.selectCarpoolCustomer(${JSON.stringify(customer).replace(/"/g, '&quot;')})">
                            選擇
                        </button>
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

        // 顯示成功訊息
        this.showSuccessMessage('已選擇共乘客戶：' + customer.name);
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
    }

    /**
     * 處理駕駛搜尋
     */
    handleDriverSearch() {
        const fleetNumber = $('#driver_fleet_number').val().trim();
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
                    $('#driver_id').val(data.id);
                    $('#driver_name').val(data.name);
                    $('#driver_plate_number').val(data.plate_number);

                    // 自動設定為已指派
                    $('select[name="status"]').val('assigned');

                    this.showSuccessMessage('已找到駕駛：' + data.name);
                }
            })
            .catch(() => {
                alert('查詢失敗，請稍後再試');
            });
    }

    /**
     * 清除駕駛資料
     */
    handleDriverClear() {
        $('#driver_fleet_number').val('');
        $('#driver_id').val('');
        $('#driver_name').val('');
        $('#driver_plate_number').val('');

        // 恢復狀態選擇
        $('select[name="status"]').val('open');
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

        this.showSuccessMessage('已交換上下車地址');
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
    handleLandmarkSearch() {
        const keyword = $('#landmarkSearchInput').val().trim();
        if (!keyword) {
            alert('請輸入搜尋關鍵字');
            return;
        }

        $('#landmarkSearchResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');

        fetch(`/landmarks-search?keyword=${encodeURIComponent(keyword)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    this.displayLandmarkResults(data.data, '#landmarkSearchResults');
                } else {
                    $('#landmarkSearchResults').html('<div class="text-center py-4"><p class="text-muted">查無符合條件的地標</p></div>');
                }
            })
            .catch(error => {
                console.error('搜尋地標錯誤:', error);
                $('#landmarkSearchResults').html('<div class="alert alert-danger">搜尋失敗，請稍後再試</div>');
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
                     onclick="orderForm.selectLandmark('${fullAddress}', ${landmark.id})">
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
     * 選擇地標
     */
    selectLandmark(address, landmarkId) {
        const targetInput = $(`#${this.currentAddressType}_address`);
        targetInput.val(address);
        targetInput.attr('data-landmark-id', landmarkId);

        // 關閉 Modal
        this.landmarkModal.hide();

        // 更新使用次數
        this.updateLandmarkUsage(landmarkId);

        // 保存到最近使用
        this.saveToRecentLandmarks(landmarkId, address);

        this.showSuccessMessage('已選擇地標：' + address);
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
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    this.displayLandmarkResults(data.data, '#landmarkPopularResults');
                } else {
                    $('#landmarkPopularResults').html('<div class="text-center py-4"><p class="text-muted">暫無熱門地標</p></div>');
                }
            })
            .catch(() => {
                $('#landmarkPopularResults').html('<div class="alert alert-danger">載入失敗</div>');
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
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    this.displayLandmarkResults(data.data, '#landmarkRecentResults');
                } else {
                    $('#landmarkRecentResults').html('<div class="text-center py-4"><p class="text-muted">無法載入最近使用記錄</p></div>');
                }
            })
            .catch(() => {
                $('#landmarkRecentResults').html('<div class="alert alert-danger">載入失敗</div>');
            });
    }

    /**
     * 處理分類篩選
     */
    handleCategoryFilter(e) {
        const category = e.target.dataset.category;
        const button = e.target;

        // 更新按鈕狀態
        $('.category-filter').removeClass('active');
        $(button).addClass('active');

        // 篩選結果
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
            'medical': { text: '醫療', class: 'bg-danger' },
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
            'medical': 'fas fa-hospital',
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
                            ${this.truncateText(order.pickup_address, 30)}
                        </small>
                    </td>
                    <td>
                        <small class="h6" title="${order.dropoff_address}">
                            ${this.truncateText(order.dropoff_address, 30)}
                        </small>
                    </td>
                    <td class="text-center">${order.companions || 0}</td>
                    <td class="text-center">
                        <i class="fas fa-${order.wheelchair ? 'check text-success' : 'times text-muted'}"></i>
                    </td>
                    <td class="text-center">
                        <i class="fas fa-${order.stair_machine ? 'check text-success' : 'times text-muted'}"></i>
                    </td>
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
        $('select[name="wheelchair"]').val(order.wheelchair ? '1' : '0');
        $('select[name="stair_machine"]').val(order.stair_machine ? '1' : '0');

        // 填入地址欄位
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
            'cancelled': '已取消'
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