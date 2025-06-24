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

        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>個案姓名 *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
            </div>

            <div class="form-group">
                <label>身分證字號 *</label>
                <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $customer->id_number) }}" required>
            </div>

            <div class="form-group">
                <label>生日</label>
                <input type="date" name="birthday" class="form-control" value="{{ old('birthday', $customer->birthday) }}">
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
                <label>特殊狀態</label>
                <input type="text" name="special_status" class="form-control" value="{{ old('special_status', $customer->special_status) }}">
            </div>

            <div class="form-group">
                <label>備註</label>
                <textarea name="note" class="form-control">{{ old('note', $customer->note) }}</textarea>
            </div>

            <div class="form-group">
                <label>個案來源</label>
                <select name="county_care" class="form-control">
                    <option value="">請選擇</option>
                    @foreach(['新北長照','台北長照','愛接送','新北復康'] as $option)
                        <option value="{{ $option }}" {{ old('county_care',$customer->county_care) == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>太豐或大立亨</label>
                <input type="text" name="service_company" class="form-control" value="{{ old('service_company', $customer->service_company) }}">
            </div>

            <div class="form-group">
                <label>狀態 *</label>
                <select name="status" class="form-control" required>
                    @foreach (['開案中','暫停中','已結案'] as $s)
                        <option value="{{ $s }}" {{ old('status', $customer->status) == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-success">更新資料</button>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">返回</a>
        </form>
    </div>
</div>

@endsection
