<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // 顯示所有訂單列表（預留）
    public function index()
    {
            $orders = \App\Models\Order::orderBy('ride_date', 'desc')->get();
            return view('orders.index', compact('orders'));
    }

    // 顯示新增訂單表單畫面
    public function create()
    {
        return view('orders.create');
    }

    // 儲存新訂單資料（之後會補功能）
    public function store(Request $request)
    {
        // 等等再補功能
    }

    // 顯示單筆訂單資料（預留）
    public function show(Order $order)
    {
        return view('orders.show', compact('order'));
    }

    // 顯示編輯表單（預留）
    public function edit(Order $order)
    {
        return view('orders.edit', compact('order'));
    }

    // 更新訂單資料（預留）
    public function update(Request $request, Order $order)
    {
        // 等等再補功能
    }

    // 刪除訂單（預留）
    public function destroy(Order $order)
    {
        // 等等再補功能
    }
}
