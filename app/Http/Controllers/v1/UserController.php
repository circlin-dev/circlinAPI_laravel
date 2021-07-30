<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\User;
use App\Models\UserFavoriteCategory;
use App\Models\UserStat;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function update_profile(Request $request): array
    {
        try {
            $user_id = JWT::decode($request->header('token'), env('JWT_SECRET'), ['HS256'])->uid;
            $nickname = $request->get('nickname');
            $area_code = $request->get('area_code');
            $gender = $request->get('gender');

            $data = User::where('id', $user_id)->first();
            if (isset($data)) {
                $user_data = [];
                $user_stat_data = [];
                if ($nickname && (new AuthController())->exists_nickname($nickname)['data']['exists']) {
                    $user_data['nickname'] = $nickname;
                }
                if ($area_code) {
                    $user_data['area_code'] = $area_code;
                }
                $user = User::where('id', $user_id)->update($user_data);

                if ($gender) {
                    $user_stat_date['gender'] = $gender;
                }
                $user_stat = UserStat::where('user_id', $user_id)->update($user_stat_data);
            } else {
                return success([
                    'result' => false,
                    'reason' => 'not enough data',
                ]);
            }
        } catch (Exception $e) {
            return failed($e);
        }
    }

    public function add_favorite_category(Request $request)
    {
        try {
            $user_id = JWT::decode($request->header('token'), env('JWT_SECRET'), ['HS256'])->uid;
            $category_id = $request->get('category_id');

            if (is_null($category_id)) {
                return success([
                    'result' => false,
                    'reason' => 'not enough data',
                ]);
            }

            if (UserFavoriteCategory::where(['user_id' => $user_id, 'category_id' => $category_id])->exists()) {
                return success(['result' => false, 'reason' => 'already following']);
            } else {
                $data = UserFavoriteCategory::create(['user_id' => $user_id, 'category_id' => $category_id]);
                if ($data) {
                    return success(['result' => true]);
                } else {
                    return success(['result' => false]);
                }
            }
        } catch (Exception $e) {
            return failed($e);
        }
    }

    public function remove_favorite_category(Request $request)
    {

    }

    public function follow(Request $request)
    {
        try {
            $user_id = JWT::decode($request->header('token'), env('JWT_SECRET'), ['HS256'])->uid;
            $target_id = $request->get('target_id');

            if (is_null($target_id)) {
                return success([
                    'result' => false,
                    'reason' => 'not enough data',
                ]);
            }

            if (Follow::where(['user_id' => $user_id, 'target_id' => $target_id])->exists()) {
                return success(['result' => false, 'reason' => 'already following']);
            } else {
                $data = Follow::create(['user_id' => $user_id, 'target_id' => $target_id]);
                if ($data) {
                    return success(['result' => true]);
                } else {
                    return success(['result' => false]);
                }
            }
        } catch (Exception $e) {
            return failed($e);
        }
    }

    public function unfollow(Request $request)
    {
        try {
            $user_id = JWT::decode($request->header('token'), env('JWT_SECRET'), ['HS256'])->uid;
            $target_id = $request->get('target_id');

            if (is_null($target_id)) {
                return success([
                    'result' => false,
                    'reason' => 'not enough data',
                ]);
            }

            if (Follow::where(['user_id' => $user_id, 'target_id' => $target_id])->doesntExist()) {
                return success(['result' => false, 'reason' => 'not following']);
            } else {
                $data = Follow::where(['user_id' => $user_id, 'target_id' => $target_id])->delete();
                if ($data) {
                    return success(['result' => true]);
                } else {
                    return success(['result' => false]);
                }
            }
        } catch (Exception $e) {
            return failed($e);
        }
    }
}
