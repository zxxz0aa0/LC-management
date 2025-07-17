@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 引入部分表單 --}}
    @include('orders.partials.form')
</div>
@endsection