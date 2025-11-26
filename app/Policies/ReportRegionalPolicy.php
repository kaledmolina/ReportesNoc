<?php

namespace App\Policies;

use App\Models\ReportRegional;
use App\Models\User;

class ReportRegionalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_report_regional');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReportRegional $report): bool
    {
        return $user->can('view_report_regional');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_report_regional');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReportRegional $report): bool
    {
        return $user->can('update_report_regional');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReportRegional $report): bool
    {
        return $user->can('delete_report_regional');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReportRegional $report): bool
    {
        return $user->can('delete_report_regional');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReportRegional $report): bool
    {
        return $user->can('delete_report_regional');
    }
}
