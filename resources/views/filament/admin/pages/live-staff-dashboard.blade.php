<x-filament::page wire:poll.10s>

<h2 class="text-xl font-bold mb-4">Live Staff Status</h2>

<div class="overflow-x-auto">

<table class="w-full bg-white rounded shadow">

<thead class="bg-gray-100 text-left">
<tr>
    <th class="p-3">Name</th>
    <th class="p-3">Role</th>
    <th class="p-3">Department</th>
    <th class="p-3">Status</th>
    <th class="p-3">Last Seen</th>
</tr>
</thead>

<tbody>

@foreach ($this->staff as $user)

<tr class="border-t">

    <td class="p-3">
        {{ $user->guest->first_name ?? $user->email }}
    </td>

    <td class="p-3">
        {{ $user->roles->pluck('name')->first() }}
    </td>

    <td class="p-3">
        {{ $user->department ?? '—' }}
    </td>

    <td class="p-3">
        @if ($user->isOnline())
            <span class="text-green-600 font-semibold">● Online</span>
        @else
            <span class="text-gray-400">● Offline</span>
        @endif
    </td>

    <td class="p-3 text-sm text-gray-500">
        {{ $user->last_seen_at?->diffForHumans() ?? 'Never' }}
    </td>

</tr>

@endforeach

<th class="p-3">Activity</th>
<td class="p-3">
    {{ $user->activities->last()->action ?? 'No activity' }}
</td>

</tbody>

</table>

</div>

</x-filament::page>
