<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MissionLikeController extends Controller
{
    public function index(Request $request, $id): array
    {
        return (new LikeController())->index($request, 'mission', $id);
    }

    public function store($id): array
    {
        return (new LikeController())->store('mission', $id);
    }

    public function destroy($id): array
    {
        return (new LikeController())->destroy('mission', $id);
    }
}
