@php
    $navigationStateScript = file_get_contents(resource_path('js/filament-navigation-state.js'));
@endphp

<script>
    (() => {
        window.__filamentAdminNavigationActiveLabels = @js($activeGroupLabels)

        if (window.__filamentAdminNavigationSynchronize) {
            window.__filamentAdminNavigationSynchronize(
                window,
                window.__filamentAdminNavigationActiveLabels,
            )
        }
    })()
</script>

<script type="module">
    {!! $navigationStateScript !!}
</script>
