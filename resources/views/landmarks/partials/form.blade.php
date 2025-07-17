<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>地標名稱 *</label>
            <input type="text" name="name" class="form-control" required 
                   value="{{ old('name', $landmark->name ?? '') }}" 
                   placeholder="例如：台北車站、榮總醫院">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>分類 *</label>
            <select name="category" class="form-control" required>
                <option value="">請選擇分類</option>
                @foreach(App\Models\Landmark::CATEGORIES as $key => $value)
                    <option value="{{ $key }}" 
                            {{ old('category', $landmark->category ?? '') == $key ? 'selected' : '' }}>
                        {{ $value }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>城市 *</label>
            <input type="text" name="city" class="form-control" required 
                   value="{{ old('city', $landmark->city ?? '') }}" 
                   placeholder="例如：台北市">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>區域 *</label>
            <input type="text" name="district" class="form-control" required 
                   value="{{ old('district', $landmark->district ?? '') }}" 
                   placeholder="例如：中正區">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>狀態</label>
            <div class="form-check">
                <input type="checkbox" name="is_active" class="form-check-input" 
                       {{ old('is_active', $landmark->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">啟用</label>
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    <label>地址 *</label>
    <input type="text" name="address" class="form-control" required 
           value="{{ old('address', $landmark->address ?? '') }}" 
           placeholder="例如：中山南路1-1號">
    <small class="form-text text-muted">請輸入詳細地址（不需包含城市和區域）</small>
</div>

<div class="form-group">
    <label>描述</label>
    <textarea name="description" class="form-control" rows="3" 
              placeholder="地標的詳細描述或備註">{{ old('description', $landmark->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <label>地址預覽</label>
    <div class="alert" id="address-preview">
        <span id="preview-text">請填入城市、區域和地址後查看完整地址</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cityInput = document.querySelector('input[name="city"]');
    const districtInput = document.querySelector('input[name="district"]');
    const addressInput = document.querySelector('input[name="address"]');
    const previewText = document.getElementById('preview-text');
    
    function updatePreview() {
        const city = cityInput.value.trim();
        const district = districtInput.value.trim();
        const address = addressInput.value.trim();
        
        if (city && district && address) {
            const fullAddress = city + district + address;
            previewText.textContent = `完整地址：${fullAddress}`;
            
            // 檢查是否包含"市"和"區"
            if (fullAddress.includes('市') && fullAddress.includes('區')) {
                previewText.className = 'text-success';
            } else {
                previewText.className = 'text-warning';
                previewText.textContent += ' (警告：地址應包含「市」和「區」)';
            }
        } else {
            previewText.textContent = '請填入城市、區域和地址後查看完整地址';
            previewText.className = '';
        }
    }
    
    // 監聽輸入變化
    cityInput.addEventListener('input', updatePreview);
    districtInput.addEventListener('input', updatePreview);
    addressInput.addEventListener('input', updatePreview);
    
    // 初始化預覽
    updatePreview();
});
</script>