<button type="button" @click="exportReport()" class="btn btn-success"
        :disabled="exporting || !hasData">
    <i class="fas fa-file-excel mr-1"></i>
    <span x-show="!exporting">{{ $text ?? '匯出 Excel 報表' }}</span>
    <span x-show="exporting">
        <i class="fas fa-spinner fa-spin"></i> 匯出中...
    </span>
</button>
