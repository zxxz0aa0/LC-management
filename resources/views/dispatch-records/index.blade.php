@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>排趟記錄</h2>
        <a href="{{ route('manual-dispatch.index') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新增排趟
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- 搜尋表單 -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-search"></i> 搜尋條件</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('dispatch-records.index') }}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">開始日期</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">結束日期</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="driver_id" class="form-label">司機</label>
                        <select class="form-select" id="driver_id" name="driver_id">
                            <option value="">-- 全部 --</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ request('driver_id') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->fleet_number }} - {{ $driver->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="keyword" class="form-label">關鍵字</label>
                        <input type="text" class="form-control" id="keyword" name="keyword" value="{{ request('keyword') }}" placeholder="司機名稱、排趟名稱">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> 搜尋
                        </button>
                        <a href="{{ route('dispatch-records.index') }}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> 清除條件
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 排趟記錄列表 -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-history"></i> 排趟記錄
                <span class="badge bg-light text-dark">共 {{ $records->total() }} 筆</span>
                <small class="ms-2">(最近 2 個月)</small>
            </h5>
        </div>
        <div class="card-body">
            @if($records->isEmpty())
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> 目前沒有排趟記錄
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:2%">#</th>
                                <th style="width:12%">排趟日期/排趟時間/排程隊編/排趟人員</th>
                                <th style="width:5%">司機 / 隊編</th>
                                <th class="text-center" style="width:10%">訂單數</th>
                                 <!--<th style="width:12%">排趟日期</th>
                                <th style="width:10%">排趟時間</th>
                                <th style="width:8%">排趟人</th>-->
                                <th style="width:10%">登打處理狀態</th>
                                <th class="text-center" style="width:10%">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $index => $record)
                                <tr>
                                    <td class="text-center">{{ $records->firstItem() + $index }}</td>
                                    <td>
                                        <strong>{{ $record->dispatch_name }}</strong>
                                    </td>
                                    <td>
                                        @if($record->driver)
                                           {{ $record->driver_name }} <span class="badge bg-info">{{ $record->driver_fleet_number }}</span>
                                        @else
                                            <span class="text-muted">{{ $record->driver_name }} (已刪除)</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $record->order_count }} 筆</span>
                                    </td>
                                     <!--<td>
                                        <i class="fas fa-calendar"></i>
                                        {{ $record->dispatch_date ? $record->dispatch_date->format('Y-m-d') : 'N/A' }}
                                    </td>
                                    <td>
                                        {{ $record->performed_at->format('H:i') }}
                                    </td>
                                    <td>
                                        {{ $record->performer->name ?? 'N/A' }}
                                    </td>-->
                                    <td id="status-cell-{{ $record->id }}">
                                        <span class="badge {{ $record->entry_status_badge_class }}">
                                            {{ $record->entry_status_label }}
                                        </span>
                                        @if($record->entryStatusUpdater)
                                            <small class="text-muted">
                                                @if($record->entry_status_updated_at)
                                                    {{ $record->entry_status_updated_at->format('Y-m-d H:i') }}
                                                @endif
                                                {{ $record->entryStatusUpdater->name }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('dispatch-records.show', $record->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary update-status-btn"
                                                data-record-id="{{ $record->id }}"
                                                data-current-status="{{ $record->entry_status }}">
                                            <i class="fas fa-edit"></i> 更新狀態
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- 分頁 -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>

</div>

<!-- 更新登打處理狀態 Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">
                    <i class="fas fa-edit"></i> 更新登打處理狀態
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    @csrf
                    <input type="hidden" id="recordId" name="record_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold">目前狀態</label>
                        <div>
                            <span id="currentStatusBadge" class="badge"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">選擇新狀態</label>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check p-3 border rounded">
                                <input class="form-check-input" type="radio" name="status" id="statusPending" value="pending">
                                <label class="form-check-label w-100" for="statusPending">
                                    <i class="fas fa-circle text-secondary me-2"></i>
                                    <strong>未處理</strong>
                                    <p class="mb-0 text-muted small">尚未開始處理登打工作</p>
                                </label>
                            </div>
                            <div class="form-check p-3 border rounded">
                                <input class="form-check-input" type="radio" name="status" id="statusProcessing" value="processing">
                                <label class="form-check-label w-100" for="statusProcessing">
                                    <i class="fas fa-circle text-info me-2"></i>
                                    <strong>處理中</strong>
                                    <p class="mb-0 text-muted small">正在進行登打處理</p>
                                </label>
                            </div>
                            <div class="form-check p-3 border rounded">
                                <input class="form-check-input" type="radio" name="status" id="statusCompleted" value="completed">
                                <label class="form-check-label w-100" for="statusCompleted">
                                    <i class="fas fa-circle text-success me-2"></i>
                                    <strong>處理完畢</strong>
                                    <p class="mb-0 text-muted small">已完成所有登打工作</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> 取消
                </button>
                <button type="button" class="btn btn-primary" id="confirmUpdateBtn">
                    <i class="fas fa-check"></i> 確認更新
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // 狀態標籤文字對照
    const statusLabels = {
        'pending': '未處理',
        'processing': '處理中',
        'completed': '處理完畢'
    };

    // Badge 樣式對照
    const statusBadgeClasses = {
        'pending': 'badge-secondary',
        'processing': 'badge-info',
        'completed': 'badge-success'
    };

    // 點擊「更新狀態」按鈕
    $('.update-status-btn').on('click', function() {
        const recordId = $(this).data('record-id');
        const currentStatus = $(this).data('current-status');

        // 設定 Modal 資料
        $('#recordId').val(recordId);
        $('#currentStatusBadge')
            .text(statusLabels[currentStatus])
            .removeClass()
            .addClass('badge ' + statusBadgeClasses[currentStatus]);

        // 預選目前狀態
        $('input[name="status"]').prop('checked', false);
        $('input[name="status"][value="' + currentStatus + '"]').prop('checked', true);

        // 顯示 Modal
        $('#updateStatusModal').modal('show');
    });

    // 點擊「確認更新」按鈕
    $('#confirmUpdateBtn').on('click', function() {
        const recordId = $('#recordId').val();
        const newStatus = $('input[name="status"]:checked').val();

        if (!newStatus) {
            alert('請選擇新的狀態');
            return;
        }

        // 停用按鈕防止重複點擊
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 更新中...');

        // 送出 AJAX 請求
        $.ajax({
            url: `/dispatch-records/${recordId}/entry-status`,
            method: 'PATCH',
            data: {
                status: newStatus,
                _token: $('input[name="_token"]').val()
            },
            success: function(response) {
                if (response.success) {
                    // 更新頁面上的狀態顯示
                    const statusCell = $('#status-cell-' + recordId);
                    let statusHtml = `<span class="badge ${response.data.badge_class}">${response.data.status_label}</span>`;

                    if (response.data.updated_by) {
                        statusHtml += `<br><small class="text-muted">${response.data.updated_by}`;
                        if (response.data.updated_at) {
                            statusHtml += `<br>${response.data.updated_at}`;
                        }
                        statusHtml += '</small>';
                    }

                    statusCell.html(statusHtml);

                    // 更新按鈕的 data-current-status
                    $(`.update-status-btn[data-record-id="${recordId}"]`).data('current-status', response.data.status);

                    // 關閉 Modal
                    $('#updateStatusModal').modal('hide');

                    // 顯示成功訊息
                    showAlert('success', response.message);
                } else {
                    showAlert('danger', '更新失敗，請稍後再試');
                }
            },
            error: function(xhr) {
                let errorMsg = '更新失敗，請稍後再試';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showAlert('danger', errorMsg);
            },
            complete: function() {
                // 恢復按鈕狀態
                $('#confirmUpdateBtn').prop('disabled', false).html('<i class="fas fa-check"></i> 確認更新');
            }
        });
    });

    // 顯示提示訊息
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $('.container-fluid').prepend(alertHtml);

        // 3 秒後自動關閉
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
});
</script>
@endpush
@endsection
