<div class="flex h-full min-w-0 max-w-60 items-center gap-2.5">
    @if ($logoUrl)
        <img
            src="{{ $logoUrl }}"
            alt="{{ $hotelName }} logo"
            class="h-full w-auto shrink-0 rounded-md object-contain"
        >
    @endif

    <span class="truncate text-sm font-semibold leading-tight text-gray-950 dark:text-white sm:text-base">
        {{ $hotelName }}
    </span>
</div>
