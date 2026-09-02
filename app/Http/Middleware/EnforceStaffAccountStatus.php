<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\StaffAccountAccess;
use Closure;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;

final class EnforceStaffAccountStatus
{
    public function __construct(private readonly StaffAccountAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isStaff() || ! $this->isAdminRequest($request)) {
            return $next($request);
        }

        if ($this->access->allows($user, $request->route()?->getName())) {
            return $next($request);
        }

        if (Livewire::isLivewireRequest() || ! $request->isMethodSafe()) {
            abort(403, $this->access->notice($user));
        }

        return redirect($this->access->destination($user))
            ->with('staff_account_notice', $this->access->notice($user));
    }

    private function isAdminRequest(Request $request): bool
    {
        return $request->is('admin', 'admin/*')
            || filament()->getCurrentPanel()?->getId() === 'admin';
    }
}
