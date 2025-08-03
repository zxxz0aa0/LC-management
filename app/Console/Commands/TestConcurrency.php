<?php

namespace App\Console\Commands;

use App\Services\OrderNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestConcurrency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:concurrency {--threads=5 : 測試執行緒數量} {--orders=10 : 每個執行緒建立的訂單數量}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '測試訂單建立的併發安全性';

    private OrderNumberService $orderNumberService;

    public function __construct(OrderNumberService $orderNumberService)
    {
        parent::__construct();
        $this->orderNumberService = $orderNumberService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threads = $this->option('threads');
        $ordersPerThread = $this->option('orders');
        $totalOrders = $threads * $ordersPerThread;

        $this->info("開始併發測試：{$threads} 個執行緒，每個建立 {$ordersPerThread} 筆訂單");
        $this->info("預期總訂單數：{$totalOrders}");

        // 記錄測試開始前的序列號
        $startSequence = $this->orderNumberService->getTodaySequenceCount();
        $this->info("測試開始前序列號：{$startSequence}");

        $startTime = microtime(true);
        
        // 模擬併發訂單編號生成
        $processes = [];
        for ($i = 0; $i < $threads; $i++) {
            $processes[] = $this->simulateOrderCreation($i, $ordersPerThread);
        }

        // 等待所有模擬完成
        $results = [];
        foreach ($processes as $process) {
            $results = array_merge($results, $process);
        }

        $endTime = microtime(true);
        $endSequence = $this->orderNumberService->getTodaySequenceCount();

        // 分析結果
        $this->analyzeResults($results, $startSequence, $endSequence, $totalOrders, $endTime - $startTime);
    }

    /**
     * 模擬訂單建立（單執行緒）
     */
    private function simulateOrderCreation(int $threadId, int $orderCount): array
    {
        $results = [];
        $orderTypes = ['新北長照', '台北長照', '新北復康', '愛接送'];
        $testIdNumbers = ['A123456789', 'B234567890', 'C345678901', 'D456789012'];

        for ($i = 0; $i < $orderCount; $i++) {
            try {
                $orderType = $orderTypes[array_rand($orderTypes)];
                $idNumber = $testIdNumbers[array_rand($testIdNumbers)];
                
                $orderNumber = $this->orderNumberService->generateOrderNumber($orderType, $idNumber);
                
                $results[] = [
                    'thread_id' => $threadId,
                    'order_index' => $i,
                    'order_number' => $orderNumber,
                    'order_type' => $orderType,
                    'success' => true,
                    'error' => null
                ];
                
            } catch (\Exception $e) {
                $results[] = [
                    'thread_id' => $threadId,
                    'order_index' => $i,
                    'order_number' => null,
                    'order_type' => $orderType ?? 'unknown',
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * 分析測試結果
     */
    private function analyzeResults(array $results, int $startSequence, int $endSequence, int $expectedTotal, float $executionTime): void
    {
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $errorCount = count($results) - $successCount;
        $actualSequenceIncrease = $endSequence - $startSequence;

        $this->info("\n=== 測試結果分析 ===");
        $this->info("執行時間：" . round($executionTime, 3) . " 秒");
        $this->info("成功建立：{$successCount} 筆");
        $this->info("失敗數量：{$errorCount} 筆");
        $this->info("序列號增加：{$actualSequenceIncrease}");
        $this->info("預期序列號增加：{$expectedTotal}");

        // 檢查序列號一致性
        if ($actualSequenceIncrease === $successCount) {
            $this->info("✅ 序列號一致性檢查：通過");
        } else {
            $this->error("❌ 序列號一致性檢查：失敗");
            $this->error("   實際序列號增加 ({$actualSequenceIncrease}) 與成功訂單數 ({$successCount}) 不符");
        }

        // 檢查訂單編號唯一性
        $orderNumbers = array_filter(array_column($results, 'order_number'));
        $uniqueOrderNumbers = array_unique($orderNumbers);
        
        if (count($orderNumbers) === count($uniqueOrderNumbers)) {
            $this->info("✅ 訂單編號唯一性檢查：通過");
        } else {
            $this->error("❌ 訂單編號唯一性檢查：失敗");
            $duplicates = array_diff_assoc($orderNumbers, $uniqueOrderNumbers);
            $this->error("   發現重複編號：" . implode(', ', array_unique($duplicates)));
        }

        // 顯示錯誤摘要
        if ($errorCount > 0) {
            $this->warn("\n=== 錯誤摘要 ===");
            $errorMessages = array_unique(array_column(array_filter($results, fn($r) => !$r['success']), 'error'));
            foreach ($errorMessages as $error) {
                $errorOccurrences = count(array_filter($results, fn($r) => !$r['success'] && $r['error'] === $error));
                $this->warn("• {$error} (發生 {$errorOccurrences} 次)");
            }
        }

        // 效能統計
        $ordersPerSecond = $successCount > 0 ? round($successCount / $executionTime, 2) : 0;
        $this->info("\n=== 效能統計 ===");
        $this->info("平均建立速度：{$ordersPerSecond} 筆/秒");
        
        if ($successCount === $expectedTotal && $actualSequenceIncrease === $expectedTotal) {
            $this->info("\n🎉 併發測試完全成功！");
        } else {
            $this->warn("\n⚠️  併發測試發現問題，請檢查上述分析結果");
        }
    }
}
