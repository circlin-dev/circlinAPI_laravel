@extends('layouts.admin')

@section('title', '배너 클릭률 통계')

@section('content')
    @php
        $header = ['홈 배너', '미션 탭 배너', '샵 배너'];
        $link_type = [
            'mission' => '미션',
            'event_mission' => '이벤트 미션',
            'event_mission_old' => '이벤트 미션',
            'product' => '제품',
            'notice' => '공지사항',
            'url' => 'URL'
        ]
    @endphp
    @foreach([$floats, $locals, $shops] as $i => $data)
        <p style="font-size: 20px">{{ $header[$i] }}
            : <b>{{ number_format($data->total()) }}</b> 개</p>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px" rowspan="3">ID</th>
                    <th style="width: 250px" rowspan="3">이미지</th>
                    <th style="width: 200px" rowspan="3">배너명</th>
                    <th style="width: 100px" rowspan="3">배너 기간</th>
                    <th style="width: 100px" rowspan="3">링크 종류</th>
                    <th style="width: 200px" rowspan="3">링크 주소</th>
                    <th style="width: 600px" colspan="14">클릭 수 / 노출 수 / 클릭률</th>
                    <tr>
                        <th style="width: 100px" colspan="1" rowspan="2">전체</th>
                        <th style="width: 250px" colspan="3">OS별</th>
                        <th style="width: 250px" colspan="3">성별</th>
                        <th style="width: 300px" colspan="7">연령별</th>
                        <tr>
                            <th style="width: 100px">안드로이드</th>
                            <th style="width: 100px">IOS</th>
                            <th style="width: 100px">그 외</th>
                            <th style="width: 100px">남성</th>
                            <th style="width: 100px">여성</th>
                            <th style="width: 100px">성별 미상</th>

                            <th style="width: 66px">10대 이하</th>
                            <th style="width: 66px">20대</th>
                            <th style="width: 66px">30대</th>
                            <th style="width: 66px">40대</th>
                            <th style="width: 66px">50대</th>
                            <th style="width: 66px">50대 초과</th>
                            <th style="width: 66px">연령 미상</th>
                        </tr>
                    </tr>
                </tr>
{{--                <tr>--}}
{{--                    <tr>--}}
{{--                        <th style="width: 100px" colspan="1">전체</th>--}}
{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <th style="width: 100px" colpan="3">OS별</th>--}}
{{--                        <tr>--}}
{{--                            <th style="width: 33px">안드로이드</th>--}}
{{--                            <th style="width: 33px">IOS</th>--}}
{{--                            <th style="width: 33px">그 외</th>--}}
{{--                        </tr>--}}
{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <th style="width: 100px" colspan="3">성별</th>--}}
{{--                        <tr>--}}
{{--                            <th style="width: 33px">성별(남성)</th>--}}
{{--                            <th style="width: 33px">성별(여성)</th>--}}
{{--                            <th style="width: 33px">성별(미입력자)</th>--}}
{{--                        </tr>--}}
{{--                    </tr>--}}
{{--                </tr>--}}
{{--                <tr>--}}
{{--                    <th style="width: 100px">전체</th>--}}
{{--                    <th style="width: 100px">안드로이드</th>--}}
{{--                    <th style="width: 100px">IOS</th>--}}
{{--                    <th style="width: 100px">그 외</th>--}}
{{--                    <th style="width: 100px">성별(남성)</th>--}}
{{--                    <th style="width: 100px">성별(여성)</th>--}}
{{--                    <th style="width: 100px">성별(미입력자)</th>--}}
{{--                </tr>--}}
            </thead>
            <tbody>
            @forelse($data as $item)
                <tr>
                    <td class="center">{{ $item->id }}</td>
                    <td><img src="{{ $item->image }}" alt="" width="100%"></td>
                    <td><a href="{{ route('admin.banner.log.show', ['id' => $item->id]) }}">{{ $item->name }}</a></td>
                    <td class="center">
                        {{ $item->started_at ?? '-' }}
                        <br>~<br>{{ $item->ended_at ?? '-' }}
                        {!! $item->is_available ? '' : '<br><span style="color:red">(종료됨)</span>' !!}
                    </td>
                    <td class="center">{{ $link_type[$item->link_type] }}</td>
                    <td>
                        @if($item->link_type === 'url')
                            <a href="{{ $item->link_url }}">{{ $item->link_url }}</a>
                        @elseif($item->link_type === 'notice')
                            <a href="{{ route('admin.notice.show', ['notice' => $item->link_id]) }}">
                                {{ $link_type[$item->link_type] }}
                            </a>
                        @else
                            {{ array_key_exists($item->link_type, $link_type) ? $link_type[$item->link_type] : '' }}
                            ( {{ $item->link_id }}
                            )
                        @endif
                    </td>
                    <td class="center">
                        {{ $item->clicks_count }}
                        / {{ $item->views_count }}
                        <br><br>{{ round($item->clicks_count / max($item->views_count, 1) * 100, 1) }}
                        %
                    </td>
                    {{-- OS별 --}}
                    <td class="center">
                        {{ $item->android_clicks_count }}
                        / {{ $item->android_views_count }}
                        <br><br>{{ round($item->android_clicks_count / max($item->android_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->ios_clicks_count }}
                        / {{ $item->ios_views_count }}
                        <br><br>{{ round($item->ios_clicks_count / max($item->ios_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->etc_clicks_count }}
                        / {{ $item->etc_views_count }}
                        <br><br>{{ round($item->etc_clicks_count / max($item->etc_views_count, 1) * 100, 1) }}
                        %
                    </td>

                    {{-- 성별 --}}
                    <td class="center">
                        {{ $item->male_clicks_count }}
                        / {{ $item->male_views_count }}
                        <br><br>{{ round($item->male_clicks_count / max($item->male_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->female_clicks_count }}
                        / {{ $item->female_views_count }}
                        <br><br>{{ round($item->female_clicks_count / max($item->female_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->no_gender_clicks_count }}
                        / {{ $item->no_gender_views_count }}
                        <br><br>{{ round($item->no_gender_clicks_count / max($item->no_gender_views_count, 1) * 100, 1) }}
                        %
                    </td>

                    {{-- 연령별 --}}
                    <td class="center">
                        {{ $item->age_10_clicks_count }}
                        / {{ $item->age_10_views_count }}
                        <br><br>{{ round($item->age_10_clicks_count / max($item->age_10_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->age_20_clicks_count }}
                        / {{ $item->age_20_views_count }}
                        <br><br>{{ round($item->age_20_clicks_count / max($item->age_20_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->age_30_clicks_count }}
                        / {{ $item->age_30_views_count }}
                        <br><br>{{ round($item->age_30_clicks_count / max($item->age_30_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->age_40_clicks_count }}
                        / {{ $item->age_40_views_count }}
                        <br><br>{{ round($item->age_40_clicks_count / max($item->age_40_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->age_50_clicks_count }}
                        / {{ $item->age_50_views_count }}
                        <br><br>{{ round($item->age_50_clicks_count / max($item->age_50_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->age_others_clicks_count }}
                        / {{ $item->age_others_views_count }}
                        <br><br>{{ round($item->age_others_clicks_count / max($item->age_others_views_count, 1) * 100, 1) }}
                        %
                    </td>
                    <td class="center">
                        {{ $item->age_unknown_clicks_count }}
                        / {{ $item->age_unknown_views_count }}
                        <br><br>{{ round($item->age_unknown_clicks_count / max($item->age_unknown_views_count, 1) * 100, 1) }}
                        %
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="center">배너가 없습니다.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <br>
        <div class="center">{{ $floats->withQueryString()->links() }}</div>
    @endforeach
@endsection
