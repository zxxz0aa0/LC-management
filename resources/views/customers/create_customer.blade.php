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

        <form method="POST" action="{{ route('customers.store') }}">
            @csrf

            <div class="form-group">
                <label>個案姓名 *</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
            </div>

            <div class="form-group">
                <label>身分證字號 *</label>
                <input type="text" name="id_number" class="form-control" required value="{{ old('id_number') }}">
            </div>

            <div class="form-group">
                <label>生日</label>
                <input type="date" name="birthday" class="form-control" value="{{ old('birthday') }}">
            </div>

            <div class="form-group">
                <label>性別</label>
                <select name="gender" class="form-control">
                    <option value="">請選擇</option>
                    <option value="男" {{ old('gender') == '男' ? 'selected' : '' }}>男</option>
                    <option value="女" {{ old('gender') == '女' ? 'selected' : '' }}>女</option>
                </select>
            </div>

            <div class="form-group">
                <label>聯絡電話（多筆用逗號分隔） *</label>
                <input type="text" name="phone_number" class="form-control" required placeholder="例如：0912xxxxxx, 02-xxxxxxx" value="{{ old('phone_number') }}">
            </div>

            <div class="form-group">
                <label>地址（需包含「市」與「區」，多筆用逗號分隔） *</label>
                <textarea name="addresses" class="form-control" required rows="2" placeholder="例如：台北市信義區市府路45號, 新北市板橋區文化路">{{ old('addresses') }}</textarea>
            </div>

            <div class="form-group">
                <label>聯絡人</label>
                <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
            </div>

            <div class="form-group">
                <label>聯絡電話</label>
                <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone') }}">
            </div>

            <div class="form-group">
                <label>關係</label>
                <input type="text" name="contact_relationship" class="form-control" value="{{ old('contact_relationship') }}">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
            </div>

            <div class="form-group">
                <label>輪椅</label>
                <select name="wheelchair" class="form-control">
                    <option value="">請選擇</option>
                    <option value="是" {{ old('wheelchair') == '是' ? 'selected' : '' }}>是</option>
                    <option value="否" {{ old('wheelchair') == '否' ? 'selected' : '' }}>否</option>
                </select>
            </div>

            <div class="form-group">
                <label>爬梯機</label>
                <select name="stair_climbing_machine" class="form-control">
                    <option value="">請選擇</option>
                    <option value="是" {{ old('stair_climbing_machine') == '是' ? 'selected' : '' }}>是</option>
                    <option value="否" {{ old('stair_climbing_machine') == '否' ? 'selected' : '' }}>否</option>
                </select>
            </div>

            <div class="form-group">
                <label>共乘</label>
                <select name="ride_sharing" class="form-control">
                    <option value="">請選擇</option>
                    <option value="是" {{ old('ride_sharing') == '是' ? 'selected' : '' }}>是</option>
                    <option value="否" {{ old('ride_sharing') == '否' ? 'selected' : '' }}>否</option>
                </select>
            </div>

            <div class="form-group">
                <label>身份別</label>
                <select name="identity" class="form-control">
                    <option value="">請選擇</option>
                    @foreach(['市區-一般','市區-中低收','市區-低收','偏區-一般','偏區-中低收','偏區-低收'] as $option)
                        <option value="{{ $option }}" {{ old('identity') == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>A單位</label>
                <input type="text" name="a_mechanism" class="form-control" value="{{ old('a_mechanism') }}">
            </div>

            <div class="form-group">
                <label>個管師</label>
                <input type="text" name="a_manager" class="form-control" value="{{ old('a_manager') }}">
            </div>

            <div class="form-group">
                <label>特殊狀態（如黑名單）</label>
                <input type="text" name="special_status" class="form-control" value="{{ old('special_status') }}">
            </div>

            <div class="form-group">
                <label>備註</label>
                <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
            </div>

            <div class="form-group">
                <label>個案來源</label>
                <select name="county_care" class="form-control">
                    <option value="">請選擇</option>
                    @foreach(['新北長照','台北長照','愛接送','新北復康'] as $option)
                        <option value="{{ $option }}" {{ old('county_care') == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>大豐或大立亨</label>
                <input type="text" name="service_company" class="form-control" value="{{ old('service_company') }}">
            </div>

            <div class="form-group">
                <label>狀態 *</label>
                <select name="status" class="form-control" required>
                    <option value="開案中" {{ old('status') == '開案中' ? 'selected' : '' }}>開案中</option>
                    <option value="暫停中" {{ old('status') == '暫停中' ? 'selected' : '' }}>暫停中</option>
                    <option value="已結案" {{ old('status') == '已結案' ? 'selected' : '' }}>已結案</option>
                </select>
            </div>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary">儲存客戶</button>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>

@endsection
