<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Cek status API.
     *
     * @group Sistem
     * @unauthenticated
     *
     * @response 200 {"status": "ok"}
     */
    public function __invoke(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }
}
