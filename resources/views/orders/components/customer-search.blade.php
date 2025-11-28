<div class="card mb-2">
    <div class="card-header bg-info">
         <div class="d-flex  align-items-center">
            <h5 class="mb-0 pe-3">
                <i class="fas fa-search me-2"></i>個案搜尋
            </h5>
            <a href="{{ route('customers.create', array_merge(['return_to' => 'orders'], request()->only(['keyword', 'driver_fleet_number', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine', 'service_company']))) }}" class="btn btn-outline-dark">
                <i class="fas fa-user-plus me-2"></i>新增個案
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
            <div class="col-md-2">
                <label for="keyword" class="form-label">搜尋關鍵字</label>
                <input type="text" name="keyword" id="keyword" class="form-control"
                       placeholder="輸入姓名、電話、ID或地址"
                       value="{{ request('keyword') }}">
            </div>
            <div class="col-md-1">
                <label for="driver_fleet_number" class="form-label">司機隊編</label>
                <input type="text" name="driver_fleet_number" id="driver_fleet_number" class="form-control"
                       placeholder="輸入司機隊編"
                       value="{{ request('driver_fleet_number') }}">
            </div>
            <div class="col-md-1">
                <label for="order_type" class="form-label">訂單來源</label>
                <select name="order_type" id="order_type" class="form-select">
                    <option value="">全部</option>
                    <option value="新北長照" {{ request('order_type') == '新北長照' ? 'selected' : '' }}>新北長照</option>
                    <option value="台北長照" {{ request('order_type') == '台北長照' ? 'selected' : '' }}>台北長照</option>
                </select>
            </div>
            <div class="col-md-1">
                <label for="stair_machine" class="form-label">爬梯機</label>
                <select name="stair_machine" id="stair_machine" class="form-select">
                    <option value="">全部</option>
                    <option value="是" {{ request('stair_machine') == '是' ? 'selected' : '' }}>是</option>
                    <option value="否" {{ request('stair_machine') == '否' ? 'selected' : '' }}>否</option>
                    <!--<option value="未知" {{ request('stair_machine') == '未知' ? 'selected' : '' }}>未知</option>-->
                </select>
            </div>
            <div class="col-md-1">
                <label for="service_company" class="form-label">服務公司</label>
                <select name="service_company" id="service_company" class="form-select">
                    <option value="">全部</option>
                    <option value="大立亨" {{ request('service_company') == '大立亨' ? 'selected' : '' }}>大立亨</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">開始日期</label>
                <input type="date" name="start_date" id="start_date" class="form-control"
                       value="{{ request('start_date') ?? \Carbon\Carbon::today()->startOfMonth()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">結束日期</label>
                <input type="date" name="end_date" id="end_date" class="form-control"
                       value="{{ request('end_date') ?? \Carbon\Carbon::now()->addMonth()->endOfMonth()->toDateString() }}">
            </div>
            <div class="col-6">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-search me-2"></i>搜尋
                </button>
                <a href="{{ route('orders.index') }}" class="btn btn-dark">
                    <i class="fas fa-undo me-2"></i>清除
                </a>
            </div>
            <div class="col-4">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-search me-2"></i>搜尋
                </button>
                <button type="button" class="btn btn-outline-dark" onclick="setQuickDate('today')">
                    <i class="fas fa-calendar-day me-2"></i>今天
                </button>
                <button type="button" class="btn btn-outline-dark" onclick="setQuickDate('tomorrow')">
                    <i class="fas fa-calendar-day me-2"></i>明天
                </button>
                <button type="button" class="btn btn-outline-dark" onclick="setQuickDate('daytomorrow')">
                    <i class="fas fa-calendar-day me-2"></i>後天
                </button>
                <button type="button" class="btn btn-outline-dark" onclick="setQuickDate('dayAfterTomorrow')">
                    <i class="fas fa-calendar-day me-2"></i>大後天
                </button>
            </div>
        </form>

        {{-- 個案搜尋結果 --}}
        @if(request()->filled('keyword') || request()->filled('customer_id'))
            <hr class="my-3">


            @if(isset($customers) && $customers->isEmpty() && $orders->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>查無符合的資料（客戶或訂單）
                </div>

            @elseif(isset($customers) && $customers->count() > 1)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>找到多筆符合資料，請選擇一位客戶：
                </div>
                <div class="list-group">
                    @foreach($customers as $customer)
                        <a href="{{ route('orders.index', array_merge(['customer_id' => $customer->id], request()->only(['keyword', 'start_date', 'end_date', 'order_type', 'stair_machine', 'service_company']))) }}"
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                    <div class="col-md-1">
                                        <h5 class="mb-0">{{ $customer->name }}</h5>
                                    </div>
                                    <div class="col-md-2">
                                        <h5 class="mb-0 ps-2">{{ $customer->id_number }}</h5>
                                    </div>
                                    <div class="col-md-2">
                                        <h5 class="mb-0">
                                            {{ collect($customer->phone_number)->first() ?? '無電話' }}
                                        </h5>
                                    </div>
                                    <div class="col-md-5">
                                        <h5 class="mb-0">
                                            {{ collect($customer->addresses)->first() ?? '無地址' }}
                                        </h5>
                                    </div>
                                    <div class="col-md-2" style="color: rgb(205, 100, 26)">
                                        <h5 class="mb-0 ps-5">點擊選擇</h5>
                                    </div>
                            </div>
                        </a>
                    @endforeach
                </div>

            @elseif(isset($customers) && $customers->count() == 1)
                @php $customer = $customers->first(); @endphp
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>個案資料
                        </h6>
                    </div>
                    <div class="card-body" style="font-size: 19px">
                        <div class="row">
                            <div class="col-md-2">
                                <strong>姓名：</strong>{{ $customer->name }}<br>
                                <strong>ID：</strong>{{ $customer->id_number }}
                            </div>
                            <div class="col-md-2">
                                <strong>電話：</strong>{{ collect($customer->phone_number)->filter()->implode(' / ') ?: '無電話' }}
                                <br>
                                <strong>生日：</strong>
                                @if($customer->birthday && $customer->birthday instanceof \Carbon\Carbon)
                                    {{ sprintf('%d-%s', $customer->birthday->year - 1911, $customer->birthday->format('m-d')) }}
                                @else
                                    N/A
                                @endif
                            </div>
                            <div class="col-md-2">
                                <strong>輪椅：</strong>
                                <span class="editable-field {{ $customer->wheelchair === '未知' ? 'text-danger' : '' }}"
                                      data-field="wheelchair"
                                      data-customer-id="{{ $customer->id }}"
                                      style="cursor: pointer; text-decoration: underline;"
                                      title="點擊編輯">
                                    {{ $customer->wheelchair }}
                                </span>
                                <br>
                                <strong>爬梯機：</strong>
                                <span class="editable-field"
                                      data-field="stair_climbing_machine"
                                      data-customer-id="{{ $customer->id }}"
                                      style="cursor: pointer; text-decoration: underline;"
                                      title="點擊編輯">
                                    {{ $customer->stair_climbing_machine }}
                                </span>
                            </div>
                            <div class="col-md-2">
                                <strong>個管師：</strong>
                                <span class="editable-field"
                                      data-field="a_manager"
                                      data-customer-id="{{ $customer->id }}"
                                      style="cursor: pointer; text-decoration: underline;"
                                      title="點擊編輯">
                                    {{ $customer->a_manager ?: '無' }}
                                </span>
                            </div>
                            <div class="col-md-2">
                                <strong>特殊狀態：</strong>
                                @if(in_array($customer->special_status, ['黑名單', '網頁']))
                                    <span class="badge bg-warning">{{ $customer->special_status }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $customer->special_status }}</span>
                                @endif
                                <br>
                                <strong>開案狀態：</strong>
                                @if(in_array($customer->status, ['暫停中', '已結案']))
                                    <span class="badge bg-danger">{{ $customer->status }}</span>
                                @else
                                    <span class="badge bg-success">{{ $customer->status }}</span>
                                @endif
                            </div>
                            @if(( $customer->status ?? '') == '開案中')
                                <div class="col-md-1 d-flex align-items-center">
                                    <a href="{{ route('orders.create', array_merge(['customer_id' => $customer->id], request()->only(['keyword', 'start_date', 'end_date', 'order_type', 'stair_machine', 'service_company']))) }}"
                                    class="btn btn-outline-success btn-sm fs-6"
                                    style="width: 100%;"
                                    >
                                        <i class="fas fa-plus me-1 "></i>建立訂單
                                    </a>
                                </div>
                                <div class="col-md-1 d-flex align-items-center">
                                    <a href="{{ route('customers.edit', array_merge(['customer' => $customer->id, 'return_to' => 'orders'], request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine', 'service_company']))) }}"
                                    class="btn btn-outline-secondary btn-sm fs-6"
                                    style="width: 100%;"
                                    >
                                        <i class="fas fa-user-edit me-1"></i>編輯個案
                                    </a>
                                </div>
                            @else
                                <div class="col-md-1 d-flex align-items-center">
                                    <span class="badge bg-danger fs-6">禁止建檔</span>
                                </div>
                                    <div class="col-md-1 d-flex align-items-center">
                                    <a href="{{ route('customers.edit', array_merge(['customer' => $customer->id, 'return_to' => 'orders'], request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine', 'service_company']))) }}"
                                    class="btn btn-outline-secondary btn-sm fs-6"
                                    style="width: 100%;"
                                    >
                                        <i class="fas fa-user-edit me-1"></i>編輯個案
                                    </a>
                                </div>
                            @endif
                        </div>
                        <hr style="border-top: 1px solid #000000;">
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <strong>住址：</strong><br>{{ collect($customer->addresses)->filter()->implode(' / ') ?: '無地址' }}
                            </div>
                            <div class="col-md-4">
                                <strong>乘客備註：</strong><br>
                                <span class="editable-field"
                                      data-field="note"
                                      data-customer-id="{{ $customer->id }}"
                                      style="color: red; cursor: pointer; text-decoration: underline;"
                                      title="點擊編輯"
                                      id="customer-note-{{ $customer->id }}">
                                    {{ $customer->note ?: '無備註' }}
                                </span>
                            </div>
                            <div class="col-md-2">
                                <strong>服務單位：</strong>
                                <span class="editable-field"
                                      data-field="service_company"
                                      data-customer-id="{{ $customer->id }}"
                                      style="cursor: pointer; text-decoration: underline;"
                                      title="點擊編輯">
                                    {{ $customer->service_company ?: '無' }}
                                </span>
                                <br>
                                <strong>訂單來源：</strong>{{ $customer->county_care }}<br>
                                <strong>照會日期：</strong>{{ $customer->referral_date ? $customer->referral_date->format('Y-m-d') : 'N/A' }}
                            </div>
                            <div class="col-md-2 text-center ">
                                @if($customer->stair_climbing_machine === '是')
                                    @php
                                        $note = $customer->note;
                                        $keyword = '開發';
                                        $displayNote = '';

                                        // 只有當 note 包含「開發」關鍵字時才顯示
                                        if ($note && mb_strpos($note, $keyword) !== false) {
                                            // 找到關鍵字的位置
                                            $position = mb_strpos($note, $keyword);
                                            // 計算起始位置（關鍵字前4個字元）
                                            $start = max(0, $position - 4);
                                            // 擷取：前4個字元 + 關鍵字（2個字）= 共6個字
                                            $displayNote = mb_substr($note, $start, $position - $start + 2);
                                        }
                                    @endphp

                                    <h4>
                                        <label style="color:red" >!!!請留意!!!</label>
                                        <br>
                                        <span class="badge bg-danger">
                                            爬梯機個案@if($displayNote) {{ $displayNote }}@endif
                                        </span>
                                    </h4>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            @endif
        @endif
    </div>
