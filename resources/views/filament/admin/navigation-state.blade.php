@php
    $navigationStateScript = file_get_contents(resource_path('js/filament-navigation-state.js'));
@endphp

<script>
    (() => {
        const activeLabels = @js($activeGroupLabels)
        const activeLabelSet = new Set(activeLabels)
        const storageKey = 'collapsedGroups'
        let collapsedGroups = []

        try {
            const storedGroups = JSON.parse(localStorage.getItem(storageKey) ?? '[]')

            collapsedGroups = Array.isArray(storedGroups) ? storedGroups : []
        } catch {
            collapsedGroups = []
        }

        const reconciledGroups = collapsedGroups.filter((label) => ! activeLabelSet.has(label))

        if (JSON.stringify(collapsedGroups) !== JSON.stringify(reconciledGroups)) {
            localStorage.setItem(storageKey, JSON.stringify(reconciledGroups))
        }

        window.__filamentAdminNavigationActiveLabels = activeLabels

        if (window.__filamentAdminNavigationSynchronize) {
            window.__filamentAdminNavigationSynchronize(
                window,
                activeLabels,
            )
        }
    })()
</script>

<script type="module">
    {!! $navigationStateScript !!}
</script>
