<x-auth-layout>
    <!-- Session Status -->
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Error Messages -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" id="loginForm">
        @csrf

        <!-- 使用者名稱 -->
        <div class="mb-3">
            <label for="name" class="form-label">
                <i class="me-2"></i>使用者名稱
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-user"></i>
                </span>
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                       name="name" value="{{ old('name') }}" required autofocus autocomplete="username"
                       placeholder="請輸入使用者名稱">
            </div>
            @error('name')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <!-- 密碼 -->
        <div class="mb-3">
            <label for="password" class="form-label">
                <i class="me-2"></i>使用者密碼
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                </span>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" 
                       name="password" required autocomplete="current-password"
                       placeholder="請輸入密碼">
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">
                    <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                </div>
            @enderror
        </div>

        <!-- 記住我 -->
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input remember-checkbox" type="checkbox" name="remember" 
                       id="remember_me" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember_me">
                    <i class="fas fa-heart me-1"></i>記住我
                </label>
            </div>
        </div>

        <!-- 登入按鈕和忘記密碼 -->
        <div class="d-grid gap-2 mb-3">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-sign-in-alt me-2"></i>登入系統
            </button>
        </div>

       <!-- @if (Route::has('password.request'))
            <div class="text-center">
                <small class="text-muted">
                    <a href="{{ route('password.request') }}">
                        <i class="fas fa-question-circle me-1"></i>忘記密碼？
                    </a>
                </small>
            </div>
        @endif-->
    </form>

    <!-- 版權資訊 -->
    <div class="text-center mt-4">
        <small class="text-muted">
            <i class="fas fa-copyright me-1"></i>{{ date('Y') }} 太豐長照訂單管理系統
        </small>
    </div>

    <script>
        // 密碼顯示/隐藏功能
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });

        // 表單驗證
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const password = document.getElementById('password').value;
            
            if (!name) {
                e.preventDefault();
                alert('請輸入使用者名稱');
                document.getElementById('name').focus();
                return false;
            }
            
            if (!password) {
                e.preventDefault();
                alert('請輸入密碼');
                document.getElementById('password').focus();
                return false;
            }
        });
    </script>
</x-auth-layout>
