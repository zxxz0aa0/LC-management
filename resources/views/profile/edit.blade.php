@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- 頁面標題 -->
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-0">個人設定</h1>
        </div>
    </div>

    <!-- 個人資料設定 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-edit me-2"></i>個人資料
                    </h3>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <div class="mb-3">
                            <label for="name" class="form-label">姓名</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">電子郵件</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="{{ old('email', $user->email) }}">
                            @error('email')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>儲存
                        </button>

                        @if (session('status') === 'profile-updated')
                            <div class="alert alert-success mt-2" style="display: none;" id="profile-success">
                                個人資料已更新成功！
                            </div>
                            <script>
                                $(document).ready(function() {
                                    $('#profile-success').show().delay(3000).fadeOut();
                                });
                            </script>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 密碼變更 -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lock me-2"></i>變更密碼
                    </h3>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="mb-3">
                            <label for="current_password" class="form-label">目前密碼</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                    <i class="far fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                            @error('current_password', 'updatePassword')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">新密碼</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" 
                                       name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="far fa-eye" id="password_icon"></i>
                                </button>
                            </div>
                            @error('password', 'updatePassword')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">確認新密碼</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmation" 
                                       name="password_confirmation" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation')">
                                    <i class="far fa-eye" id="password_confirmation_icon"></i>
                                </button>
                            </div>
                            @error('password_confirmation', 'updatePassword')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i>更新密碼
                        </button>

                        @if (session('status') === 'password-updated')
                            <div class="alert alert-success mt-2" style="display: none;" id="password-success">
                                密碼已更新成功！
                            </div>
                            <script>
                                $(document).ready(function() {
                                    $('#password-success').show().delay(3000).fadeOut();
                                });
                            </script>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 刪除帳戶 -->
    <div class="row">
        <div class="col-md-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>危險區域
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        刪除帳戶後，所有相關資料將被永久移除，此操作無法復原。
                    </p>
                    
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                        <i class="fas fa-trash me-1"></i>刪除帳戶
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 刪除帳戶確認 Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">確認刪除帳戶</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                <div class="modal-body">
                    <p class="mb-3">請輸入您的密碼以確認刪除帳戶：</p>
                    <div class="mb-3">
                        <div class="input-group">
                            <input type="password" class="form-control" id="delete_password" name="password" 
                                   placeholder="請輸入密碼確認" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('delete_password')">
                                <i class="far fa-eye" id="delete_password_icon"></i>
                            </button>
                        </div>
                        @error('password', 'userDeletion')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-danger">確認刪除</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '_icon');
    
    if (passwordInput.type === 'password') {
        // 顯示密碼
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        // 隱藏密碼
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
@endsection
