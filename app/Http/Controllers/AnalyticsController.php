<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnalyticsController extends Controller
{
    public function pulse(Request $request): Response
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:190', 'starts_with:/'],
            'duration' => ['required', 'integer', 'min:0', 'max:86400'],
        ]);

        $key = (string) $request->cookie('koko_vid', '');
        if (! preg_match('/^[A-Za-z0-9]{32}$/', $key)) {
            return response()->noContent();
        }

        try {
            $visit = Visit::query()->where('key', $key)->first();
            $view = $visit
                ? PageView::query()->where('visit_id', $visit->id)->where('path', $data['path'])->latest('id')->first()
                : null;
            if ($view && $data['duration'] > $view->duration) {
                $view->forceFill(['duration' => $data['duration']])->save();
            }
        } catch (\Throwable) {
            return response()->noContent();
        }

        return response()->noContent();
    }
}
