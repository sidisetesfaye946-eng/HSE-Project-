<?php

namespace App\Http\Controllers;

use App\AdminDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AdminDashboardService $adminDashboard,
        SiteOfficerDashboardController $siteOfficerDashboard,
        HseOfficerDashboardController $hseOfficerDashboard,
    ): View {
        if ($request->user()->hasRole('Admin')) {
            return view('admin.dashboard', [
                'stats' => $adminDashboard->stats(),
            ]);
        }

        if ($request->user()->hasRole('HSE Site Officer')) {
            return $siteOfficerDashboard->index($request);
        }

        if ($request->user()->hasRole('HSE Officer')) {
            return $hseOfficerDashboard->index($request);
        }

        return view('dashboard');
    }
}
