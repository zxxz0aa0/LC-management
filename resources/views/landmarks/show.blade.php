@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">地標詳情</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="120">地標名稱：</th>
                            <td>{{ $landmark->name }}</td>
                        </tr>
                        <tr>
                            <th>完整地址：</th>
                            <td>{{ $landmark->city }}{{ $landmark->district }}{{ $landmark->address }}</td>
                        </tr>
                        <tr>
                            <th>城市：</th>
                            <td>{{ $landmark->city }}</td>
                        </tr>
                        <tr>
                            <th>區域：</th>
                            <td>{{ $landmark->district }}</td>
                        </tr>
                        <tr>
                            <th>分類：</th>
                            <td>
                                <span class="badge bg-info">{{ $landmark->category_name }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>狀態：</th>
                            <td>
                                @if($landmark->is_active)
                                    <span class="badge bg-success">啟用</span>
                                @else
                                    <span class="badge bg-secondary">停用</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="120">使用次數：</th>
                            <td>{{ $landmark->usage_count }}</td>
                        </tr>
                        <tr>
                            <th>建立者：</th>
                            <td>{{ $landmark->created_by }}</td>
                        </tr>
                        <tr>
                            <th>建立時間：</th>
                            <td>{{ $landmark->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>更新時間：</th>
                            <td>{{ $landmark->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        @if($landmark->coordinates)
                            <tr>
                                <th>座標：</th>
                                <td>
                                    @if(isset($landmark->coordinates['lat']) && isset($landmark->coordinates['lng']))
                                        經度: {{ $landmark->coordinates['lng'] }}<br>
                                        緯度: {{ $landmark->coordinates['lat'] }}
                                    @else
                                        {{ json_encode($landmark->coordinates) }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($landmark->description)
                <div class="row">
                    <div class="col-12">
                        <h5>描述：</h5>
                        <p class="text-muted">{{ $landmark->description }}</p>
                    </div>
                </div>
            @endif

            <div class="form-group">
                <a href="{{ route('landmarks.edit', $landmark) }}" class="btn btn-warning">編輯</a>
                <a href="{{ route('landmarks.index') }}" class="btn btn-secondary">返回列表</a>
                <form method="POST" action="{{ route('landmarks.destroy', $landmark) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('確定要刪除此地標嗎？')">刪除</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection