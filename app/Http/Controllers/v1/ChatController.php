<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatUser;
use App\Models\FeedImage;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function create_room(Request $request): array
    {
        $data = ChatRoom::create(['title' => $request->get('title')]);

        $this->enter_room($request, $data->id);

        return success([
            'result' => true,
            'room' => $data,
        ]);
    }

    public function delete_room(Request $request): array
    {
        // 필요하려나?
    }

    public function enter_room(Request $request, $room_id): array
    {
        $data = ChatUser::create(['chat_room_id' => $room_id, 'user_id' => token()->uid]);

        return success(['result' => true]);
    }

    public function leave_room(Request $request, $room_id): array
    {
        $data = ChatUser::where(['chat_room_id' => $room_id, 'user_id' => token()->uid])->delete();

        return success(['result' => $data > 0]);
    }

    public function send_message(Request $request, $room_id): array
    {
        if (ChatUser::where(['chat_room_id' => $room_id, 'user_id' => token()->uid])->exists()) {
            $data = ChatMessage::create([
                'chat_room_id' => $room_id,
                'user_id' => token()->uid,
                'message' => $request->get('message'),
            ]);

            return success(['result' => true]);
        } else {
            return success([
                'result' => false,
                'reason' => 'not enter room',
            ]);
        }
    }

    /* 1대1 채팅방 입장 */
    public function create_or_enter_room(Request $request, $target_id): array
    {
        try {
            DB::beginTransaction();

            $user_id = token()->uid;

            $room = ChatRoom::join('chat_users as cu1', function ($query) use ($user_id) {
                $query->on('cu1.chat_room_id', 'chat_rooms.id')->where('cu1.user_id', $user_id);
            })
                ->join('chat_users as cu2', function ($query) use ($target_id) {
                    $query->on('cu1.chat_room_id', 'chat_rooms.id')->where('cu2.user_id', $target_id);
                })
                ->select('chat_rooms.*')
                ->first();

            if (!$room) {
                $room = $this->create_room($request)['data']['room'];
                ChatUser::create(['chat_room_id' => $room->id, 'user_id' => $target_id]);
            }

            $users = ChatUser::where('chat_room_id', $room->id)
                ->whereIn('user_id', [$user_id])
                ->pluck('user_id');

            foreach (array_diff([$user_id], $users->toArray()) as $user) {
                ChatUser::create([
                    'chat_room_id' => $room->id,
                    'user_id' => $user,
                ]);
            }

            DB::commit();

            return success([
                'result' => true,
                'room' => $room,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return exceped($e);
        }
    }

    /* 1대1 메시지 전송 */
    public function send_direct(Request $request, $target_id): array
    {
        try {
            $user_id = token()->uid;

            if ($user_id == $target_id) {
                return success([
                    'result' => false,
                    'reason' => 'myself',
                ]);
            }

            $message = $request->get('message');
            $file = $request->file('file');
            $mission_id = $request->get('mission_id');
            $feed_id = $request->get('feed_id');

            if (!$target_id || (!$message && !$file && !$mission_id && !$feed_id)) {
                return success([
                    'result' => false,
                    'reason' => 'not enough data',
                ]);
            }

            $room = $this->create_or_enter_room($request, $target_id)['data']['room'];

            $users = ChatUser::where('chat_room_id', $room->id)
                ->whereIn('user_id', [$user_id, $target_id])
                ->pluck('user_id');

            foreach (array_diff([$user_id, $target_id], $users->toArray()) as $user) {
                ChatUser::create([
                    'chat_room_id' => $room->id,
                    'user_id' => $user,
                ]);
            }

            $this->send_message($request, $room->id);

            return success(['result' => true]);
        } catch (Exception $e) {
            return exceped($e);
        }
    }

    public function index(Request $request): array
    {
        $user_id = token()->uid;

        $data = ChatUser::where('chat_users.user_id', $user_id)
            ->select([
                'chat_users.chat_room_id',
                'nickname' => DB::table('chat_users as cu')->select('nickname')
                    ->whereColumn('cu.chat_room_id', 'chat_users.chat_room_id')
                    ->whereColumn('cu.user_id', '!=', 'chat_users.user_id')
                    ->join('users', 'users.id', 'user_id')->limit(1),
                'profile_image' => DB::table('chat_users as cu')->select('profile_image')
                    ->whereColumn('cu.chat_room_id', 'chat_users.chat_room_id')
                    ->whereColumn('cu.user_id', '!=', 'chat_users.user_id')
                    ->join('users', 'users.id', 'user_id')->limit(1),
                'latest_message' => ChatMessage::select('message')->whereColumn('chat_room_id', 'chat_users.chat_room_id')
                    ->orderBy('id', 'desc')->limit(1),
                'latest_at' => ChatMessage::select('created_at')->whereColumn('chat_room_id', 'chat_users.chat_room_id')
                    ->orderBy('id', 'desc')->limit(1),
            ])
            ->get();

        return success([
            'result' => true,
            'rooms' => $data,
        ]);
    }

    public function show(Request $request, $room_id): array
    {
        $user_id = token()->uid;

        $loaded_id = $request->get('loaded_id');

        if (ChatUser::where(['chat_room_id' => $room_id, 'user_id' => $user_id])->doesntExist()) {
            return success([
                'result' => false,
                'reason' => 'not enter room',
            ]);
        }

        $messages = ChatMessage::where('chat_room_id', $room_id)
            ->when($loaded_id, function ($query, $loaded_id) {
                $query->where('chat_messages.id', '<', $loaded_id);
            })
            ->leftJoin('missions', 'missions.id', 'chat_messages.mission_id')
            ->select([
                'chat_messages.user_id', 'chat_messages.message', 'chat_messages.image', 'chat_messages.created_at',
                'missions.title as mission_title', 'missions.description as mission_description',
                'missions.thumbnail_image as mission_thumbnail_image',
                'feed_image' => FeedImage::select('image')->whereColumn('feed_id', 'chat_messages.feed_id')->limit(1),
            ])
            ->orderBy('chat_messages.id', 'desc')
            ->take(20)->get();

        foreach ($messages as $i => $message) {
            $messages[$i]->mission = arr_group($messages[$i], ['title', 'description', 'thumbnail_image'], 'mission_');
        }

        return success([
            'result' => true,
            'messages' => $messages,
        ]);
    }
}