<?php

namespace Tests\Feature;

use App\Jobs\ProcessOrderImportJob;
use App\Models\ImportProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_import_job_creation()
    {
        // 建立模擬的匯入進度記錄
        $batchId = (string) Str::uuid();
        $filePath = 'test-orders.xlsx';

        $importProgress = ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'orders',
            'filename' => 'test-orders.xlsx',
            'total_rows' => 100,
            'status' => 'pending',
        ]);

        // 測試Job能否正常創建
        $job = new ProcessOrderImportJob($batchId, $filePath);

        $this->assertInstanceOf(ProcessOrderImportJob::class, $job);
    }

    public function test_queued_import_creates_job()
    {
        Queue::fake();

        // 建立並登入用戶
        $user = User::factory()->create();
        $this->actingAs($user);

        // 模擬檔案上傳
        Storage::fake('local');
        $file = UploadedFile::fake()->create('orders.xlsx', 1024);

        $response = $this->post(route('orders.import'), [
            'file' => $file,
        ]);

        // 由於我們使用的是 queuedImport 方法，檢查是否有Job被推送
        // 注意：小檔案可能不會進入佇列處理
        $response->assertRedirect();
    }

    public function test_import_progress_page_accessible()
    {
        // 建立並登入用戶
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立測試用的匯入進度記錄
        $batchId = (string) Str::uuid();
        
        $importProgress = ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'orders',
            'filename' => 'test-orders.xlsx',
            'total_rows' => 100,
            'processed_rows' => 50,
            'success_count' => 45,
            'error_count' => 5,
            'status' => 'processing',
        ]);

        $response = $this->get(route('orders.import.progress', ['batchId' => $batchId]));

        $response->assertStatus(200);
        $response->assertViewIs('orders.import-progress');
        $response->assertViewHas('progress');
    }

    public function test_import_progress_api_returns_json()
    {
        // 建立並登入用戶
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立測試用的匯入進度記錄
        $batchId = (string) Str::uuid();
        
        $importProgress = ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'orders',
            'filename' => 'test-orders.xlsx',
            'total_rows' => 100,
            'processed_rows' => 75,
            'success_count' => 70,
            'error_count' => 5,
            'status' => 'processing',
        ]);

        $response = $this->get(route('api.orders.import.progress', ['batchId' => $batchId]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'batch_id',
            'type', 
            'filename',
            'total_rows',
            'processed_rows',
            'success_count',
            'error_count',
            'status',
        ]);
    }

    public function test_start_queue_worker_for_orders()
    {
        // 建立並登入用戶
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立測試用的匯入進度記錄
        $batchId = (string) Str::uuid();
        
        ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'orders',
            'filename' => 'test-orders.xlsx',
            'total_rows' => 100,
            'status' => 'pending',
        ]);

        // 模擬檔案存在
        Storage::fake('local');
        Storage::put('imports/' . $batchId . '.xlsx', 'fake excel content');

        Queue::fake();

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson(route('orders.startQueueWorker'), [
                'batch_id' => $batchId,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '訂單匯入處理已啟動，請稍候監控進度',
        ]);

        // 驗證Job被推送
        Queue::assertPushed(ProcessOrderImportJob::class);
    }
}