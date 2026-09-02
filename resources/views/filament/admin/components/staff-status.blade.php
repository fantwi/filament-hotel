@php
    use App\Enums\StaffAccountStatus;

    $user = $user ?? $record;
    $status = $user->status;
    $online = $status === StaffAccountStatus::Active && $user->isOnline();
@endphp

<span class="inline-flex items-center gap-2 text-sm font-medium">
    @if ($status === StaffAccountStatus::Active)
        <span class="relative flex h-2.5 w-2.5" aria-hidden="true">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $online ? 'bg-success-400' : 'bg-danger-400' }} opacity-75"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $online ? 'bg-success-500' : 'bg-danger-500' }}"></span>
        </span>
        <span>Active</span>
        <span class="sr-only">{{ $online ? 'Online' : 'Offline' }}</span>
    @else
        <x-filament::badge :color="$status->color()">{{ $status->label() }}</x-filament::badge>
    @endif
</span>