</div>

{{-- 包含通用編輯欄位 Modal --}}
@include('orders.components.edit-field-modal')

<script>
// 編輯欄位的配置
const fieldConfig = {
    'note': {
        label: '乘客備註',
        type: 'textarea'
    },
    'a_manager': {
        label: '個管師',
        type: 'textarea'
    },
    'wheelchair': {
        label: '輪椅',
        type: 'select'
    },
    'stair_climbing_machine': {
        label: '爬梯機',
        type: 'select'
    },
    'service_company': {
        label: '服務單位',
        type: 'textarea'
    }
};

// 使用事件代理綁定編輯欄位的點擊事件
document.addEventListener('click', function(e) {
    const editableField = e.target.closest('.editable-field');
    if (editableField) {
        e.preventDefault();
        const fieldName = editableField.dataset.field;
        const customerId = editableField.dataset.customerId;
        const currentValue = editableField.textContent.trim();

        openEditFieldModal(customerId, fieldName, currentValue);
    }
});

/**
 * 打開編輯欄位 Modal
 */
function openEditFieldModal(customerId, fieldName, currentValue) {
    const config = fieldConfig[fieldName];
    if (!config) {
        console.error('未知的欄位:', fieldName);
        return;
    }

    // 設定 Modal 標題和欄位信息
    const modal = new bootstrap.Modal(document.getElementById('editFieldModal'));
    document.getElementById('editFieldTitle').textContent = `編輯${config.label}`;
    document.getElementById('editFieldName').value = fieldName;

    // 根據欄位類型顯示相應的輸入控件
    const textContainer = document.getElementById('textFieldContainer');
    const selectContainer = document.getElementById('selectFieldContainer');

    if (config.type === 'textarea') {
        textContainer.style.display = 'block';
        selectContainer.style.display = 'none';
        document.getElementById('editFieldLabel').textContent = config.label;
        document.getElementById('editFieldValue').value = currentValue === '無備註' || currentValue === '無' ? '' : currentValue;
    } else if (config.type === 'select') {
        textContainer.style.display = 'none';
        selectContainer.style.display = 'block';
        document.getElementById('editSelectLabel').textContent = config.label;
        document.getElementById('editFieldSelect').value = currentValue;
    }

    // 儲存客戶 ID 供保存時使用
    document.getElementById('editFieldModal').dataset.customerId = customerId;

    modal.show();
}

