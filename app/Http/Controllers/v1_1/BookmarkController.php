<?php

namespace App\Http\Controllers\v1_1;

use App\Http\Controllers\Controller;
use App\Models\FeedMission;
use App\Models\Mission;
use App\Models\MissionComment;
use App\Models\MissionGround;
use App\Models\MissionPush;
use App\Models\MissionStat;
use App\Models\Order;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookmarkController extends Controller
{
    public function index(Request $request, $category_id = null, $limit = null): array
    {
        $user_id = token()->uid;

        $category_id = $category_id ?? $request->get('category_id');
        $limit = $limit ?? $request->get('limit', 0);

        $data = MissionStat::when($category_id, function ($query, $category_id) {
            $query->where('missions.mission_category_id', $category_id);
        })
            ->when($category_id === 0, function ($query) {
                $query->where('is_event', 1);
            })
            ->where('mission_stats.user_id', $user_id)
            ->join('missions', 'missions.id', 'mission_stats.mission_id')
            ->join('mission_categories', 'mission_categories.id', 'missions.mission_category_id')
            ->join('users', 'users.id', 'missions.user_id') // 미션 제작자
            ->leftJoin('mission_products', 'mission_products.mission_id', 'missions.id')
            ->leftJoin('products', 'products.id', 'mission_products.product_id')
            ->leftJoin('brands', 'brands.id', 'products.brand_id')
            ->leftJoin('outside_products', 'outside_products.id', 'mission_products.outside_product_id')
            ->select([
                'mission_categories.id as category_id',
                'mission_categories.title as category_title',
                'mission_categories.emoji',
                'missions.id',
                'missions.title',
                DB::raw("IFNULL(missions.description, '') as description"),
                'missions.is_event',
                DB::raw("missions.id <= 1213 and missions.is_event = 1 as is_old_event"),
                'missions.event_type',
                'missions.is_ground',
                'missions.is_ocr',
                'missions.started_at',
                'missions.ended_at',
                DB::raw("(missions.started_at is null or missions.started_at<='" . date('Y-m-d H:i:s') . "') and
                    (missions.ended_at is null or missions.ended_at>'" . date('Y-m-d H:i:s') . "') as is_available"),
                'missions.thumbnail_image',
                'missions.success_count',
                'mission_stat_id' => MissionStat::withTrashed()->select('id')->whereColumn('mission_id', 'missions.id')
                    ->where('user_id', $user_id)->orderBy('id', 'desc')->limit(1),
                'mission_stat_user_id' => MissionStat::withTrashed()
                    ->select('user_id')
                    ->whereColumn('mission_id', 'missions.id')
                    ->where('user_id', $user_id)
                    ->orderBy('id', 'desc')
                    ->limit(1),
                'users.id as user_id',
                'users.nickname',
                'users.profile_image',
                'users.gender',
                'area' => area_like(),
                'mission_products.type as product_type', //'mission_products.product_id',
                DB::raw("IF(mission_products.type='inside', mission_products.product_id, mission_products.outside_product_id) as product_id"),
                DB::raw("IF(mission_products.type='inside', brands.name_ko, outside_products.brand) as product_brand"),
                DB::raw("IF(mission_products.type='inside', products.name_ko, outside_products.title) as product_title"),
                DB::raw("IF(mission_products.type='inside', products.thumbnail_image, outside_products.image) as product_image"),
                'outside_products.url as product_url',
                DB::raw("IF(mission_products.type='inside', products.price, outside_products.price) as product_price"),
                'place_address' => Place::select('address')->whereColumn('mission_places.mission_id', 'missions.id')
                    ->join('mission_places', 'mission_places.place_id', 'places.id')
                    ->orderBy('mission_places.id')->limit(1),
                'place_title' => Place::select('title')->whereColumn('mission_places.mission_id', 'missions.id')
                    ->join('mission_places', 'mission_places.place_id', 'places.id')
                    ->orderBy('mission_places.id')->limit(1),
                'place_description' => Place::select('description')
                    ->whereColumn('mission_places.mission_id', 'missions.id')
                    ->join('mission_places', 'mission_places.place_id', 'places.id')
                    ->orderBy('mission_places.id')
                    ->limit(1),
                'place_image' => Place::select('image')->whereColumn('mission_places.mission_id', 'missions.id')
                    ->join('mission_places', 'mission_places.place_id', 'places.id')
                    ->orderBy('mission_places.id')->limit(1),
                'place_url' => Place::select('url')->whereColumn('mission_places.mission_id', 'missions.id')
                    ->join('mission_places', 'mission_places.place_id', 'places.id')
                    ->orderBy('mission_places.id')->limit(1),
                'is_bookmark' => MissionStat::selectRaw('COUNT(1) > 0')->where('mission_stats.user_id', $user_id)
                    ->whereColumn('mission_id', 'missions.id'),
                'today_upload' => FeedMission::selectRaw("COUNT(1) > 0")
                    ->whereColumn('feed_missions.mission_id', 'missions.id')->where('feeds.user_id', $user_id)
                    ->where('feeds.created_at', '>=', init_today())
                    ->whereNull('feeds.deleted_at')
                    ->join('feeds', 'feeds.id', 'feed_missions.feed_id'),
                'bookmarks' => MissionStat::withTrashed()->selectRaw("COUNT(distinct user_id)")
                    ->whereColumn('mission_id', 'missions.id'),
                'comments' => MissionComment::selectRaw("COUNT(1)")->whereColumn('mission_id', 'missions.id'),
                'has_check' => FeedMission::selectRaw("COUNT(1) > 0")
                    ->whereColumn('feed_missions.mission_id', 'missions.id')->where('feeds.user_id', $user_id)
                    ->where('feeds.created_at', '>=', init_today())
                    ->whereNull('feeds.deleted_at')
                    ->join('feeds', 'feeds.id', 'feed_missions.feed_id'),
                'feed_id' => FeedMission::select('feed_id')
                    ->whereColumn('feed_missions.mission_id', 'missions.id')->where('feeds.user_id', $user_id)
                    ->where('feeds.created_at', '>=', init_today())
                    ->join('feeds', 'feeds.id', 'feed_missions.feed_id')->limit(1),
            ])
            ->withCount([
                'feeds' => function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                },
            ])
            ->orderBy('has_check')
            ->orderBy('is_event')
            ->orderBy('event_order')
            ->orderBy('id', 'desc')
            ->when($limit, function ($query, $limit) {
                $query->take($limit);
            })->get();

        $grounds = MissionGround::whereIn('mission_id', $data->pluck('id'))
            ->select([
                'mission_id',
                'intro_video',
                'logo_image',
                'code_title',
                'code',
                'code_placeholder',
                'code_image',
                'goal_distance_title',
                'goal_distances',
                'goal_distance_text',
                DB::raw("goal_distance_text is not null as `need_distance`"),
                'distance_placeholder',
            ])
            ->get();

        foreach ($grounds as $ground) {
            $data->firstWhere('id', $ground->mission_id)->ground = $ground;
        }

        if (!$category_id) {
            $tmp = [];
            foreach ($data->groupBy('category_title') as $i => $item) {
                $tmp[] = [
                    'id' => $item[0]->category_id,
                    'title' => $i,
                    'emoji' => $item[0]->emoji,
                    'missions' => $item->toArray(),
                ];
            }
            $data = $tmp;
        }

        return success([
            'result' => true,
            'missions' => $data,
        ]);
    }

    public function store(Request $request, $mission_id = null): array
    {
        $user_id = token()->uid;
        if (!$mission_id = $mission_id ?? $request->get('mission_id')) {
            return success(['result' => false, 'reason' => 'not enough data']);
        }
        $code = $request->get('code');
        $goal_distance = $request->get('goal_distance',
            MissionGround::where('mission_id', $mission_id)->value('goal_distances')[0] ?? null);

        $goal_distance = $goal_distance ? preg_replace('/[^\d.]+/', '', $goal_distance) : null;

        $mission = Mission::select([
            'missions.id',
            is_available(),
            'code' => MissionGround::select('code')->whereColumn('mission_id', 'missions.id'),
            'code_type' => MissionGround::select('code_type')
                ->whereColumn('mission_id', 'missions.id'),
            'max_no' => MissionStat::selectRaw("IFNULL(MAX(entry_no),0)")
                ->whereColumn('mission_id', 'missions.id')
                ->orderBy('mission_stats.id', 'desc'),
        ])
            ->where('missions.id', $mission_id)
            ->with('refundProducts', fn($query) => $query->select(['products.id']))
            ->first();

        if (!$mission->is_available && !$mission->is_reserve_available) {
            return success(['result' => false, 'message' => '참가 가능한 미션이 아닙니다.']);
        }
        if (MissionStat::where(['user_id' => $user_id, 'mission_id' => $mission_id])->exists()) {
            return success(['result' => false, 'message' => '이미 참여 중인 미션입니다.']);
        }
        if (!is_null($mission->code) && $mission->code !== $code) {
            return success(['result' => false, 'message' => '참여코드가 틀렸습니다. 다시 확인해주세요.']);
        }
        if ($mission->refundProducts->count() > 0) {
            $paid = Order::join('order_products', 'order_id', 'orders.id')
                ->where('user_id', $user_id)
                ->whereIn('product_id', $mission->refundProducts->pluck('id'))
                ->exists();
            if (!$paid) {
                return success(['result' => false, 'message' => '체험 제품을 먼저 주문해야 합니다.']);
            }
        }

        $data = MissionStat::create([
            'user_id' => $user_id,
            'mission_id' => $mission_id,
            'code' => $code,
            'entry_no' => $mission->max_no + 1,
            'goal_distance' => $goal_distance,
        ]);

        // 조건별 푸시
        $pushes = MissionPush::where('mission_id', $mission_id)->get();
        if (count($pushes) > 0) {
            foreach ($pushes as $push) {
                if ($push->type === 'bookmark' && $push->value > 0) {
                    PushController::send_mission_push($push, $user_id, $mission_id);
                }
            }
        }
        return success(['result' => (bool)$data]);
    }

    public function destroy($id): array
    {
        $user_id = token()->uid;

        if (is_null($id)) {
            return success([
                'result' => false,
                'reason' => 'not enough data',
            ]);
        }

        if ($bookmark = MissionStat::where(['user_id' => $user_id, 'mission_id' => $id])->first()) {
            DB::beginTransaction();

            $data = $bookmark->delete();

            DB::commit();
            return success(['result' => $data > 0]);
        } else {
            return success([
                'result' => false,
                'reason' => 'not bookmark',
            ]);
        }
    }
}
