<?php

namespace App\Http\Controllers;

use App\Models\Landmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LandmarkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Landmark::query();

        // 關鍵字搜尋
        if ($request->filled('keyword')) {
            $query->search($request->keyword);
        }

        // 分類篩選
        if ($request->filled('category')) {
            $query->category($request->category);
        }

        // 狀態篩選
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 排序
        $sortBy = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        if ($sortBy === 'usage_count') {
            $query->orderBy('usage_count', 'desc');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $landmarks = $query->paginate(20);

        return view('landmarks.index', compact('landmarks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('landmarks.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:50',
            'district' => 'required|string|max:50',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // 檢查地址格式（需包含"市"與"區"）
        $fullAddress = $validated['city'].$validated['district'].$validated['address'];
        if (! preg_match('/市.+區/', $fullAddress)) {
            return back()->withErrors(['address' => '地址必須包含「市」與「區」'])->withInput();
        }

        $validated['created_by'] = Auth::user()->name ?? 'System';
        $validated['is_active'] = $request->has('is_active');

        Landmark::create($validated);

        return redirect()->route('landmarks.index')->with('success', '地標新增成功');
    }

    /**
     * Display the specified resource.
     */
    public function show(Landmark $landmark)
    {
        return view('landmarks.show', compact('landmark'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Landmark $landmark)
    {
        return view('landmarks.edit', compact('landmark'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Landmark $landmark)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:50',
            'district' => 'required|string|max:50',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // 檢查地址格式（需包含"市"與"區"）
        $fullAddress = $validated['city'].$validated['district'].$validated['address'];
        if (! preg_match('/市.+區/', $fullAddress)) {
            return back()->withErrors(['address' => '地址必須包含「市」與「區」'])->withInput();
        }

        $validated['is_active'] = $request->has('is_active');

        $landmark->update($validated);

        return redirect()->route('landmarks.index')->with('success', '地標更新成功');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Landmark $landmark)
    {
        $landmark->delete();

        return redirect()->route('landmarks.index')->with('success', '地標刪除成功');
    }

    /**
     * 地標搜尋 API
     */
    public function search(Request $request)
    {
        $keyword = $request->get('keyword', '');

        if (empty($keyword)) {
            return response()->json([
                'success' => false,
                'message' => '請輸入搜尋關鍵字',
            ]);
        }

        $landmarks = Landmark::active()
            ->search($keyword)
            ->popular()
            ->limit(10)
            ->get(['id', 'name', 'address', 'city', 'district', 'category', 'usage_count']);

        return response()->json([
            'success' => true,
            'data' => $landmarks,
        ]);
    }

    /**
     * 批量刪除
     */
    public function batchDestroy(Request $request)
    {
        $ids = $request->get('ids', []);

        if (empty($ids)) {
            return back()->with('error', '請選擇要刪除的地標');
        }

        Landmark::whereIn('id', $ids)->delete();

        return back()->with('success', '批量刪除成功');
    }

    /**
     * 批量啟用/停用
     */
    public function batchToggle(Request $request)
    {
        $ids = $request->get('ids', []);
        $status = $request->get('status', true);

        if (empty($ids)) {
            return back()->with('error', '請選擇要修改的地標');
        }

        Landmark::whereIn('id', $ids)->update(['is_active' => $status]);

        $message = $status ? '批量啟用成功' : '批量停用成功';

        return back()->with('success', $message);
    }
}
