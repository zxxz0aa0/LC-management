<div class="row">
    <div class="col-md-6">
        <!--客戶資料-->
        <h5 class="mb-3">訂單編號：{{ $order->order_number }}</h5>
        <p><strong>是否輪椅：</strong>{{ $order->wheelchair == 1 ? '是' : '否' }}</p>
        <p><strong>陪同人數：</strong>{{ $order->companions }}</p>
        <p><strong>共乘對象：</strong>{{ $order->carpool_name }}</p>
        <p><strong>備註：</strong>{{ $order->remark }}</p>
        <p><strong>建單人：</strong>{{ $order->created_by }}</p>
        <p><strong>建立時間：</strong>{{ $order->created_at }}</p>
    </div>
    <div class="col-md-6">
        <!--駕駛資料-->
        @if($order->driver)
            <p><strong>隊員編號：</strong>{{ $order->driver->fleet_number }}</p>
            <p><strong>駕駛姓名：</strong>{{ $order->driver->name }}</p>
            <p><strong>車牌號碼：</strong>{{ $order->driver->plate_number }}</p>
            <p><strong>車輛顏色：</strong>{{ $order->driver->car_color }}</p>
            <p><strong>聯絡電話：</strong>{{ $order->driver->phone }}</p>
        @else
            <p><strong>駕駛：</strong>尚未指派</p>
        @endif
    </div>


</div>
