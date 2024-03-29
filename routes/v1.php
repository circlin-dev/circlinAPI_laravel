<?php

use App\Http\Controllers\v1;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return ['success' => true];
})->name('index');

/* 인증 */
Route::group(['prefix' => 'auth', 'as' => 'auth.'], function () {
    /* 중복 체크 */
    Route::group(['prefix' => 'exists', 'as' => 'exists.'], function () {
        Route::get('/email/{email}', [v1\AuthController::class, 'exists_email'])->name('email');
        Route::get('/nickname/{nickname}', [v1\AuthController::class, 'exists_nickname'])->name('nickname');
    });

    /* 회원가입 */
    Route::post('/signup', [v1\AuthController::class, 'signup'])->name('signup');
    Route::post('/signup/sns', [v1\AuthController::class, 'signup_sns'])->name('signup.sns');

    /* 로그인 */
    Route::post('/login', [v1\AuthController::class, 'login'])->name('login');
    Route::post('/login/sns', [v1\AuthController::class, 'login_sns'])->name('login.sns');

    /* 초기데이터 구성 */
    Route::get('/check/init', [v1\AuthController::class, 'check_init'])->name('check.init');
});

/* 유저 관련 */
Route::group(['prefix' => 'user', 'as' => 'user.'], function () {
    Route::get('/', [v1\UserController::class, 'index'])->name('index');
    Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
        Route::patch('/', [v1\UserController::class, 'update'])->name('update');
        Route::post('/recommend', [v1\UserController::class, 'push_recommend'])->name('push_recommend');
        Route::post('/image', [v1\UserController::class, 'change_profile_image'])->name('update.image');
        Route::delete('/image', [v1\UserController::class, 'remove_profile_image'])->name('delete.image');
        Route::post('/token', [v1\UserController::class, 'update_token'])->name('update.token');
        Route::post('/change_password', [v1\UserController::class, 'change_password'])->name('change_password');
        Route::post('/find_password', [v1\UserController::class, 'find_password'])->name('find_password');
        Route::post('/withdraw', [v1\UserController::class, 'withdraw'])->name('withdraw');
    });

    Route::resource('favorite_category', v1\UserFavoriteCategoryController::class);
    Route::post('/follow', [v1\UserController::class, 'follow'])->name('follow.create');
    Route::delete('/follow/{id}', [v1\UserController::class, 'unfollow'])->name('follow.destroy');

    /* 유저 상세 페이지 */
    Route::group(['prefix' => '{user_id}'], function () {
        Route::get('/', [v1\UserController::class, 'show'])->name('show');
        Route::get('/feed', [v1\UserController::class, 'feed'])->name('feed');
        Route::get('/check', [v1\UserController::class, 'check'])->name('check');
        Route::get('/mission', [v1\UserController::class, 'mission'])->name('mission');
        Route::get('/mission/created', [v1\UserController::class, 'created_mission'])->name('mission.created');
        Route::get('/follower', [v1\UserController::class, 'follower'])->name('follower');
        Route::get('/following', [v1\UserController::class, 'following'])->name('following');

        Route::get('/wallpaper', [v1\UserController::class, 'wallpaper'])->name('wallpaper');
    });
});
Route::get('/area', [v1\BaseController::class, 'area'])->name('area');
Route::get('/suggest_user', [v1\BaseController::class, 'suggest_user'])->name('suggest.user');
Route::get('/place', [v1\BaseController::class, 'place'])->name('place');

/* 알림 관련 */
Route::get('/notification', [v1\NotificationController::class, 'index'])->name('notification.index');

/* 미션 관련 */
Route::get('/bookmark', [v1\BookmarkController::class, 'index'])->name('bookmark.index');
Route::post('/bookmark', [v1\BookmarkController::class, 'store'])->name('bookmark.store');
Route::delete('/bookmark/{mission_id}', [v1\BookmarkController::class, 'destroy'])->name('bookmark.destroy');
Route::group(['prefix' => 'category', 'as' => 'category.'], function () {
    Route::get('/{town?}', [v1\MissionCategoryController::class, 'index'])->where(['town' => 'town'])->name('index');
    Route::get('/{category_id}', [v1\MissionCategoryController::class, 'show'])->name('show');
    Route::get('/{category_id}/mission', [v1\MissionCategoryController::class, 'mission'])->name('mission');
    Route::get('/{category_id}/user', [v1\MissionCategoryController::class, 'user'])->name('user');
});
Route::post('/event_mission_info', [v1\MissionController::class, 'event_mission_info']);
Route::post('/mission_info', [v1\MissionController::class, 'mission_info']);
Route::post('/start_event_mission', [v1\MissionController::class, 'start_event_mission']);
Route::post('/participant_list', [v1\MissionController::class, 'participant_list']);
Route::post('/certification_image', [v1\MissionController::class, 'certification_image']);
Route::post('/doublezone_feed_list', [v1\MissionController::class, 'doublezone_feed_list']);

