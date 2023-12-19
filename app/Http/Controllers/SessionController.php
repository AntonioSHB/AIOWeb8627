<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SessionService;

class SessionController extends Controller
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    public function updateSessionTime(Request $request)
    {
        $this->sessionService->updateLastActivity($request);

        return response()->json(['message' => 'Session time updated.']);
    }
}
