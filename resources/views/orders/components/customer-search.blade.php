<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fas fa-search me-2"></i>客戶搜尋
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
            <div class="col-md-6">
                <label for="keyword" class="form-label">搜尋關鍵字</label>
                <input type="text" name="keyword" id="keyword" class="form-control"
                       placeholder="輸入姓名、電話或身分證字號"
                       value="{{ request('keyword') }}">
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">開始日期</label>
                <input type="date" name="start_date" id="start_date" class="form-control"
                       value="{{ request('start_date') ?? \Carbon\Carbon::today()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">結束日期</label>
                <input type="date" name="end_date" id="end_date" class="form-control"
                       value="{{ request('end_date') ?? \Carbon\Carbon::now()->endOfMonth()->toDateString() }}">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>搜尋
                </button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-undo me-2"></i>清除
                </a>
            </div>
        </form>

        {{-- 客戶搜尋結果 --}}
        @if(request()->filled('keyword') || request()->filled('customer_id'))
            <hr class="my-4">
            <h6 class="text-muted mb-3">搜尋結果</h6>
            
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
                        <a href="{{ route('orders.index', ['customer_id' => $customer->id, 'keyword' => request('keyword')]) }}" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $customer->name }}</h6>
                                    <p class="mb-1">{{ $customer->id_number }}</p>
                                    <small class="text-muted">
                                        {{ is_array($customer->phone_number) ? $customer->phone_number[0] : $customer->phone_number }} / 
                                        {{ is_array($customer->addresses) ? $customer->addresses[0] : $customer->addresses }}
                                    </small>
                                </div>
                                <small class="text-muted">點擊選擇</small>
                            </div>
                        </a>
                    @endforeach
                </div>

            @elseif(isset($customers) && $customers->count() == 1)
                @php $customer = $customers->first(); @endphp
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>客戶資料
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <strong>姓名：</strong><br>{{ $customer->name }}
                            </div>
                            <div class="col-md-2">
                                <strong>身分證字號：</strong><br>{{ $customer->id_number }}
                            </div>
                            <div class="col-md-2">
                                <strong>電話：</strong><br>{{ is_array($customer->phone_number) ? implode(' / ', $customer->phone_number) : $customer->phone_number }}
                            </div>
                            <div class="col-md-3">
                                <strong>住址：</strong><br>{{ is_array($customer->addresses) ? implode(' / ', $customer->addresses) : $customer->addresses }}
                            </div>
                            <div class="col-md-2">
                                <strong>身份別：</strong><br>{{ $customer->identity }}
                            </div>
                            <div class="col-md-1">
                                <a href="{{ route('orders.create', ['customer_id' => $customer->id, 'keyword' => request('keyword')]) }}" 
                                   class="btn btn-success btn-sm">
                                    <i class="fas fa-plus me-1"></i>建立訂單
                                </a>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <strong>狀態：</strong>
                                @if(in_array($customer->status, ['暫停中', '已結案']))
                                    <span class="badge bg-danger">{{ $customer->status }}</span>
                                @else
                                    <span class="badge bg-success">{{ $customer->status }}</span>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <strong>特殊狀態：</strong>
                                @if(in_array($customer->special_status, ['黑名單', 'VIP']))
                                    <span class="badge bg-warning">{{ $customer->special_status }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ $customer->special_status }}</span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <strong>乘客備註：</strong>{{ $customer->note }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>