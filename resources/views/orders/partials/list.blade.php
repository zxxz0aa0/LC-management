{{-- resources/views/orders/partials/list.blade.php --}}
<table id="order-table" class="table table-bordered table-hover align-middle">
    <thead>
        <tr>
            <th>編號</th>
            <th>客戶姓名</th>
            <th>用車日期</th>
            <th>訂單狀態</th>
            <th>建單人員</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orders as $order)
        <tr>
            <td>{{ $order->order_number }}</td>
            <td>{{ $order->customer_name }}</td>
            <td>{{ $order->ride_date }}</td>
            <td>{{ $order->status }}</td>
            <td>{{ $order->created_by }}</td>
            <td>
                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-info">檢視</a>
                <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-warning">編輯</a>
            </td>
        </tr>
        @empty

        @endforelse
    </tbody>
</table>
