<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardStatisticsService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(DashboardStatisticsService $statistics): View
    {
        return view('index', [
            'dashboard' => $statistics->build(),
        ]);
    }
}
