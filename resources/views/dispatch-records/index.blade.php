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
                                <th class="text-center" style="width:5%">#</th>
                                <th style="width:25%">排趟名稱</th>
                                <th style="width:12%">司機</th>
                                <th class="text-center" style="width:8%">訂單數</th>
                                <th style="width:12%">排趟日期</th>
                                <th style="width:15%">執行時間</th>
                                <th style="width:10%">執行人</th>
                                <th class="text-center" style="width:13%">操作</th>
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
                                            <span class="badge bg-info">{{ $record->driver_fleet_number }}</span>
                                            {{ $record->driver_name }}
                                        @else
                                            <span class="text-muted">{{ $record->driver_name }} (已刪除)</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $record->order_count }} 筆</span>
                                    </td>
                                    <td>
                                        <i class="fas fa-calendar"></i>
                                        {{ $record->dispatch_date ? $record->dispatch_date->format('Y-m-d') : 'N/A' }}
                                    </td>
                                    <td>
                                        <small>{{ $record->performed_at->format('Y-m-d H:i') }}</small>
                                    </td>
                                    <td>
                                        {{ $record->performer->name ?? 'N/A' }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('dispatch-records.show', $record->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> 查看
                                        </a>
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
@endsection
