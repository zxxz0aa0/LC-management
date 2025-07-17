<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Exports\CustomersExport;
use App\Imports\CustomersImport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('id_number', 'like', "%{$keyword}%")
                ->orWhereJsonContains('phone_number', $keyword);
            });
        }

        $customers = $query->latest()->paginate(10);

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create_customer');
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
            'status' => 'required|in:開案中,暫停中,已結案',
        ]);

        // 檢查地址格式（每一筆都需包含“市”與“區”）
        $addressArray = array_map('trim', explode(',', $validated['addresses']));
        foreach ($addressArray as $address) {
            if (!preg_match('/市.+區/', $address)) {
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
            'note' => $request->note,
            'a_mechanism' => $request->a_mechanism,
            'a_manager' => $request->a_manager,
            'special_status' => $request->special_status,
            'county_care' => $request->county_care,
            'service_company' => $request->service_company,
            'status' => $validated['status'],
            'created_by' => auth()->user()->name ?? 'system',
        ]);

        return redirect()->route('customers.index')->with('success', '客戶已建立');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'id_number' => 'required|string|max:20',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone_number' => 'required|string',
            'addresses' => 'required|string',
            'status' => 'required|in:開案中,暫停中,已結案',
        ]);

        $addressArray = array_map('trim', explode(',', $validated['addresses']));
        foreach ($addressArray as $address) {
            if (!preg_match('/市.+區/', $address)) {
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

        $importer = new \App\Imports\CustomersImport();
        \Maatwebsite\Excel\Facades\Excel::import($importer, $request->file('file'));

        $success = $importer->successCount;
        $fail = $importer->skipCount;
        $errors = $importer->errorMessages;

        return redirect()->route('customers.index')->with([
            'success' => "匯入完成：成功 {$success} 筆，失敗 {$fail} 筆。",
            'import_errors' => $errors,
        ]);
    }

    public function batchDelete(Request $request)
{
    $ids = $request->input('ids', []);

    if (!empty($ids)) {
        Customer::whereIn('id', $ids)->delete();
        return redirect()->route('customers.index')->with('success', '已成功刪除選取的客戶');
    }

    return redirect()->route('customers.index')->with('error', '請先勾選要刪除的資料');
}

    //共乘對象查詢
    public function carpoolSearch(Request $request)
    {
        $keyword = $request->input('keyword');

        $customers = Customer::where(function ($query) use ($keyword) {
            $query->where('name', 'like', "%{$keyword}%")
                ->orWhere('id_number', 'like', "%{$keyword}%")
                ->orWhereJsonContains('phone_number', $keyword);
        })->get(['name', 'id_number', 'phone_number', 'addresses','id']);

        // 將 phone_number 改為只取第一個號碼
        $customers = $customers->map(function ($customer) {
            $customer->phone_number = is_array($customer->phone_number)
                ? ($customer->phone_number[0] ?? '')
                : $customer->phone_number;
            return $customer;
        });

        return response()->json($customers);
    }


}
