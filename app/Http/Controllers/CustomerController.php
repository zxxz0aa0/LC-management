<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Exports\CustomerTemplateExport;
use App\Models\Customer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ImportProgress;
use App\Imports\RowCountImport;
use App\Jobs\ProcessCustomerImportJob;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = collect(); // 預設為空集合
        $hasSearched = false;
        $searchError = null;

        if ($request->filled('keyword')) {
            $hasSearched = true;
            $keyword = trim($request->keyword); // 去除前後空格
            
            if (strlen($keyword) >= 1) {
                // 關鍵字有效，執行搜尋
                $query = Customer::query();
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('id_number', 'like', "%{$keyword}%")
                        ->orWhereJsonContains('phone_number', $keyword);
                });

                $customers = $query->latest()->get();
            } else {
                // 只有空格或空字串
                $searchError = '請輸入至少一個有效字元進行搜尋';
                $customers = collect(); // 確保為空結果
            }
        }

        return view('customers.index', compact('customers', 'hasSearched', 'searchError'));
    }

    public function create(Request $request)
    {
        return view('customers.create_customer', [
            'return_to' => $request->get('return_to'),
            'search_params' => $request->only(['keyword', 'start_date', 'end_date', 'customer_id'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_number' => 'required|string|max:20',
            'birthday' => 'nullable|date',
            'email' => 'nullable|email|unique:customers,email',
            'phone_number' => 'required|string',
            'addresses' => 'required|string',
            'referral_date' => 'required|date',
            'status' => 'required|in:開案中,暫停中,已結案',
        ]);

        // 檢查地址格式（每一筆都需包含“市”與“區”）
        $addressArray = array_map('trim', explode(',', $validated['addresses']));
        foreach ($addressArray as $address) {
            if (! preg_match('/市.+區/', $address)) {
                return back()->withErrors(['addresses' => '每筆地址必須包含「市」與「區」'])->withInput();
            }
        }

        $phones = array_map('trim', explode(',', $validated['phone_number']));

        Customer::create([
            'name' => $validated['name'],
            'id_number' => $validated['id_number'],
            'birthday' => $validated['birthday'],
            'email' => $validated['email'] ?? null,
            'phone_number' => $phones,
            'addresses' => $addressArray,
            'contact_person' => $request->contact_person,
            'contact_phone' => $request->contact_phone,
            'contact_relationship' => $request->contact_relationship,
            'gender' => $request->gender,
            'wheelchair' => $request->wheelchair,
            'stair_climbing_machine' => $request->stair_climbing_machine,
            'ride_sharing' => $request->ride_sharing,
            'identity' => $request->identity,
            'referral_date' => $validated['referral_date'],
            'note' => $request->note,
            'a_mechanism' => $request->a_mechanism,
            'a_manager' => $request->a_manager,
            'special_status' => $request->special_status,
            'county_care' => $request->county_care,
            'service_company' => $request->service_company,
            'status' => $validated['status'],
            'created_by' => auth()->user()->name ?? 'system',
        ]);

        // 根據 return_to 參數決定返回位置
        if ($request->get('return_to') === 'orders') {
            $searchParams = $request->only(['keyword', 'start_date', 'end_date', 'customer_id']);
            return redirect()->route('orders.index', $searchParams)->with('success', '客戶已建立');
        }

        return redirect()->route('customers.index')->with('success', '客戶已建立');
    }

    public function edit(Request $request, Customer $customer)
    {
        return view('customers.edit', [
            'customer' => $customer,
            'return_to' => $request->get('return_to'),
            'search_params' => $request->only(['keyword', 'start_date', 'end_date', 'customer_id'])
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_number' => 'required|string|max:20',
            'email' => 'nullable|email|unique:customers,email,'.$customer->id,
            'phone_number' => 'required|string',
            'addresses' => 'required|string',
            'status' => 'required|in:開案中,暫停中,已結案',
        ]);

        $addressArray = array_map('trim', explode(',', $validated['addresses']));
        foreach ($addressArray as $address) {
            if (! preg_match('/市.+區/', $address)) {
                return back()->withErrors(['addresses' => '每筆地址必須包含「市」與「區」'])->withInput();
            }
        }

        $phones = array_map('trim', explode(',', $validated['phone_number']));

        $customer->update([
            'name' => $validated['name'],
            'id_number' => $validated['id_number'],
            'email' => $validated['email'] ?? null,
            'phone_number' => $phones,
            'addresses' => $addressArray,
            'contact_person' => $request->contact_person,
            'contact_phone' => $request->contact_phone,
            'contact_relationship' => $request->contact_relationship,
            'birthday' => $request->birthday,
            'gender' => $request->gender,
            'wheelchair' => $request->wheelchair,
            'stair_climbing_machine' => $request->stair_climbing_machine,
            'ride_sharing' => $request->ride_sharing,
            'identity' => $request->identity,
            'note' => $request->note,
            'a_mechanism' => $request->a_mechanism,
            'a_manager' => $request->a_manager,
            'special_status' => $request->special_status,
            'county_care' => $request->county_care,
            'service_company' => $request->service_company,
            'status' => $validated['status'],
            'updated_by' => auth()->user()->name ?? 'system',
        ]);

        // 根據 return_to 參數決定返回位置
        if ($request->get('return_to') === 'orders') {
            $searchParams = $request->only(['keyword', 'start_date', 'end_date', 'customer_id']);
            return redirect()->route('orders.index', $searchParams)->with('success', '客戶已更新');
        }

        return redirect()->route('customers.index')->with('success', '客戶已更新');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('success', '客戶已刪除');
    }

    // 匯出 Excel
    public function export()
    {
        return Excel::download(new CustomersExport, 'customers.xlsx');
    }

    // 處理匯入
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $importer = new \App\Imports\CustomersImport;
        \Maatwebsite\Excel\Facades\Excel::import($importer, $request->file('file'));

        $success = $importer->successCount;
        $fail = $importer->skipCount;
        $errors = $importer->errorMessages;

        return redirect()->route('customers.index')->with([
            'success' => "匯入完成：成功 {$success} 筆，失敗 {$fail} 筆。",
            'import_errors' => $errors,
        ]);
    }

    // 處理佇列匯入 (適用於大量資料)
    public function queuedImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        // 生成唯一批次ID
        $batchId = Str::uuid()->toString();
        $filename = $request->file('file')->getClientOriginalName();

        // 儲存檔案
        $filePath = $request->file('file')->store('imports', 'local');

        // 預先讀取檔案計算總行數
        $rowCounter = new RowCountImport();
        \Maatwebsite\Excel\Facades\Excel::import($rowCounter, storage_path('app/' . $filePath));
        $totalRows = $rowCounter->getRowCount();

        // 建立進度記錄
        $importProgress = ImportProgress::create([
            'batch_id' => $batchId,
            'type' => 'customers',
            'filename' => $filename,
            'total_rows' => $totalRows,
            'status' => 'pending',
        ]);

        // 使用自定義 Job 加入佇列處理
        ProcessCustomerImportJob::dispatch($batchId, $filePath);

        return redirect()->route('customers.import.progress', ['batchId' => $batchId])
            ->with('success', "匯入已開始處理，總共 {$totalRows} 筆資料。請稍候並監控進度。");
    }

    // 查詢匯入進度
    public function importProgress($batchId)
    {
        $progress = ImportProgress::where('batch_id', $batchId)->firstOrFail();
        
        return view('customers.import-progress', compact('progress'));
    }

    // API: 取得匯入進度 JSON
    public function getImportProgress($batchId)
    {
        $progress = ImportProgress::where('batch_id', $batchId)->first();
        
        if (!$progress) {
            return response()->json(['error' => '找不到匯入記錄'], 404);
        }

        return response()->json([
            'batch_id' => $progress->batch_id,
            'filename' => $progress->filename,
            'total_rows' => $progress->total_rows,
            'processed_rows' => $progress->processed_rows,
            'success_count' => $progress->success_count,
            'error_count' => $progress->error_count,
            'status' => $progress->status,
            'progress_percentage' => $progress->progress_percentage,
            'error_messages' => $progress->error_messages,
            'started_at' => $progress->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $progress->completed_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! empty($ids)) {
            Customer::whereIn('id', $ids)->delete();

            return redirect()->route('customers.index')->with('success', '已成功刪除選取的客戶');
        }

        return redirect()->route('customers.index')->with('error', '請先勾選要刪除的資料');
    }

    // 更新客戶備註
    public function updateNote(Request $request, Customer $customer)
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $customer->update([
            'note' => $request->note,
            'updated_by' => auth()->user()->name ?? 'system',
        ]);

        return response()->json([
            'success' => true,
            'message' => '備註已更新',
            'note' => $customer->note,
        ]);
    }

    // 共乘對象查詢
    public function carpoolSearch(Request $request)
    {
        $keyword = $request->input('keyword');

        $customers = Customer::where(function ($query) use ($keyword) {
            $query->where('name', 'like', "%{$keyword}%")
                ->orWhere('id_number', 'like', "%{$keyword}%")
                ->orWhereJsonContains('phone_number', $keyword);
        })->get(['name', 'id_number', 'phone_number', 'addresses', 'id']);

        // 將 phone_number 改為只取第一個號碼
        $customers = $customers->map(function ($customer) {
            $customer->phone_number = is_array($customer->phone_number)
                ? ($customer->phone_number[0] ?? '')
                : $customer->phone_number;

            return $customer;
        });

        return response()->json($customers);
    }

    /**
     * 下載客戶匯入範例檔案
     */
    public function downloadTemplate()
    {
        return Excel::download(new CustomerTemplateExport, '客戶匯入範例檔案.xlsx');
    }

    /**
     * 啟動佇列處理
     */
    public function startQueueWorker(Request $request)
    {
        $batchId = $request->input('batch_id');
        
        // 檢查匯入記錄是否存在
        $importProgress = ImportProgress::where('batch_id', $batchId)->first();
        
        if (!$importProgress) {
            return response()->json([
                'success' => false,
                'message' => '找不到匯入記錄'
            ], 404);
        }
        
        // 檢查狀態是否為 pending
        if ($importProgress->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => '任務已經在處理中或已完成'
            ], 400);
        }
        
        try {
            // 使用 --once 參數只處理一個任務
            $command = 'php artisan queue:work --once';
            
            // 在 Windows 上使用 start 在背景執行
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = "start /B $command";
            } else {
                $command = "$command > /dev/null 2>&1 &";
            }
            
            // 執行命令
            exec($command, $output, $returnCode);
            
            return response()->json([
                'success' => true,
                'message' => '佇列處理已啟動，請稍候查看進度更新',
                'command' => $command
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '啟動佇列處理失敗：' . $e->getMessage()
            ], 500);
        }
    }
}
