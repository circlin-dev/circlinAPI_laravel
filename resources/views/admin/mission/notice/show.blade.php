@extends('layouts.admin')

@section('title', '미션 공지사항')

@section('content')
    <div class="grid">
        <div class="item head">제목</div>
        <div class="item">{{ $data['title'] }}</div>
        <div class="item" style="grid-column: 2/-1">{{ $data['created_at'] }}</div>
        <div class="item head">내용</div>
        <div class="item">{{ $data['body'] }}</div>
        <div class="item img-wrapper" style="grid-column: 1/-1">
            @foreach($data['images'] as $image)
                <img src="{{ $image->image }}" alt="">
            @endforeach
        </div>
    </div>
    <br>

    <style>
        .grid {
            display: grid;
            width: 1200px;
            grid-template-columns: 1fr 3fr;
        }

        .item {
            padding: 8px;
        }

        .img-wrapper {
            display: grid;
            grid-template-columns: 33%;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }
    </style>
@endsection
