{{-- 通用編輯欄位 Modal --}}
<div class="modal fade" id="editFieldModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editFieldTitle">編輯欄位</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editFieldForm">
                    @csrf
                    <input type="hidden" id="editFieldName" name="field_name">

                    {{-- 文字欄位（備註、個管師、服務單位）--}}
                    <div class="mb-3" id="textFieldContainer" style="display: none;">
                        <label for="editFieldValue" class="form-label" id="editFieldLabel">欄位值</label>
                        <textarea class="form-control"
                                  id="editFieldValue"
                                  name="value"
                                  rows="4"
                                  placeholder="請輸入內容..."
                                  maxlength="1000"></textarea>
                        <div class="form-text">最多1000字</div>
                    </div>

                    {{-- 選擇欄位（輪椅、爬梯機）--}}
                    <div class="mb-3" id="selectFieldContainer" style="display: none;">
                        <label for="editFieldSelect" class="form-label" id="editSelectLabel">請選擇</label>
                        <select class="form-select" id="editFieldSelect" name="value">
                            <option value="">未知</option>
                            <option value="是">是</option>
                            <option value="否">否</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="editFieldSaveBtn" onclick="saveEditField()">
                    <span class="spinner-border spinner-border-sm d-none me-2"></span>
                    儲存
                </button>
            </div>
        </div>
    </div>
</div>