/**
 * 保存編輯的欄位
 */
function saveEditField() {
    const modal = document.getElementById('editFieldModal');
    const customerId = modal.dataset.customerId;
    const fieldName = document.getElementById('editFieldName').value;
    const config = fieldConfig[fieldName];
    const saveBtn = document.getElementById('editFieldSaveBtn');

    // 根據欄位類型取得值
    let fieldValue;
    if (config.type === 'textarea') {
        fieldValue = document.getElementById('editFieldValue').value;
    } else if (config.type === 'select') {
        fieldValue = document.getElementById('editFieldSelect').value;
    }

    // 顯示載入狀態
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>儲存中...';
    saveBtn.disabled = true;

    // 發送 AJAX 請求
    fetch(`/customers/${customerId}/field`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            field_name: fieldName,
            value: fieldValue
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // 更新頁面上的欄位顯示
            const fieldElement = document.querySelector(`.editable-field[data-customer-id="${customerId}"][data-field="${fieldName}"]`);
            if (fieldElement) {
                // 處理特殊情況：如果值為空，顯示「無」
                let displayValue = fieldValue;
                if (!displayValue || displayValue.trim() === '') {
                    if (fieldName === 'note') {
                        displayValue = '無備註';
                    } else {
                        displayValue = '無';
                    }
                }
                fieldElement.textContent = displayValue;
            }

            // 關閉 Modal
            bootstrap.Modal.getInstance(modal).hide();

            // 顯示成功訊息
            showSuccessMessage(`${config.label}已更新`);
        } else {
            showErrorMessage(data.message || '更新失敗');
        }
    })
    .catch(error => {
        console.error('更新失敗:', error);
        showErrorMessage('更新失敗，請稍後再試');
    })
    .finally(() => {
        // 恢復按鈕狀態
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// 綁定保存按鈕事件（使用事件代理，等待頁面加載完成）
document.addEventListener('click', function(e) {
    if (e.target.id === 'editFieldSaveBtn') {
        e.preventDefault();
        saveEditField();
    }
});

function setQuickDate(period) {
    // 使用伺服器端的今天日期作為基準，確保跨日時的一致性
    const serverToday = '{{ \Carbon\Carbon::today()->toDateString() }}';
    const today = new Date(serverToday + 'T00:00:00');
    let targetDate;

    switch(period) {
        case 'today':
            targetDate = new Date(today);
            break;
        case 'tomorrow':
            targetDate = new Date(today);
            targetDate.setDate(today.getDate() + 1);
            break;
        case 'daytomorrow':
            targetDate = new Date(today);
            targetDate.setDate(today.getDate() + 2);
            break;
        case 'dayAfterTomorrow':
            targetDate = new Date(today);
            targetDate.setDate(today.getDate() + 3);
            break;
        default:
            return;
    }

    // 格式化為 YYYY-MM-DD (使用本地時間，避免 UTC 時區轉換問題)
    const year = targetDate.getFullYear();
    const month = String(targetDate.getMonth() + 1).padStart(2, '0');
    const day = String(targetDate.getDate()).padStart(2, '0');
    const dateString = `${year}-${month}-${day}`;

    // 設定開始日期和結束日期
    document.getElementById('start_date').value = dateString;
    document.getElementById('end_date').value = dateString;
}
</script>
