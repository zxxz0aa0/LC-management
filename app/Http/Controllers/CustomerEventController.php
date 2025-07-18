<?php

namespace App\Http\Controllers;

use App\Models\CustomerEvent;
use Illuminate\Http\Request; // ⬅️ 確保有引入模型

class CustomerEventController extends Controller
{
    // 儲存事件
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'event' => 'required|string',
            'event_date' => 'nullable|date',
        ]);

        CustomerEvent::create([
            'customer_id' => $request->customer_id,
            'event_date' => $request->event_date,
            'event' => $request->event,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', '事件已新增');
    }

    // 更新事件內容
    public function update(Request $request, $id)
    {
        $request->validate([
            'event' => 'required|string',
        ]);

        $event = CustomerEvent::findOrFail($id);
        $event->event = $request->event;
        $event->save();

        return back()->with('success', '事件已更新');
    }

    // 刪除事件
    public function destroy($id)
    {
        $event = CustomerEvent::findOrFail($id);
        $event->delete();

        return back()->with('success', '事件已刪除');
    }
}
