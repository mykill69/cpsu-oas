<?php

namespace App\Providers;

use App\Models\College;
use App\Models\SurveyVisit;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot()
    {
        $this->registerPolicies();

        // QA sees every college's binder; an in-charge sees only visits assigned to their own college.
        Gate::define('view-binder', function (User $user, College $college, SurveyVisit $visit) {
            if ($user->isQa()) {
                return true;
            }

            return $user->isInCharge()
                && (int) $user->college_id === $college->id
                && $visit->colleges()->whereKey($college->id)->exists();
        });

        // Only the college's own in-charge writes to its binder, only while the visit is open,
        // and only in the current school year (past years are a read-only record).
        Gate::define('edit-binder', function (User $user, College $college, SurveyVisit $visit) {
            return $user->isInCharge()
                && (int) $user->college_id === $college->id
                && $visit->is_active
                && (bool) $visit->schoolYear?->is_current
                && $visit->colleges()->whereKey($college->id)->exists();
        });
    }
}
