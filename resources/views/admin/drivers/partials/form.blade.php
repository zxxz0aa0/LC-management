<div class="mb-3">
    <label>姓名</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $driver->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label>手機</label>
    <input type="text" name="phone" class="form-control" value="{{ old('phone', $driver->phone ?? '') }}" required>
</div>

<div class="mb-3">
    <label>身分證</label>
    <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $driver->id_number ?? '') }}" required>
</div>

<div class="mb-3">
    <label>車隊編號</label>
    <input type="text" name="fleet_number" class="form-control" value="{{ old('fleet_number', $driver->fleet_number ?? '') }}">
</div>

<div class="mb-3">
    <label>車牌號碼</label>
    <input type="text" name="plate_number" class="form-control" value="{{ old('plate_number', $driver->plate_number ?? '') }}">
</div>

<div class="mb-3">
    <label>車色</label>
    <input type="text" name="car_color" class="form-control" value="{{ old('car_color', $driver->car_color ?? '') }}">
</div>

<div class="mb-3">
    <label>車品牌</label>
    <input type="text" name="car_brand" class="form-control" value="{{ old('car_brand', $driver->car_brand ?? '') }}">
</div>

<div class="mb-3">
    <label>車輛樣式</label>
    <input type="text" name="car_vehicle_style" class="form-control" value="{{ old('car_vehicle_style', $driver->car_vehicle_style ?? '') }}">
</div>

<div class="mb-3">
    <label>所屬公司</label>
    <input type="text" name="lc_company" class="form-control" value="{{ old('lc_company', $driver->lc_company ?? '') }}">
</div>

<div class="mb-3">
    <label>可接訂單種類（逗號分隔）</label>
    <input type="text" name="order_type" class="form-control" value="{{ old('order_type', $driver->order_type ?? '') }}">
</div>

<div class="mb-3">
    <label>服務類型（逗號分隔）</label>
    <input type="text" name="service_type" class="form-control" value="{{ old('service_type', $driver->service_type ?? '') }}">
</div>

<div class="mb-3">
    <label>狀態</label>
    <select name="status" class="form-select">
        <option value="active" {{ (old('status', $driver->status ?? '') == 'active') ? 'selected' : '' }}>在職</option>
        <option value="inactive" {{ (old('status', $driver->status ?? '') == 'inactive') ? 'selected' : '' }}>離職</option>
        <option value="blacklist" {{ (old('status', $driver->status ?? '') == 'blacklist') ? 'selected' : '' }}>黑名單</option>
    </select>
</div>
