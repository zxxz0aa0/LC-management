@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">編輯客戶資料</h3>
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

        <form id="edit-customer-form" method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>個案姓名 *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
            </div>

            <div class="form-group">
                <label>身分證字號 *</label>
                <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $customer->id_number) }}" required pattern="^[A-Z][12]\d{8}$" placeholder="請輸入正確的身分證字號格式（如：A123456789）">
            </div>

            <div class="form-group">
                <label>生日</label>
                <div class="input-group">
                    <input type="text" id="birthday-input" class="form-control" placeholder="可輸入民國年：077/07/07 或西元年：1988/07/07" value="{{ old('birthday', $customer->birthday ? \Carbon\Carbon::parse($customer->birthday)->format('Y/m/d') : '') }}">
                    <input type="hidden" name="birthday" id="birthday-hidden" value="{{ old('birthday', $customer->birthday) }}">
                </div>
                <small class="form-text text-muted">支援民國年格式（如：077/07/07）或西元年格式（如：1988/07/07）</small>
            </div>

            <div class="form-group">
                <label>性別</label>
                <select name="gender" class="form-control">
                    <option value="">請選擇</option>
                    <option value="男" {{ old('gender', $customer->gender) == '男' ? 'selected' : '' }}>男</option>
                    <option value="女" {{ old('gender', $customer->gender) == '女' ? 'selected' : '' }}>女</option>
                </select>
            </div>

            <div class="form-group">
                <label>聯絡電話（逗號分隔） *</label>
                <input type="text" name="phone_number" class="form-control"
                    value="{{ old('phone_number', is_array($customer->phone_number) ? implode(',', $customer->phone_number) : '') }}"
                    required>
            </div>

            <div class="form-group">
                <label>地址（逗號分隔） *</label>
                <textarea name="addresses" class="form-control" required rows="2">{{ old('addresses', is_array($customer->addresses) ? implode(',', $customer->addresses) : '') }}</textarea>
            </div>

            <div class="form-group">
                <label>聯絡人</label>
                <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person) }}">
            </div>

            <div class="form-group">
                <label>聯絡電話</label>
                <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $customer->contact_phone) }}">
            </div>

            <div class="form-group">
                <label>關係</label>
                <input type="text" name="contact_relationship" class="form-control" value="{{ old('contact_relationship', $customer->contact_relationship) }}">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
            </div>

            <div class="form-group">
                <label>輪椅</label>
                <select name="wheelchair" class="form-control">
                    <option value="">請選擇</option>
                    <option value="是" {{ old('wheelchair', $customer->wheelchair) == '是' ? 'selected' : '' }}>是</option>
                    <option value="否" {{ old('wheelchair', $customer->wheelchair) == '否' ? 'selected' : '' }}>否</option>
                </select>
            </div>

            <div class="form-group">
                <label>爬梯機</label>
                <select name="stair_climbing_machine" class="form-control">
                    <option value="">請選擇</option>
                    <option value="是" {{ old('stair_climbing_machine', $customer->stair_climbing_machine) == '是' ? 'selected' : '' }}>是</option>
                    <option value="否" {{ old('stair_climbing_machine', $customer->stair_climbing_machine) == '否' ? 'selected' : '' }}>否</option>
                </select>
            </div>

            <div class="form-group">
                <label>共乘</label>
                <select name="ride_sharing" class="form-control">
                    <option value="">請選擇</option>
                    <option value="是" {{ old('ride_sharing', $customer->ride_sharing) == '是' ? 'selected' : '' }}>是</option>
                    <option value="否" {{ old('ride_sharing', $customer->ride_sharing) == '否' ? 'selected' : '' }}>否</option>
                </select>
            </div>

            <div class="form-group">
                <label>身份別</label>
                <select name="identity" class="form-control">
                    <option value="">請選擇</option>
                    @foreach(['市區-一般','市區-中低收','市區-低收','偏區-一般','偏區-中低收','偏區-低收'] as $option)
                        <option value="{{ $option }}" {{ old('identity', $customer->identity) == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>A單位</label>
                <input type="text" name="a_mechanism" class="form-control" value="{{ old('a_mechanism', $customer->a_mechanism) }}">
            </div>

            <div class="form-group">
                <label>個管師</label>
                <input type="text" name="a_manager" class="form-control" value="{{ old('a_manager', $customer->a_manager) }}">
            </div>

           <div class="form-group">
                <label>特殊狀態（如黑名單）</label>
                <select name="special_status" class="form-control" required>
                    <option value="一般" {{ old('special_status') == '一般' ? 'selected' : '' }}>一般</option>
                    <option value="黑名單" {{ old('special_status') == '黑名單' ? 'selected' : '' }}>黑名單</option>
                    <option value="個管單" {{ old('special_status') == '個管單' ? 'selected' : '' }}>個管單</option>
                    <option value="VIP" {{ old('special_status') == 'VIP' ? 'selected' : '' }}>VIP</option>
                </select>
            </div>

            <div class="form-group">
                <label>備註</label>
                <textarea name="note" class="form-control">{{ old('note', $customer->note) }}</textarea>
            </div>

            <div class="form-group">
                <label>個案來源</label>
                <select name="county_care" class="form-control">
                    <option value="">請選擇</option>
                    @foreach(['新北長照','台北長照','愛接送','新北復康','一般乘客'] as $option)
                        <option value="{{ $option }}" {{ old('county_care',$customer->county_care) == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>可服務交通公司</label>
                <select name="service_company" class="form-control">
                    <option value="" disabled selected>請選擇</option>
                    <option value="太豐" {{ old('service_company', $customer->service_company) == '太豐' ? 'selected' : '' }}>太豐</option>
                    <option value="大立亨" {{ old('service_company', $customer->service_company) == '大立亨' ? 'selected' : '' }}>大立亨</option>
                    <option value="太豐與大立亨" {{ old('service_company', $customer->service_company) == '太豐與大立亨' ? 'selected' : '' }}>太豐與大立亨</option>
                </select>
            </div>


            <div class="form-group">
                <label>狀態 *</label>
                <select name="status" class="form-control" required>
                    @foreach (['開案中','暫停中','已結案'] as $s)
                        <option value="{{ $s }}" {{ old('status', $customer->status) == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-success" form="edit-customer-form">更新資料</button>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">返回</a>
        </form>
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
    
    // 表單提交前確保日期格式正確
    document.getElementById('edit-customer-form').addEventListener('submit', function(e) {
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
