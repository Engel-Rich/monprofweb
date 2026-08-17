<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Admin\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStatisticsController extends Controller
{
    public function __invoke(Request $request, StatisticsService $statistics): View
    {
        $classId = $request->filled('classe') ? (int) $request->input('classe') : null;

        return view('screen.partner.home', [
            'statistics' => $statistics->build($classId),
        ]);
    }
}
