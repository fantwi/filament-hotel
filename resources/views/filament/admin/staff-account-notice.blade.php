@php
    $user = auth()->user();
    $notice = $user instanceof \App\Models\User
        ? app(\App\Services\StaffAccountAccess::class)->notice($user)
        : null;
@endphp

@if ($notice)
    <div
        class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium {{ $user->isSuspended() ? 'border-danger-300 bg-danger-50 text-danger-800 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-200' : 'border-warning-300 bg-warning-50 text-warning-800 dark:border-warning-500/40 dark:bg-warning-500/10 dark:text-warning-200' }}"
        role="alert"
    >
        {{ $notice }}
    </div>
@endif
