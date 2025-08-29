<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', 'LC 管理系統') }} - 登入</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap + AdminLTE + FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <!-- 自訂樣式 -->
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .login-box {
            width: 400px;
            margin: 5% auto;
        }
        
        .card {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 15px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            text-align: center;
            padding: 20px;
        }
        
        .card-header h3 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-control {
            border-radius: 25px;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
        }
        
        .input-group-text {
            border-radius: 25px 0 0 25px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
        }
        
        .input-group .form-control {
            border-radius: 0 25px 25px 0;
            border-left: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #45a049 0%, #3d8b40 100%);
            transform: translateY(-2px);
        }
        
        .brand-logo {
            font-size: 3rem;
            color: white;
            margin-bottom: 10px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .text-muted a {
            color: #6c757d !important;
            text-decoration: none;
        }
        
        .text-muted a:hover {
            color: #4CAF50 !important;
        }
        
        .remember-checkbox {
            transform: scale(1.2);
        }
        
        @media (max-width: 576px) {
            .login-box {
                width: 95%;
                margin: 5% auto;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .brand-logo {
                font-size: 2rem;
            }
            
            .card-header h3 {
                font-size: 1.3rem;
            }
            
            .btn-primary {
                padding: 10px 20px;
            }
        }
        
        @media (max-width: 360px) {
            .login-box {
                width: 98%;
                margin: 2% auto;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .brand-logo {
                font-size: 1.8rem;
            }
            
            .input-group .form-control,
            .input-group-text {
                padding: 8px 15px;
            }
        }
    </style>
</head>

<body class="hold-transition">

<div class="login-box">
    <div class="text-center mb-4">
        <h2 class="text-white font-weight-bold">LC 長照管理系統</h2>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-sign-in-alt me-2"></i>系統登入</h3>
        </div>
        
        <div class="card-body">
            {{ $slot }}
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>

<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // 表單提交動畫效果
    $('form').on('submit', function() {
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.html('<i class="fas fa-spinner fa-spin me-2"></i>登入中...');
    });
    
    // 輸入框聚焦效果
    $('.form-control').on('focus', function() {
        $(this).parent().find('.input-group-text').css('border-color', '#4CAF50');
    });
    
    $('.form-control').on('blur', function() {
        $(this).parent().find('.input-group-text').css('border-color', '#e9ecef');
    });
});
</script>

</body>
</html>