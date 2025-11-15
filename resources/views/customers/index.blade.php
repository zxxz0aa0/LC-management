@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="mb-0">個案列表</h3>
        </div>
    </div>

    <div class="card-body">

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
                @if (session('import_stats'))
                    @php $stats = session('import_stats'); @endphp
                    <div class="mt-2 small">
                        <strong>檔案：</strong>{{ $stats['filename'] }} |
                        <strong>總處理：</strong>{{ number_format($stats['total_processed']) }} 筆 |
                        <strong>成功：</strong>{{ number_format($stats['success_count']) }} 筆 |
                        <strong>失敗：</strong>{{ number_format($stats['fail_count']) }} 筆
                    </div>
                @endif
            </div>
        @endif

        @if (session('import_error'))
            <div class="alert alert-danger">{{ session('import_error') }}</div>
        @endif

        @if (session('import_errors') && count(session('import_errors')) > 0)
            <div class="alert alert-warning">
                <strong>以下資料匯入失敗：</strong>
                <ul class="mb-0">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="GET" action="{{ route('customers.index') }}" class="mb-3">
            <div class="row g-3">
                <div class="col-md-2">
                    <label for="keyword" class="form-label">關鍵字搜尋</label>
                    <input type="text" name="keyword" id="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="輸入姓名、電話或身分證查詢">
                    @if(isset($searchError))
                        <small class="text-danger mt-1 d-block">{{ $searchError }}</small>
                    @endif
                </div>
                <div class="col-md-2">
                    <label for="referral_date" class="form-label">照會日期</label>
                    <input type="date" name="referral_date" id="referral_date" value="{{ request('referral_date') }}" class="form-control">
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="btn-group me-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>搜尋
                        </button>
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-undo me-1"></i>清除
                        </a>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12 text-end">
                    <!-- 匯入匯出功能 -->
                    <div class="btn-group">
                        <a href="{{ route('customers.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>新增個案
                        </a>
                        <!--<a href="{{ route('customers.export') }}" class="btn btn-outline-success">
                            <i class="fas fa-download me-1"></i>匯出 Excel
                        </a>-->
                        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="fas fa-upload me-1"></i>匯入 Excel
                        </button>
                        <a href="{{ route('customers.template') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-excel me-1"></i>下載範例
                        </a>
                    </div>
                </div>
            </div>
        </form>


        <div class="table-responsive">
            <table id="customers-table" class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-success align-middle">
                    <tr>
                        <!--<th style="width:40px;"><input type="checkbox" id="select-all"></th>--> <!--備註：可批次刪除選項-->
                        <th style="width: 7%;">照會日期</th>
                        <th style="width: 7%;">姓名</th>
                        <th style="width: 7%;">身分證字號</th>
                        <th style="width: 10%;">聯絡電話</th>
                        <th style="width: 19%;">地址</th>
                        <th style="width: 8%;">個案來源</th>
                        <th style="width: 8%;">服務公司</th>
                        <th style="width: 6%;">輪椅</th>
                        <th style="width: 6%;">爬梯</th>
                        <th style="width: 6%;">特殊</th>
                        <th style="width: 6%;">狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <!--<td><input class="me-2" type="checkbox" name="ids[]" value="{{ $customer->id }}" form="batch-delete-form">{{ $loop->iteration }}</td>-->
                            <td>{{ $customer->referral_date ? $customer->referral_date->format('Y-m-d') : 'N/A' }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->id_number }}</td>
                            <td>{{ is_array($customer->phone_number) ? implode(' / ', $customer->phone_number) : $customer->phone_number }}</td>
                            <td>{{ is_array($customer->addresses) ? implode(', ', $customer->addresses) : $customer->addresses }}</td>
                            <td>{{ $customer->county_care }}</td>
                            <td>{{ $customer->service_company }}</td>
                            <td>{{ $customer->wheelchair }}</td>
                            <td>{{ $customer->stair_climbing_machine }}</td>
                            <td>{{ $customer->special_status === '一般' ? '' : $customer->special_status }}</td>
                            <td>
                                @if($customer->status === '開案中')
                                    <span class="badge bg-success text-dark">開案中</span>
                                @elseif($customer->status === '暫停中')
                                    <span class="badge bg-warning">暫停中</span>
                                @else
                                    <span class="badge bg-danger">已結案</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1"  style="width:150px;" >
                                    <a href="{{ route('customers.edit', ['customer' => $customer, 'keyword' => request('keyword')]) }}" class="btn btn-sm btn-warning">編輯</a>
                                    <!--<form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline m-0 p-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('確定要刪除嗎？');">刪除</button>
                                    </form>-->
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#eventModal{{ $customer->id }}">事件</button>
                                </div>
                                <!-- Modal -->
                                    <div class="modal fade" id="eventModal{{ $customer->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">事件紀錄：{{ $customer->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">

                                            {{-- 新增事件 --}}
                                            <form method="POST" action="{{ route('customer-events.store') }}" class="row g-2 mb-3">
                                                @csrf
                                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                                <div class="col-md-3">
                                                    <input type="datetime-local" name="event_date" class="form-control" required value="{{ now()->format('Y-m-d\TH:i') }}">
                                                </div>
                                                <div class="col-md-8">
                                                    <input type="text" name="event" class="form-control" placeholder="事件內容" required>
                                                </div>
                                                <div class="col-md-1">
                                                    <button class="btn btn-success w-100">新增</button>
                                                </div>
                                            </form>

                                            {{-- 顯示事件清單 --}}
                                            <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                <th class="col-md-2 align-middle text-center">建檔日期</th>
                                                <th class="col-md-7 align-middle text-center">事件</th>
                                                <th class="col-md-1 align-middle text-center">建立人</th>
                                                <th class="col-md-2 align-middle text-center">操作</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($customer->events as $event)
                                                <tr id="event-row-{{ $event->id }}">
                                                    <td class="align-middle text-center">{{ \Carbon\Carbon::parse($event->event_date)->format('Y-m-d H:i') }}</td>
                                                    <td class="align-middle">
                                                    <form method="POST" action="{{ route('customer-events.update', $event->id) }}" id="update-form-{{ $event->id }}">
                                                        @csrf @method('PUT')
                                                        <input type="text" name="event" value="{{ $event->event }}" class="form-control">
                                                    </form>
                                                    </td>
                                                    <td class="align-middle text-center">{{ $event->creator->name ?? 'N/A' }}</td>
                                                    <td class="d-flex gap-1 align-middle justify-content-center">
                                                    <button type="submit" form="update-form-{{ $event->id }}" class="btn btn-sm btn-primary">儲存</button>
                                                    <form method="POST" action="{{ route('customer-events.destroy', $event->id) }}" onsubmit="return confirm('確定要刪除嗎？')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">刪除</button>
                                                    </form>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            </table>

                                        </div>
                                        </div>
                                    </div>
                                    </div>
                                <!-- Modal -->
                            </td>
                        </tr>

                    @endforeach
                </tbody>
            </table>
                    <!--<form id="batch-delete-form" method="POST" action="{{ route('customers.batchDelete') }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('確定要刪除選取的客戶嗎？')">
                            批次刪除
                        </button>
                    </form>-->
        </div>
        
    </div>
</div>

<!-- 匯入 Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">匯入客戶資料</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="importFile" class="form-label">選擇 Excel 檔案</label>
                        <input type="file" name="file" id="importFile" accept=".xlsx,.xls" class="form-control" required>
                        <div class="form-text">支援 .xlsx 和 .xls 格式</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">匯入方式</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-primary">
                                            <i class="fas fa-bolt me-2"></i>即時匯入
                                        </h6>
                                        <p class="card-text small">適用於少量資料（建議 < 1000 筆）</p>
                                        <ul class="list-unstyled small text-muted">
                                            <li>• 立即處理並顯示結果</li>
                                            <li>• 處理時間：約 30-60 秒</li>
                                            <li>• 瀏覽器等待期間</li>
                                        </ul>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="submitImport('normal')">
                                            選擇即時匯入
                                        </button>
                                    </div>
                                </div>
                            </div>

                           <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-success">
                                            <i class="fas fa-clock me-2"></i>佇列匯入
                                        </h6>
                                        <p class="card-text small">適用於大量資料（建議 > 1000 筆）</p>
                                        <ul class="list-unstyled small text-muted">
                                            <li>• 背景處理，可監控進度</li>
                                            <li>• 處理時間：約 3-5 分鐘</li>
                                            <li>• 不會占用瀏覽器</li>
                                        </ul>
                                        <button type="button" class="btn btn-success btn-sm" onclick="submitImport('queued')">
                                            選擇佇列匯入
                                        </button>
                                    </div>
                               </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <strong>💡 使用建議：</strong>
                        <ul class="mb-0">
                            <li>資料量 < 1000 筆：選擇「即時匯入」</li>
                            <li>資料量 ≥ 1000 筆：選擇「佇列匯入」</li>
                            <li>請先下載範例檔案，並按照範例格式填入資料</li>
                        </ul>
                    </div>
                   </form>
                </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
            </div>
        </div>
    </div>
</div>

<script>
function submitImport(type) {
        const form = document.getElementById('importForm');
        const fileInput = document.getElementById('importFile');

    if (!fileInput.files.length) {
        alert('請選擇要匯入的檔案');
        return;
    }

    if (type === 'normal') {
        form.action = "{{ route('customers.import') }}";
    } else if (type === 'queued') {
        form.action = "{{ route('customers.queuedImport') }}";    
    }
    
    form.submit();

    // 關閉 modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('importModal'));
    modal.hide();
}
</script>

@endsection

@push('scripts')
<!-- DataTables 初始腳本 -->
<script>
    $(document).ready(function () {
        $('#customers-table').DataTable({
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
            }
        });
    });

    // 全選 / 取消全選
    $('#select-all').click(function () {
        $('input[name="ids[]"]').prop('checked', this.checked);
    });
</script>
@endpush

