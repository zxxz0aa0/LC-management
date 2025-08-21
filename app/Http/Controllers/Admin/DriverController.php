<?php

namespace App\Http\Controllers\Admin;

use App\Exports\DriversExport;
use App\Exports\DriverTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\DriversImport;
use App\Models\Driver;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::query();

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%")
                    ->orWhere('phone', 'like', "%$keyword%")
                    ->orWhere('id_number', 'like', "%$keyword%")
                    ->orWhere('fleet_number', 'like', "%$keyword%");
            });
        }

        $drivers = $query->paginate(20);

        return view('admin.drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('admin.drivers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required|unique:drivers',
            'id_number' => 'required|unique:drivers',
        ]);

        Driver::create($request->all());

        return redirect()->route('drivers.index')->with('success', '駕駛新增成功');
    }

    public function edit(Driver $driver)
    {
        return view('admin.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required|unique:drivers,phone,'.$driver->id,
            'id_number' => 'required|unique:drivers,id_number,'.$driver->id,
        ]);

        $driver->update($request->all());

        return redirect()->route('drivers.index')->with('success', '駕駛更新成功');
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();

        return redirect()->route('drivers.index')->with('success', '駕駛已刪除');
    }

    public function searchByFleetNumber(Request $request)
    {
        $fleetNumber = $request->input('fleet_number');

        $driver = \App\Models\Driver::where('fleet_number', $fleetNumber)->first();

        if (! $driver) {
            return response()->json(['error' => '查無此隊編'], 404);
        }

        return response()->json([
            'id' => $driver->id,
            'name' => $driver->name,
            'plate_number' => $driver->plate_number,
        ]);
    }

    /**
     * 匯出駕駛資料為 Excel 檔案
     */
    public function export()
    {
        return Excel::download(new DriversExport, '駕駛資料_'.date('Y-m-d').'.xlsx');
    }

    /**
     * 匯入駕駛資料
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $importer = new DriversImport;
        Excel::import($importer, $request->file('file'));

        $success = $importer->successCount;
        $fail = $importer->skipCount;
        $errors = $importer->errorMessages;

        return redirect()->route('drivers.index')->with([
            'success' => "匯入完成：成功 {$success} 筆，失敗 {$fail} 筆。",
            'import_errors' => $errors,
        ]);
    }

    /**
     * 下載駕駛匯入範例檔案
     */
    public function downloadTemplate()
    {
        return Excel::download(new DriverTemplateExport, '駕駛匯入範例檔案.xlsx');
    }
}