Route::group(['prefix' => 'mission', 'as' => 'mission.'], function () {
    Route::post('/', [v1\MissionController::class, 'store'])->name('store');
    Route::group(['prefix' => '{mission_id}'], function () {
        Route::get('/', [v1\MissionController::class, 'show'])->name('show');
        Route::get('/feed', [v1\MissionController::class, 'feed'])->name('feed');
        Route::get('/edit', [v1\MissionController::class, 'edit'])->name('edit');
        Route::patch('/', [v1\MissionController::class, 'update'])->name('update');
        Route::delete('/', [v1\MissionController::class, 'destroy'])->name('destroy');
        Route::get('/user', [v1\MissionController::class, 'user'])->name('user');
        Route::post('/invite', [v1\MissionController::class, 'invite'])->name('invite');
        Route::get('/like', [v1\MissionLikeController::class, 'index'])->name('like.index');
        Route::post('/like', [v1\MissionLikeController::class, 'store'])->name('like.store');
        Route::delete('/like', [v1\MissionLikeController::class, 'destroy'])->name('like.destroy');
        Route::get('/comment', [v1\MissionCommentController::class, 'index'])->name('comment.index');
        Route::post('/comment', [v1\MissionCommentController::class, 'store'])->name('comment.store');
        Route::delete('/comment/{comment_id}', [v1\MissionCommentController::class, 'destroy'])->name('comment.destroy');
    });
});

/* Home */
Route::get('/town', [v1\HomeController::class, 'town'])->name('home.town');
Route::get('/newsfeed', [v1\HomeController::class, 'newsfeed'])->name('home.newsfeed');
Route::get('/badge', [v1\HomeController::class, 'badge'])->name('home.badge');

Route::get('/banner/local', [v1\BannerController::class, 'category_banner'])->name('banner');
Route::get('/banner/{type}', [v1\BannerController::class, 'index'])->name('banner');

Route::group(['prefix' => 'popular', 'as' => 'popular.'], function () {
    Route::get('/place', [v1\PopularPlaceController::class, 'index'])->name('index');
    Route::get('/place/{id}', [v1\PopularPlaceController::class, 'show'])->name('show');
    Route::get('/product', [v1\PopularProductController::class, 'index'])->name('index');
    Route::get('/product/{type}/{id}', [v1\PopularProductController::class, 'show'])
        ->where(['type' => '(in|out)side'])->name('show');
});

Route::get('/keyword/{type}', [v1\KeywordController::class, 'index'])
    ->where(['type' => '(place|product)'])->name('place');

Route::group(['prefix' => 'feed', 'feed.'], function () {
    Route::post('/', [v1\FeedController::class, 'store'])->name('store');
    Route::group(['prefix' => '{feed_id}'], function () {
        Route::get('/', [v1\FeedController::class, 'show'])->name('show');
        Route::get('/edit', [v1\FeedController::class, 'edit'])->name('edit');
        Route::patch('/', [v1\FeedController::class, 'update'])->name('update');
        Route::post('/show', [v1\FeedController::class, 'show_feed'])->name('show');
        Route::post('/hide', [v1\FeedController::class, 'hide_feed'])->name('hide');
        Route::delete('/', [v1\FeedController::class, 'destroy'])->name('destroy');
        Route::get('/like', [v1\FeedLikeController::class, 'index'])->name('like.index');
        Route::post('/like', [v1\FeedLikeController::class, 'store'])->name('like.store');
        Route::delete('/like', [v1\FeedLikeController::class, 'destroy'])->name('like.destroy');
        Route::get('/comment', [v1\FeedCommentController::class, 'index'])->name('comment.index');
        Route::post('/comment', [v1\FeedCommentController::class, 'store'])->name('comment.store');
        Route::delete('/comment/{comment_id}', [v1\FeedCommentController::class, 'destroy'])->name('comment.destroy');
    });
});

