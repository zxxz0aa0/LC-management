@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0">駕駛列表</h3>
            <div class="ms-auto">
                <a href="{{ route('drivers.create') }}" class="btn btn-primary">新增駕駛</a>
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table id="drivers-table" class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>姓名</th>
                            <th>手機</th>
                            <th>身分證</th>
                            <th>狀態</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $driver)
                        <tr>
                            <td>{{ $driver->name }}</td>
                            <td>{{ $driver->phone }}</td>
                            <td>{{ $driver->id_number }}</td>
                            <td>{{ $driver->status }}</td>
                            <td>
                                <a href="{{ route('drivers.edit', $driver->id) }}" class="btn btn-sm btn-warning">編輯</a>
                                <form action="{{ route('drivers.destroy', $driver->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('確定要刪除嗎？')">
                                    @csrf
                                    @method('DELETE')
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
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#drivers-table').DataTable({
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
</script>
@endpush
