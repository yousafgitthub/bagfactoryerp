<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'attendance');

        return Inertia::render('Hr/Attendance/Hub', [
            'pageTitle' => 'Attendance',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Attendance'],
            ],
            'links' => [
                [
                    'label' => 'Attendance register',
                    'description' => 'Browse and edit individual attendance rows.',
                    'href' => route('hr.attendance.index'),
                ],
                [
                    'label' => 'Daily entry',
                    'description' => 'Mark attendance for all active employees for one date.',
                    'href' => route('hr.attendance.daily'),
                ],
                [
                    'label' => 'Monthly report',
                    'description' => 'Summary counts by employee for a calendar month.',
                    'href' => route('hr.attendance.monthly'),
                ],
                [
                    'label' => 'Overtime register',
                    'description' => 'Log and approve overtime hours.',
                    'href' => route('hr.overtime.index'),
                ],
            ],
            'canManage' => $request->user()?->can('attendance.manage') === true,
        ]);
    }
}