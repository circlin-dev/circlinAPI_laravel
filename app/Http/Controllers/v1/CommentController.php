<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Feed;
use App\Models\FeedComment;
use App\Models\Mission;
use App\Models\MissionComment;
use App\Models\Notice;
use App\Models\NoticeComment;
use App\Models\ProductReview;
use App\Models\ProductReviewComment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    public function index(Request $request, $table, $id): array
    {
        try {
            $page = $request->get('page', 0);
            $limit = $request->get('limit', 50);

            $query_comment = match ($table) {
                'feed' => new FeedComment(),
                'mission' => new MissionComment(),
                'notice' => new NoticeComment(),
                'product_review' => new ProductReviewComment(),
            };

            $query_comment = $query_comment->withTrashed()->where("{$table}_id", $id)
                ->whereRaw("({$table}_comments.deleted_at is null or
                    (depth=0 and (select COUNT(1) from {$table}_comments c where c.{$table}_id={$table}_comments.{$table}_id and c.depth>0 and c.group={$table}_comments.group)>0))")
                ->join('users', 'users.id', "{$table}_comments.user_id")
                ->select([
                    "{$table}_comments.group", "{$table}_comments.depth",
                    DB::raw("{$table}_comments.deleted_at is not null as is_delete"),
                    "{$table}_comments.created_at",
                    "{$table}_comments.id",
                    // DB::raw("IF({$table}_comments.deleted_at is null, {$table}_comments.comment, null) as comment"),
                    "{$table}_comments.comment",
                    'users.id as user_id',
                    'users.nickname',
                    'users.profile_image',
                    'users.gender',
                ])
                ->orderBy('group', 'desc')->orderBy('depth')->orderBy('id');

            $total = $query_comment->count();

            $comments = $query_comment->skip($page * $limit)->take($limit)->get();

            return success([
                'result' => true,
                'total' => $total,
                'comment_total' => $total,
                'comments' => $comments,
            ]);
        } catch (Exception $e) {
            return exceped($e);
        }
    }

    public function store($table, $id, $group, $comment)
    {
        try {
            DB::beginTransaction();

            $user_id = token()->uid;

            if (!$comment) {
                return success([
                    'result' => false,
                    'reason' => 'not enough data',
                ]);
            }

            $query = match ($table) {
                'feed' => new Feed(),
                'mission' => new Mission(),
                'notice' => new Notice(),
                'product_review' => new ProductReview(),
            };

            $query_comment = match ($table) {
                'feed' => new FeedComment(),
                'mission' => new MissionComment(),
                'notice' => new NoticeComment(),
                'product_review' => new ProductReviewComment(),
            };

            $max_group = $query_comment->withTrashed()->where("{$table}_id", $id)->max('group') ?? -1;
            $group = $group ?? ($max_group + 1);

            $data = $query_comment->create([
                "{$table}_id" => $id, 'user_id' => $user_id,
                'group' => min($group, $max_group + 1),
                'depth' => ($group >= $max_group + 1) ? 0 : 1,
                'comment' => $comment,
            ]);

            // 답글인 경우 푸시
            /*$comment_target_id = $query_comment->where(["{$table}_id" => $id, 'group' => $group, 'depth' => 0])->value('user_id');
            if ($group <= $max_group && $comment_target_id !== $user_id) {
                NotificationController::send($comment_target_id, "{$table}_reply", $user_id, $data->id, true);
            }*/

            // 글 주인한테 푸시
            if ($table !== 'notice' && ($feed_target_id = $query->where('id', $id)->value('user_id')) !== $user_id) {
                NotificationController::send($feed_target_id, "{$table}_comment", $user_id, $data->id, true);
            }

            DB::commit();

            return success(['result' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            return exceped($e);
        }
    }

    public function destroy($table, $id, $comment_id)
    {
        try {
            $data = match ($table) {
                'feed' => FeedComment::where(['id' => $comment_id, "{$table}_id" => $id])->first(),
                'mission' => MissionComment::where(['id' => $comment_id, "{$table}_id" => $id])->first(),
                'notice' => NoticeComment::where(['id' => $comment_id, "{$table}_id" => $id])->first(),
                'product_review' => ProductReviewComment::where(['id' => $comment_id, "{$table}_id" => $id])->first(),
            };

            if (is_null($data)) {
                return success([
                    'result' => false,
                    'reason' => 'not exists comment',
                ]);
            }

            if (token()->uid !== $data->user_id) {
                return success([
                    'result' => false,
                    'reason' => 'not access comment',
                ]);
            }

            $data->delete();

            return success(['result' => true]);
        } catch (Exception $e) {
            return exceped($e);
        }
    }
}
