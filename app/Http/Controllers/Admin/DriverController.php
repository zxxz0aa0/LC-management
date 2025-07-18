<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

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

        $drivers = $query->get();

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
}
