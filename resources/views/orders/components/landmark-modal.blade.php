<div class="modal fade" id="landmarkModal" tabindex="-1" aria-labelledby="landmarkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" id="landmarkModalHeader">
                <div class="d-flex align-items-center">
                    <i class="fas fa-map-marker-alt me-3 fs-4"></i>
                    <div>
                        <h5 class="modal-title mb-0" id="landmarkModalLabel">選擇地標</h5>
                        <small class="text-light opacity-75" id="landmarkModalSubtitle">快速填入常用地址</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body p-0">
                {{-- 搜尋區域 --}}
                <div class="landmark-search-area p-3 bg-light border-bottom">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="landmarkSearchInput" class="form-control border-start-0"
                               placeholder="搜尋地標名稱或地址...">
                        <button class="btn btn-primary" type="button" id="searchLandmarkBtn">
                            <i class="fas fa-search me-1"></i>搜尋
                        </button>
                    </div>
                </div>
                
                {{-- 分類篩選 --}}
                <div class="landmark-categories p-3 border-bottom bg-light">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm category-filter active"
                                data-category="all">全部</button>
                        <button type="button" class="btn btn-outline-danger btn-sm category-filter"
                                data-category="hospital">醫院</button>
                        <button type="button" class="btn btn-outline-warning btn-sm category-filter"
                                data-category="clinic">診所</button>
                        <button type="button" class="btn btn-outline-primary btn-sm category-filter"
                                data-category="transport">交通</button>
                        <button type="button" class="btn btn-outline-success btn-sm category-filter"
                                data-category="education">教育</button>
                        <button type="button" class="btn btn-outline-warning btn-sm category-filter"
                                data-category="government">政府</button>
                        <button type="button" class="btn btn-outline-info btn-sm category-filter"
                                data-category="commercial">商業</button>
                    </div>
                </div>
                
                {{-- 分頁標籤 --}}
                <div class="landmark-tabs">
                    <ul class="nav nav-pills nav-justified bg-light" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="search-tab" data-bs-toggle="pill"
                                    data-bs-target="#search-content" type="button" role="tab">
                                <i class="fas fa-search me-1"></i>搜尋結果
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="popular-tab" data-bs-toggle="pill"
                                    data-bs-target="#popular-content" type="button" role="tab">
                                <i class="fas fa-fire me-1"></i>熱門地標
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="recent-tab" data-bs-toggle="pill"
                                    data-bs-target="#recent-content" type="button" role="tab">
                                <i class="fas fa-history me-1"></i>最近使用
                            </button>
                        </li>
                    </ul>
                </div>
                
                {{-- 內容區域 --}}
                <div class="landmark-content" style="max-height: 400px; overflow-y: auto;">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="search-content" role="tabpanel">
                            <div id="landmarkSearchResults" class="p-3">
                                <div class="text-center py-4">
                                    <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
                                    <p class="text-muted mb-0">請輸入關鍵字搜尋地標</p>
                                </div>
                            </div>
                            {{-- 分頁控制區域 --}}
                            <div id="landmarkPagination" class="p-3 border-top bg-light" style="display: none;">
                                <nav aria-label="地標搜尋分頁">
                                    <ul class="pagination pagination-sm justify-content-center mb-0" id="landmarkPaginationList">
                                        {{-- 分頁項目將由 JavaScript 動態生成 --}}
                                    </ul>
                                </nav>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="popular-content" role="tabpanel">
                            <div id="landmarkPopularResults" class="p-3"></div>
                        </div>
                        <div class="tab-pane fade" id="recent-content" role="tabpanel">
                            <div id="landmarkRecentResults" class="p-3"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <small class="text-muted me-auto">
                    <i class="fas fa-lightbulb me-1"></i>提示：點擊地標快速填入地址
                </small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
            </div>
        </div>
    </div>
</div>