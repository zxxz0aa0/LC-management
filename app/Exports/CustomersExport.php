<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromQuery, WithHeadings, WithMapping
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = Customer::query();

        // 關鍵字搜尋
        if ($this->request->filled('keyword')) {
            $keyword = trim($this->request->keyword);
            if (strlen($keyword) >= 1) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('id_number', 'like', "%{$keyword}%")
                        ->orWhereJsonContains('phone_number', $keyword);
                });
            }
        }

        // 照會日期搜尋
        if ($this->request->filled('referral_date')) {
            $query->whereDate('referral_date', $this->request->referral_date);
        }

        // 建立時間區間搜尋
        if ($this->request->filled('created_start') && $this->request->filled('created_end')) {
            $startDate = \Carbon\Carbon::parse($this->request->created_start);
            $endDate = \Carbon\Carbon::parse($this->request->created_end);

            // 驗證時間範圍合理性
            if (! $endDate->lt($startDate)) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // 個案來源搜尋
        if ($this->request->filled('county_care')) {
            $query->where('county_care', $this->request->county_care);
        }

        // 個案狀態搜尋（只有在提交表單時才套用預設值）
        if ($this->request->has('status')) {
            $status = $this->request->input('status') ?: '開案中';
            $query->where('status', $status);
        }

        return $query->latest();
    }

    public function map($customer): array
    {
        return [
            $customer->name,
            $customer->id_number,
            implode(',', $customer->phone_number ?? []),
            /*$customer->birthday,
            $customer->gender,
            implode(',', $customer->phone_number ?? []),
            implode(',', $customer->addresses ?? []),
            $customer->contact_person,
            $customer->contact_phone,
            $customer->contact_relationship,
            $customer->email,
            $customer->wheelchair,
            $customer->stair_climbing_machine,
            $customer->ride_sharing,
            $customer->identity,
            $customer->note,
            $customer->a_mechanism,*/
            $customer->a_manager,
            $customer->special_status,
            $customer->county_care,
            $customer->service_company,
            $customer->status,
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'id_number',
            'phone_number',
            /*'birthday',
            'gender',
            'addresses',
            'contact_person',
            'contact_phone',
            'contact_relationship',
            'email',
            'wheelchair',
            'stair_climbing_machine',
            'ride_sharing',
            'identity',
            'note',
            'a_mechanism',*/
            'a_manager',
            'special_status',
            'county_care',
            'service_company',
            'status',
        ];
    }
}
