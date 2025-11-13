@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">新增客戶資料</h3>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>請修正以下錯誤：</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="create-customer-form" method="POST" action="{{ route('customers.store') }}">
            @csrf
            
            {{-- 隱藏欄位保存返回參數 --}}
            @if(isset($return_to))
                <input type="hidden" name="return_to" value="{{ $return_to }}">
            @endif
            @if(isset($search_params))
                @foreach($search_params as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
            @endif

            <div class="form-group">
                <label>照會日期</label>
                <input type="date" name="referral_date" id="referral_date" class="form-control" readonly value="{{ old('referral_date') }}">
            </div>

            <div class="row">
                <div class="col-md-2 form-group">
                    <label>個案姓名 *</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                </div>

                <div class="col-md-2 form-group">
                    <label>身分證字號 *</label>
                    <input type="text" name="id_number" class="form-control" required value="{{ old('id_number') }}" pattern="^[A-Z][1289]\d{8}$" maxlength="10" placeholder="身分證字號格式（例如：A123456789）">
                </div>

                <div class="col-md-2 form-group">
                    <label>生日</label>
                    <div class="input-group">
                        <input type="text" id="birthday-input" class="form-control" placeholder="可輸入民國年：077/07/07 或西元年：1988/07/07" value="{{ old('birthday') }}">
                        <input type="hidden" name="birthday" id="birthday-hidden">
                    </div>
                </div>

                <div class="col-md-2 form-group">
                    <label>性別</label>
                    <select name="gender" class="form-control">
                        <option value="">請選擇</option>
                        <option value="男" {{ old('gender') == '男' ? 'selected' : '' }}>男</option>
                        <option value="女" {{ old('gender') == '女' ? 'selected' : '' }}>女</option>
                    </select>
                </div>

                <div class="col-md-2 form-group">
                    <label>聯絡電話（多筆用逗號分隔） *</label>
                    <input type="text" name="phone_number" class="form-control" required placeholder="例如：0912xxxxxx, 02-xxxxxxx" value="{{ old('phone_number') }}">
                </div>

                <div class="col-md-2 form-group">
                    <label>身份別</label>
                    <select name="identity" class="form-control">
                        <option value="">請選擇</option>
                        <option value="市區-一般" {{ old('identity') == '市區-一般' ? 'selected' : '' }}>市區-一般</option>
                        <option value="市區-中低收" {{ old('identity') == '市區-中低收' ? 'selected' : '' }}>市區-中低收</option>
                        <option value="市區-低收" {{ old('identity') == '市區-低收' ? 'selected' : '' }}>市區-低收</option>
                        <option value="偏區-一般" {{ old('identity') == '偏區-一般' ? 'selected' : '' }}>偏區-一般</option>
                        <option value="偏區-中低收" {{ old('identity') == '偏區-中低收' ? 'selected' : '' }}>偏區-中低收</option>
                        <option value="偏區-低收" {{ old('identity') == '偏區-低收' ? 'selected' : '' }}>偏區-低收</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>地址（需包含「市」與「區」，多筆用逗號分隔） *</label>
                <textarea name="addresses" class="form-control" required rows="2" placeholder="例如：台北市信義區市府路45號, 新北市板橋區文化路">{{ old('addresses') }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-2 form-group">
                    <label>聯絡人</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                </div>

                <div class="col-md-2 form-group">
                    <label>聯絡電話</label>
                    <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone') }}">
                </div>

                <div class="col-md-2 form-group">
                    <label>關係</label>
                    <input type="text" name="contact_relationship" class="form-control" value="{{ old('contact_relationship') }}">
                </div>

                <div class="col-md-2 form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>

                <div class="col-md-2 form-group">
                    <label>輪椅</label>
                    <select name="wheelchair" class="form-control">
                        <option value="未知" {{ old('wheelchair') == '未知' ? 'selected' : '' }}>未知</option>
                        <option value="否" {{ old('wheelchair') == '否' ? 'selected' : '' }}>否</option>
                        <option value="是" {{ old('wheelchair') == '是' ? 'selected' : '' }}>是</option>
                    </select>
                </div>

                <div class="col-md-2 form-group">
                    <label>爬梯機</label>
                    <select name="stair_climbing_machine" class="form-control">
                        <option value="否" {{ old('stair_climbing_machine') == '否' ? 'selected' : '' }}>否</option>
                        <option value="是" {{ old('stair_climbing_machine') == '是' ? 'selected' : '' }}>是</option>
                    </select>
                </div>
            </div>

                <div class="form-group">
                    <label>備註</label>
                    <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                </div>

            <div class="row">
                <div class="col-md-2 form-group">
                    <label>可否共乘</label>
                    <select name="ride_sharing" class="form-control">
                        <option value="">請選擇</option>
                        <option value="是" {{ old('ride_sharing') == '是' ? 'selected' : '' }}>是</option>
                        <option value="否" {{ old('ride_sharing') == '否' ? 'selected' : '' }}>否</option>
                    </select>
                </div>


                <div class="col-md-2 form-group">
                    <label>A單位</label>
                    <input type="text" name="a_mechanism" class="form-control" value="{{ old('a_mechanism') }}">
                </div>

                <div class="col-md-2 form-group">
                    <label>個管師</label>
                    <input type="text" name="a_manager" class="form-control" value="{{ old('a_manager') }}">
                </div>

                <div class="col-md-2 form-group">
                    <label>特殊狀態（如黑名單）</label>
                    <select name="special_status" class="form-control" required>
                        <option value="一般" {{ old('special_status') == '一般' ? 'selected' : '' }}>一般</option>
                        <option value="個管單" {{ old('special_status') == '個管單' ? 'selected' : '' }}>個管單</option>
                        <option value="網頁" {{ old('special_status') == '網頁' ? 'selected' : '' }}>網頁</option>
                        <option value="黑名單" {{ old('special_status') == '黑名單' ? 'selected' : '' }}>黑名單</option>
                        <option value="已往生" {{ old('special_status') == '已往生' ? 'selected' : '' }}>已往生</option>
                    </select>
                </div>

                <div class="col-md-2 form-group">
                    <label>個案來源</label>
                    <select name="county_care" class="form-control" required>
                        <option value="" disabled {{ old('county_care') ? '' : 'selected' }}>請選擇</option>
                        @foreach(['新北長照','台北長照','愛接送','新北復康','一般乘客'] as $option)
                            <option value="{{ $option }}" {{ old('county_care') == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 form-group">
                    <label>可服務交通公司</label>
                    <select name="service_company" class="form-control">
                        <option value="太豐" {{ old('service_company') == '太豐' ? 'selected' : '' }}>太豐</option>
                        <option value="大立亨" {{ old('service_company') == '大立亨' ? 'selected' : '' }}>大立亨</option>
                        <option value="太豐與大立亨" {{ old('service_company') == '太豐與大立亨' ? 'selected' : '' }}>太豐與大立亨</option>
                    </select>

                </div>
            </div>

            <div class="form-group">
                <label>狀態 *</label>
                <select name="status" class="form-control" required>
                    <option value="開案中" {{ old('status') == '開案中' ? 'selected' : '' }}>開案中</option>
                    <option value="暫停中" {{ old('status') == '暫停中' ? 'selected' : '' }}>暫停中</option>
                    <option value="已結案" {{ old('status') == '已結案' ? 'selected' : '' }}>已結案</option>
                </select>
            </div>
        </form>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary" form="create-customer-form">儲存客戶</button>
        @if(isset($return_to) && $return_to === 'orders')
            <a href="{{ route('orders.index', $search_params ?? []) }}" class="btn btn-secondary">返回訂單管理</a>
        @else
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">返回</a>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const birthdayInput = document.getElementById('birthday-input');
    const birthdayHidden = document.getElementById('birthday-hidden');

    // 民國年轉西元年的函數
    function convertToWesternDate(inputValue) {
        // 移除所有空格
        const cleanValue = inputValue.replace(/\s+/g, '');

        // 支援的格式：077/01/06, 077-01-06, 0770106
        const rocPatterns = [
            /^(\d{3})\/(\d{2})\/(\d{2})$/,  // 077/01/06
            /^(\d{3})-(\d{2})-(\d{2})$/,   // 077-01-06
            /^(\d{7})$/                     // 0770106
        ];

        // 西元年格式：1988/01/06, 1988-01-06, 19880106
        const westernPatterns = [
            /^(\d{4})\/(\d{2})\/(\d{2})$/,  // 1988/01/06
            /^(\d{4})-(\d{2})-(\d{2})$/,   // 1988-01-06
            /^(\d{8})$/                     // 19880106
        ];

        // 檢查是否為民國年格式
        for (let pattern of rocPatterns) {
            const match = cleanValue.match(pattern);
            if (match) {
                let year, month, day;

                if (pattern.source.includes('(\\d{7})')) {
                    // 格式：0770106
                    year = parseInt(match[1].substring(0, 3));
                    month = match[1].substring(3, 5);
                    day = match[1].substring(5, 7);
                } else {
                    // 格式：077/01/06 或 077-01-06
                    year = parseInt(match[1]);
                    month = match[2];
                    day = match[3];
                }

                // 民國年轉西元年 (民國年 + 1911)
                const westernYear = year + 1911;
                return `${westernYear}-${month}-${day}`;
            }
        }

        // 檢查是否為西元年格式
        for (let pattern of westernPatterns) {
            const match = cleanValue.match(pattern);
            if (match) {
                let year, month, day;

                if (pattern.source.includes('(\\d{8})')) {
                    // 格式：19880106
                    year = match[1].substring(0, 4);
                    month = match[1].substring(4, 6);
                    day = match[1].substring(6, 8);
                } else {
                    // 格式：1988/01/06 或 1988-01-06
                    year = match[1];
                    month = match[2];
                    day = match[3];
                }

                return `${year}-${month}-${day}`;
            }
        }

        return null;
    }

    // 輸入時即時轉換
    birthdayInput.addEventListener('input', function() {
        const inputValue = this.value;
        const convertedDate = convertToWesternDate(inputValue);

        if (convertedDate) {
            birthdayHidden.value = convertedDate;
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else if (inputValue.trim() === '') {
            birthdayHidden.value = '';
            this.classList.remove('is-invalid', 'is-valid');
        } else {
            birthdayHidden.value = '';
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    });

    // 失去焦點時格式化顯示
    birthdayInput.addEventListener('blur', function() {
        const convertedDate = convertToWesternDate(this.value);
        if (convertedDate) {
            // 將西元年格式轉換為顯示格式
            const date = new Date(convertedDate);
            if (!isNaN(date.getTime())) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                this.value = `${year}/${month}/${day}`;
            }
        }
    });

    // 設定照會日期為當天日期
    document.getElementById('referral_date').value = new Date().toISOString().split('T')[0];

    // 表單提交前確保日期格式正確
    document.getElementById('create-customer-form').addEventListener('submit', function(e) {
        const birthdayValue = birthdayInput.value;
        if (birthdayValue && !birthdayHidden.value) {
            e.preventDefault();
            alert('請輸入正確的日期格式');
            birthdayInput.focus();
        }
    });
});
</script>
@endpush