/* 마이페이지 (UserController 로 넘김) */
Route::group(['prefix' => 'mypage', 'as' => 'mypage.'], function () {
    Route::get('/', [v1\MypageController::class, 'index'])->name('index');
    Route::get('/feed', [v1\MypageController::class, 'feed'])->name('feed');
    Route::get('/check', [v1\MypageController::class, 'check'])->name('check');
    Route::get('/mission', [v1\MypageController::class, 'mission'])->name('mission');
    Route::get('/mission/created', [v1\MypageController::class, 'created_mission'])->name('mission.created');
    Route::get('/follower', [v1\MypageController::class, 'follower'])->name('follower');
    Route::get('/following', [v1\MypageController::class, 'following'])->name('following');

    Route::get('/gallery', [v1\MypageController::class, 'gallery'])->name('gallery');
    Route::get('/wallpaper', [v1\MypageController::class, 'wallpaper'])->name('wallpaper');
});

/* 탐색 페이지 */
Route::get('/explore', [v1\SearchController::class, 'index'])->name('explore');
Route::get('/explore/search', [v1\SearchController::class, 'search'])->name('explore.search');
Route::get('/explore/search/simple', [v1\SearchController::class, 'simple'])->name('explore.search.simple');
Route::get('/explore/search/invite_code', [v1\SearchController::class, 'invite_code'])->name('explore.search.invite_code');
Route::get('/explore/search/user', [v1\SearchController::class, 'user'])->name('explore.search.user');
Route::get('/explore/search/mission', [v1\SearchController::class, 'mission'])->name('explore.search.mission');
Route::get('/explore/search/product', [v1\SearchController::class, 'product'])->name('explore.search.product');

/* 채팅 관련 */
Route::group(['prefix' => 'chat', 'as' => 'chat.'], function () {
    Route::get('/', [v1\ChatController::class, 'index'])->name('index');
    Route::group(['prefix' => '{room_id}'], function () {
        Route::get('/', [v1\ChatController::class, 'show'])->name('show');
        // Route::post('/send', [v1\ChatController::class, 'send_message'])->name('send');
        Route::get('/user', [v1\ChatController::class, 'user'])->name('user');
        Route::post('/leave', [v1\ChatController::class, 'leave_room'])->name('leave');
        Route::post('/unblock', [v1\ChatController::class, 'show_room'])->name('show');
        Route::post('/block', [v1\ChatController::class, 'hide_room'])->name('hide');
        Route::delete('/{id}', [v1\ChatController::class, 'destroy'])->name('message.destroy');
    });
    Route::post('/direct/enter/{target_id}', [v1\ChatController::class, 'enter_direct'])->name('direct.enter');
    Route::post('/direct/send/multiple', [v1\ChatController::class, 'send_direct_multiple'])->name('direct.send.multiple');
    Route::post('/direct/send/{target_id}', [v1\ChatController::class, 'send_direct'])->name('direct.send');
});

/* 공지 */
Route::group(['prefix' => 'notice', 'as' => 'notice.'], function () {
    Route::get('/', [v1\NoticeController::class, 'index'])->name('index');
    Route::group(['prefix' => '{notice_id}'], function () {
        Route::get('/', [v1\NoticeController::class, 'show'])->name('show');
        Route::get('/comment', [v1\NoticeCommentController::class, 'index'])->name('comment.index');
        Route::post('/comment', [v1\NoticeCommentController::class, 'store'])->name('comment.store');
        Route::delete('/comment/{comment_id}', [v1\NoticeCommentController::class, 'destroy'])->name('comment.destroy');
    });
});

/* 샵 관련 */
Route::get('/shop_banner', [v1\ShopController::class, 'shop_banner']);
Route::get('/shop_category', [v1\ShopController::class, 'shop_category']);
Route::post('/item_list', [v1\ShopController::class, 'item_list']);
Route::post('/shop/product_detail', [v1\ShopController::class, 'product_detail']);
Route::get('/shop/point', [v1\ShopController::class, 'shop_point_list']);
Route::get('/shop/bought', [v1\ShopController::class, 'bought_product_list']);
Route::get('/shop/cart_list', [v1\ShopController::class, 'cart_list']);
Route::post('/shop/order', [v1\ShopController::class, 'order_product']);
Route::post('/shop/cart', [v1\ShopController::class, 'cart']);
Route::post('/shop/update_cart', [v1\ShopController::class, 'update_cart']);

Route::get('/latest_version', [v1\BaseController::class, 'latest_version']);

Route::get('/show_construction', function () {
    return success([
        'result' => true,
        'show' => false,
    ]);
});

Route::get('/feed_upload_point', function () {
    return success([
        'result' => true,
        'show' => true,
    ]);
});

Route::get('/mission_upload_point', function () {
    return success([
        'result' => true,
        'show' => true,
    ]);
});
