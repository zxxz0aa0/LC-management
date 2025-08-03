{{-- 通用操作確認 Modal --}}
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">確認操作</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="actionModalBody">
                <!-- 動態內容 -->
            </div>
            <div class="modal-footer" id="actionModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="actionModalConfirm">確認</button>
            </div>
        </div>
    </div>
</div>

{{-- 指派司機 Modal --}}
<div class="modal fade" id="assignDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">指派司機</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignDriverForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="assignGroupId" name="group_id">
                    <div class="mb-3">
                        <label class="form-label">選擇司機</label>
                        <select name="driver_id" id="assignDriverSelect" class="form-select" required>
                            <option value="">請選擇司機</option>
                            {{-- 動態載入司機選項 --}}
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        指派司機後，群組內所有訂單都會分配給該司機
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none me-2"></span>
                        確認指派
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 取消群組 Modal --}}
<div class="modal fade" id="cancelGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>取消群組
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cancelGroupForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="cancelGroupId" name="group_id">
                    <div class="alert alert-warning">
                        <strong>注意：</strong>取消群組將會將所有訂單狀態設為「已取消」，此操作不可復原！
                    </div>
                    <div class="mb-3">
                        <label class="form-label">群組資訊</label>
                        <div id="cancelGroupInfo" class="form-control-plaintext">
                            <!-- 動態顯示群組資訊 -->
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">取消原因 <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="請輸入取消原因" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-warning">
                        <span class="spinner-border spinner-border-sm d-none me-2"></span>
                        確認取消群組
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 解除群組 Modal --}}
<div class="modal fade" id="dissolveGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-unlink me-2"></i>解除群組
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="dissolveGroupForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="dissolveGroupId" name="group_id">
                    <div class="alert alert-danger">
                        <strong>危險操作：</strong>解除群組將會把共乘訂單拆分為獨立訂單，此操作不可復原！
                    </div>
                    <div class="mb-3">
                        <label class="form-label">群組資訊</label>
                        <div id="dissolveGroupInfo" class="form-control-plaintext">
                            <!-- 動態顯示群組資訊 -->
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">解除原因 <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="請輸入解除原因" required></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="force" id="forceDissolve">
                        <label class="form-check-label" for="forceDissolve">
                            強制解除（即使有進行中的訂單）
                        </label>
                    </div>
                    <div id="dissolveWarning" class="alert alert-warning mt-3" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        群組中有進行中的訂單，建議先完成或取消這些訂單再解除群組。
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-danger">
                        <span class="spinner-border spinner-border-sm d-none me-2"></span>
                        確認解除群組
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 載入中 Modal --}}
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h6 id="loadingText">處理中...</h6>
                <small class="text-muted">請稍候，正在處理您的請求</small>
            </div>
        </div>
    </div>
</div>

{{-- 結果提示 Modal --}}
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resultModalTitle">操作結果</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="resultModalBody">
                <!-- 動態內容 -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">確定</button>
            </div>
        </div>
    </div>
</div>