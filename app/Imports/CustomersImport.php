<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomersImport implements ToCollection, WithHeadingRow
{
    public $successCount = 0;

    public $skipCount = 0;

    public $errorMessages = [];

    public function collection(Collection $rows)
    {
        $rowIndex = 2; // 從第2列開始讀資料（第1列為標題）

        foreach ($rows as $row) {
            $idNumber = trim($row['id_number'] ?? '');

            if (! $idNumber) {
                $this->errorMessages[] = "第 {$rowIndex} 列：缺少身分證號";
                $this->skipCount++;
                $rowIndex++;

                continue;
            }

            $customer = Customer::where('id_number', $idNumber)->first();
            $data = [];

            foreach ($row->toArray() as $key => $value) {
                $value = trim((string) $value);

                if ($value === '' || $key === 'id_number') {
                    continue;
                }

                if (in_array($key, ['phone_number', 'addresses'])) {
                    // 處理 JSON 或逗號格式
                    $array = [];
                    if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                        $decoded = json_decode($value, true);
                        $array = is_array($decoded) ? array_filter(array_map('trim', $decoded)) : [];
                    } else {
                        $array = array_filter(array_map('trim', explode(',', $value)));
                    }

                    if (count($array) === 0) {
                        continue;
                    } // 若解析結果為空，跳過

                    $data[$key] = $array;

                    continue;
                }

                if ($key === 'birthday' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $this->errorMessages[] = "第 {$rowIndex} 列：生日格式錯誤（應為 YYYY-MM-DD）";
                    $this->skipCount++;

                    continue 2;
                }

                $data[$key] = $value;
            }

            try {
                if ($customer) {
                    $customer->update($data);
                } else {
                    $data['id_number'] = $idNumber;
                    $data['name'] = $data['name'] ?? '未填寫';
                    Customer::create($data);
                }

                $this->successCount++;
            } catch (\Exception $e) {
                $msg = "第 {$rowIndex} 列：資料庫錯誤";
                if (str_contains($e->getMessage(), 'phone_number')) {
                    $msg .= '（phone_number 欄位錯誤）';
                }
                if (str_contains($e->getMessage(), 'addresses')) {
                    $msg .= '（addresses 欄位錯誤）';
                }

                // 記錄完整錯誤訊息以便診斷
                $msg .= ' - 詳細錯誤：'.$e->getMessage();

                // 記錄嘗試儲存的資料
                if (isset($data['phone_number'])) {
                    $msg .= ' | phone_number 內容：'.json_encode($data['phone_number']);
                }
                if (isset($data['addresses'])) {
                    $msg .= ' | addresses 內容：'.json_encode($data['addresses']);
                }

                $this->errorMessages[] = $msg;
                $this->skipCount++;
            }

            $rowIndex++;
        }
    }
}
