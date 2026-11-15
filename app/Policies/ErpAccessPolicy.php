<?php

namespace App\Policies;

use App\Models\User;

class ErpAccessPolicy
{
    public function viewDashboard(User $user): bool
    {
        return $user->can('dashboard.view');
    }

    public function viewModule(User $user, string $module): bool
    {
        $modules = config('erp.modules', []);

        if (! isset($modules[$module])) {
            return false;
        }

        if ($module === 'dashboard') {
            return $user->can('dashboard.view');
        }

        return $user->can("{$module}.view");
    }

    public function manageInventory(User $user): bool
    {
        return $user->can('inventory.manage');
    }

    public function manageDanaProduction(User $user): bool
    {
        return $user->can('dana-production.manage');
    }

    public function manageBagProduction(User $user): bool
    {
        return $user->can('shopping-bag-production.manage');
    }

    public function manageEmployees(User $user): bool
    {
        return $user->can('employees.manage');
    }

    public function manageAttendance(User $user): bool
    {
        return $user->can('attendance.manage');
    }

    public function approveOvertime(User $user): bool
    {
        return $user->can('payroll.approve')
            || $user->can('employees.manage')
            || $user->can('attendance.manage');
    }

    public function approvePayroll(User $user): bool
    {
        return $user->can('payroll.approve');
    }

    public function approveFinancialStatements(User $user): bool
    {
        return $user->can('accounting.approve-financial-statements');
    }
}