<?php

namespace Tests\Feature;

use App\Jobs\ProcessCustomerImportJob;
use App\Models\Customer;
use App\Models\ImportProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試用戶並登入
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_customer_import_job_creation()
    {
        // 建立模擬的匯入進度記錄
        $batchId = (string) Str::uuid();
        $filePath = 'test-customers.xlsx';

        $importProgress = ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'customers',
            'filename' => 'test-customers.xlsx',
            'total_rows' => 50,
            'status' => 'pending',
        ]);

        // 測試Job能否正常創建
        $job = new ProcessCustomerImportJob($batchId, $filePath);

        $this->assertInstanceOf(ProcessCustomerImportJob::class, $job);
    }

    public function test_queued_import_creates_job()
    {
        Queue::fake();
        Storage::fake('local');

        // 建立測試Excel檔案
        $file = UploadedFile::fake()->create('customers.xlsx', 2048); // 2MB檔案

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('customers.queuedImport'), [
                'file' => $file,
            ]);

        // 檢查是否創建進度記錄和調度Job
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 驗證Job被推送
        Queue::assertPushed(ProcessCustomerImportJob::class);

        // 驗證進度記錄被創建
        $this->assertDatabaseHas('import_progress', [
            'type' => 'customers',
            'filename' => 'customers.xlsx',
            'status' => 'pending',
        ]);
    }

    public function test_immediate_import_with_valid_data()
    {
        Storage::fake('local');

        // 創建有效的測試客戶資料
        $csvContent = "id_number,name,phone_number,addresses\n";
        $csvContent .= "A123456789,測試客戶1,02-12345678,台北市中正區test路1號\n";
        $csvContent .= "B987654321,測試客戶2,0912345678,台北市大安區test路2號\n";

        // 暫時創建CSV檔案（實際應該是Excel）
        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            $csvContent
        );

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('customers.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');
    }

    public function test_import_with_invalid_data()
    {
        Storage::fake('local');

        // 創建包含無效資料的測試檔案
        $csvContent = "id_number,name,phone_number,addresses\n";
        $csvContent .= ",測試客戶1,02-12345678,台北市中正區test路1號\n"; // 缺少身分證號
        $csvContent .= "INVALID_ID,測試客戶2,0912345678,台北市大安區test路2號\n"; // 無效身分證號

        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            $csvContent
        );

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('customers.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('import_errors');
    }

    public function test_import_progress_page_accessible()
    {
        // 建立測試用的匯入進度記錄
        $batchId = (string) Str::uuid();

        $importProgress = ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'customers',
            'filename' => 'test-customers.xlsx',
            'total_rows' => 100,
            'processed_rows' => 50,
            'success_count' => 45,
            'error_count' => 5,
            'status' => 'processing',
        ]);

        $response = $this->get(route('customers.import.progress', ['batchId' => $batchId]));

        $response->assertStatus(200);
        $response->assertViewIs('customers.import-progress');
        $response->assertViewHas('progress');
    }

    public function test_import_progress_api_returns_json()
    {
        // 建立測試用的匯入進度記錄
        $batchId = (string) Str::uuid();

        $importProgress = ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'customers',
            'filename' => 'test-customers.xlsx',
            'total_rows' => 100,
            'processed_rows' => 75,
            'success_count' => 70,
            'error_count' => 5,
            'status' => 'processing',
        ]);

        $response = $this->get(route('api.customers.import.progress', ['batchId' => $batchId]));

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

    public function test_start_queue_worker_for_customers()
    {
        // 建立測試用的匯入進度記錄
        $batchId = (string) Str::uuid();

        ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'customers',
            'filename' => 'test-customers.xlsx',
            'total_rows' => 100,
            'status' => 'pending',
        ]);

        // 模擬檔案存在
        Storage::fake('local');
        Storage::put('imports/'.$batchId.'.xlsx', 'fake excel content');

        Queue::fake();

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson(route('customers.startQueueWorker'), [
                'batch_id' => $batchId,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '客戶匯入處理已啟動，請稍候監控進度',
        ]);

        // 驗證Job被推送
        Queue::assertPushed(ProcessCustomerImportJob::class);
    }

    public function test_file_size_validation()
    {
        Storage::fake('local');

        // 建立超過50MB的大檔案
        $largeFile = UploadedFile::fake()->create('large_customers.xlsx', 51 * 1024); // 51MB

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('customers.queuedImport'), [
                'file' => $largeFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('file');
    }

    public function test_file_format_validation()
    {
        Storage::fake('local');

        // 建立非Excel檔案
        $invalidFile = UploadedFile::fake()->create('customers.txt', 1024);

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('customers.import'), [
                'file' => $invalidFile,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_duplicate_customer_handling()
    {
        Storage::fake('local');

        // 先建立一個客戶
        Customer::create([
            'id_number' => 'A123456789',
            'name' => '現有客戶',
            'phone_number' => ['02-11111111'],
            'addresses' => ['原始地址'],
        ]);

        // 嘗試匯入相同身分證號的客戶
        $csvContent = "id_number,name,phone_number,addresses\n";
        $csvContent .= "A123456789,更新客戶,02-22222222,新地址\n";

        $file = UploadedFile::fake()->createWithContent(
            'customers.csv',
            $csvContent
        );

        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('customers.import'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('customers.index'));

        // 驗證客戶資料已更新
        $updatedCustomer = Customer::where('id_number', 'A123456789')->first();
        $this->assertEquals('更新客戶', $updatedCustomer->name);
        $this->assertEquals(['02-22222222'], $updatedCustomer->phone_number);
    }

    public function test_memory_management_with_large_dataset()
    {
        // 模擬記憶體管理測試
        $importer = new \App\Imports\CustomersImport;

        // 檢查記憶體管理方法存在
        $this->assertTrue(method_exists($importer, 'collection'));

        // 初始記憶體使用量
        $initialMemory = memory_get_usage(true);

        // 執行記憶體清理（通過反射呼叫私有方法）
        $reflection = new \ReflectionClass($importer);
        $method = $reflection->getMethod('performMemoryCleanup');
        $method->setAccessible(true);
        $method->invoke($importer, true); // 強制清理

        // 驗證記憶體沒有異常增長
        $afterMemory = memory_get_usage(true);
        $this->assertLessThanOrEqual($initialMemory * 2, $afterMemory, '記憶體使用量異常增長');
    }
}
