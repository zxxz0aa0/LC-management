@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">編輯地標</h3>
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

            <form method="POST" action="{{ route('landmarks.update', $landmark) }}">
                @csrf
                @method('PUT')
                @include('landmarks.partials.form')

                <div class="form-group">
                    <button type="submit" class="btn btn-success">更新地標</button>
                    <a href="{{ route('landmarks.index') }}" class="btn btn-secondary">返回列表</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection