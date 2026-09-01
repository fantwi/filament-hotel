@php
    $navigationStateScript = file_get_contents(resource_path('js/filament-navigation-state.js'));
@endphp

<script>
    (() => {
        const activeLabels = @js($activeGroupLabels)
        const activeLabelSet = new Set(activeLabels)
        const storageKey = 'collapsedGroups'
        let collapsedGroups = []
        let storedStateIsValid = true

        const storedValue = localStorage.getItem(storageKey)

        if (storedValue !== null) {
            try {
                const storedGroups = JSON.parse(storedValue)

                if (Array.isArray(storedGroups)) {
                    collapsedGroups = storedGroups
                } else {
                    storedStateIsValid = false
                }
            } catch {
                storedStateIsValid = false
            }
        }

        const reconciledGroups = collapsedGroups.filter((label) => ! activeLabelSet.has(label))

        if (! storedStateIsValid || JSON.stringify(collapsedGroups) !== JSON.stringify(reconciledGroups)) {
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
