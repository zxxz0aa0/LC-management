@extends('layouts.app')

@section('content')
<div class="container">
    <h3>編輯駕駛</h3>

    <form action="{{ route('drivers.update', $driver->id) }}" method="POST">
        @csrf
        @method('PUT')

        @include('admin.drivers.partials.form', ['driver' => $driver])

        <button type="submit" class="btn btn-primary">更新</button>
        <a href="{{ route('drivers.index') }}" class="btn btn-secondary">返回</a>
    </form>
</div>
@endsection
