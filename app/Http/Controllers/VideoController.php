<?php

namespace App\Http\Controllers;

use App\Support\IceServers;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function show(): View
    {
        return view('video', [
            'iceServers' => IceServers::list(),
        ]);
    }
}
