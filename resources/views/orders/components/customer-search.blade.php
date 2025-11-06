<div class="card mb-2">
    <div class="card-header bg-info">
         <div class="d-flex  align-items-center">
            <h5 class="mb-0 pe-3">
                <i class="fas fa-search me-2"></i>個案搜尋
            </h5>
            <a href="{{ route('customers.create', array_merge(['return_to' => 'orders'], request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine']))) }}" class="btn btn-outline-dark">
                <i class="fas fa-user-plus me-2"></i>新增個案
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
            <div class="col-md-2">
                <label for="keyword" class="form-label">搜尋關鍵字</label>
                <input type="text" name="keyword" id="keyword" class="form-control"
                       placeholder="輸入姓名、電話或身分證字號"
                       value="{{ request('keyword') }}">
            </div>
            <div class="col-md-2">
                <label for="order_type" class="form-label">訂單來源</label>
                <select name="order_type" id="order_type" class="form-select">
                    <option value="">全部</option>
                    <option value="新北長照" {{ request('order_type') == '新北長照' ? 'selected' : '' }}>新北長照</option>
                    <option value="台北長照" {{ request('order_type') == '台北長照' ? 'selected' : '' }}>台北長照</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="stair_machine" class="form-label">爬梯機</label>
                <select name="stair_machine" id="stair_machine" class="form-select">
                    <option value="">全部</option>
                    <option value="是" {{ request('stair_machine') == '是' ? 'selected' : '' }}>是</option>
                    <option value="否" {{ request('stair_machine') == '否' ? 'selected' : '' }}>否</option>
                    <!--<option value="未知" {{ request('stair_machine') == '未知' ? 'selected' : '' }}>未知</option>-->
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">開始日期</label>
                <input type="date" name="start_date" id="start_date" class="form-control"
                       value="{{ request('start_date') ?? \Carbon\Carbon::today()->toDateString() }}">
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
            <div class="col-6">
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


            @if(isset($customers) && $customers->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>查無符合的客戶資料
                </div>

            @elseif(isset($customers) && $customers->count() > 1)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>找到多筆符合資料，請選擇一位客戶：
                </div>
                <div class="list-group">
                    @foreach($customers as $customer)
                        <a href="{{ route('orders.index', array_merge(['customer_id' => $customer->id], request()->only(['keyword', 'start_date', 'end_date', 'order_type', 'stair_machine']))) }}"
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
                                <strong>姓名：</strong><br>{{ $customer->name }}
                            </div>
                            <div class="col-md-2">
                                <strong>身分證字號：</strong><br>{{ $customer->id_number }}
                            </div>
                            <div class="col-md-2">
                                <strong>電話：</strong><br>{{ collect($customer->phone_number)->filter()->implode(' / ') ?: '無電話' }}
                            </div>
                            <div class="col-md-2">
                                <strong>輪椅：</strong>
                                <span class="{{ $customer->wheelchair === '未知' ? 'text-danger' : '' }}">
                                    {{ $customer->wheelchair }}
                                </span>
                                <br>
                                <strong>爬梯機：</strong>{{ $customer->stair_climbing_machine }}
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
                                <div class="col-md-1">
                                    <a href="{{ route('orders.create', array_merge(['customer_id' => $customer->id], request()->only(['keyword', 'start_date', 'end_date', 'order_type', 'stair_machine']))) }}"
                                    class="btn btn-success btn-sm fs-6 d-flex align-items-center justify-content-center"
                                    style="width: 100%;"
                                    >
                                        <i class="fas fa-plus me-1 "></i>建立訂單
                                    </a>
                                </div>
                                <div class="col-md-1">
                                    <a href="{{ route('customers.edit', array_merge(['customer' => $customer->id, 'return_to' => 'orders'], request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine']))) }}"
                                    class="btn btn-warning btn-sm fs-6 d-flex align-items-center justify-content-center"
                                    style="width: 100%;"
                                    >
                                        <i class="fas fa-user-edit me-1"></i>編輯個案
                                    </a>
                                </div>
                            @else
                                <div class="col-md-2">
                                    <span class="badge bg-danger fs-6">結案或暫停中</span>
                                </div>
                            @endif
                        </div>
                        <hr style="border-top: 1px solid #000000;">
                        <div class="row mt-4">
                            <div class="col-md-3">
                                <strong>住址：</strong><br>{{ collect($customer->addresses)->filter()->implode(' / ') ?: '無地址' }}
                            </div>
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-start">
                                    <button type="button" class="btn btn-sm btn-outline-success ms-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#noteModal{{ $customer->id }}"
                                            title="編輯備註">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <div class="flex-grow-1 ms-2">
                                        <strong>乘客備註：</strong><br>
                                        <span style="color: red" id="customer-note-{{ $customer->id }}">{{ $customer->note ?: '無備註' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <strong>訂單來源：</strong>{{ $customer->county_care }}<br>
                                <strong>照會日期：</strong>{{ $customer->referral_date ? $customer->referral_date->format('Y-m-d') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 備註編輯 Modal --}}
                <div class="modal fade" id="noteModal{{ $customer->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">編輯備註：{{ $customer->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="noteForm{{ $customer->id }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="note{{ $customer->id }}" class="form-label">客戶備註</label>
                                        <textarea class="form-control"
                                                  id="note{{ $customer->id }}"
                                                  name="note"
                                                  rows="4"
                                                  placeholder="請輸入客戶備註..."
                                                  maxlength="1000">{{ $customer->note }}</textarea>
                                        <div class="form-text">最多1000字</div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                <button type="button" class="btn btn-primary" onclick="updateCustomerNote({{ $customer->id }})">儲存</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<script>
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
