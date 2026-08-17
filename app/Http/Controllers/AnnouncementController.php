<?php

namespace App\Http\Controllers;

use App\Services\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function getLatestAnnouncement(Request $request, AnnouncementService $announcements): JsonResponse
    {
        return response()->json(
            $announcements->latest(
                $request->string('after_id')->toString() ?: null,
                $request->integer('zone_id') ?: null,
            ),
        );
    }
}
