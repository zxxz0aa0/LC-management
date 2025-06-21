<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>LC 管理系統</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- AdminLTE + Bootstrap + DataTables 的 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>

<body class="hold-transition  sidebar-collapse">

<div class="wrapper">

    <!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- 左上角漢堡按鈕 -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
                <span class="navbar-brand">LC 管理系統</span>
            </a>
        </li>
    </ul>

    <!-- 右邊其他功能 -->
</nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="" class="brand-link text-center">
            <span class="brand-text font-weight-light">長照管理</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                    <li class="nav-item">
                        <a href="{{ route('customers.index') }}" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>客戶管理</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('drivers.index') }}" class="nav-link {{ request()->is('admin/drivers*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-car"></i>
                            <p>駕駛管理</p>
                        </a>
                    </li>
                    {{-- 未來可加入：訂單管理、駕駛管理 --}}
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper p-3">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="main-footer text-center">
        <strong>&copy; {{ date('Y') }} LC 管理系統</strong>
    </footer>
</div>

</body>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>

<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- 你的自訂 jQuery 行為（例如更新事件） -->
<script>
$(document).ready(function () {
    $('.update-event-btn').on('click', function () {
        const id = $(this).data('id');
        const input = $(`input[data-id='${id}']`);
        const newValue = input.val();

        $.ajax({
            url: '/customer-events/' + id,
            method: 'PUT',
            data: {
                event: newValue,
                _token: '{{ csrf_token() }}',
            },
            success: function (response) {
                if (response.success) {
                    alert('已更新成功');
                }
            },
            error: function () {
                alert('更新失敗，請檢查輸入或稍後再試');
            }
        });
    });
});
</script>

<!-- 留給每頁個別 @push('scripts') 使用 -->
@stack('scripts')


</html>



