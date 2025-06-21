@extends('layouts.app')

@section('content')
<div class="container">
    <h3>新增駕駛</h3>

    <form action="{{ route('drivers.store') }}" method="POST">
        @csrf

        @include('admin.drivers.partials.form', ['driver' => null])

        <button type="submit" class="btn btn-primary">儲存</button>
        <a href="{{ route('drivers.index') }}" class="btn btn-secondary">返回</a>
    </form>
</div>
@endsection
