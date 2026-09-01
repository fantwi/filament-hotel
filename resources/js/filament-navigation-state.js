const COLLAPSED_GROUPS_STORAGE_KEY = 'collapsedGroups';
const CONTROLLER_KEY = '__filamentAdminNavigationGroupsController';

const readCollapsedGroups = (windowObject) => {
    const rawValue = windowObject.localStorage.getItem(COLLAPSED_GROUPS_STORAGE_KEY);

    if (rawValue === null) {
        return { groups: [], valid: true };
    }

    try {
        const value = JSON.parse(rawValue);

        return Array.isArray(value)
            ? { groups: value, valid: true }
            : { groups: [], valid: false };
    } catch {
        return { groups: [], valid: false };
    }
};

export const synchronizeFilamentNavigationGroups = (windowObject, activeGroupLabels) => {
    const controller = windowObject[CONTROLLER_KEY] ??= {
        activeGroupLabels: [],
        reconcile: null,
        navigationListenerRegistered: false,
        alpineListenerRegistered: false,
    };

    controller.activeGroupLabels = [...new Set(activeGroupLabels)];
    controller.reconcile ??= () => {
        const activeLabels = new Set(controller.activeGroupLabels);
        const storedState = readCollapsedGroups(windowObject);
        const collapsedGroups = storedState.groups;
        const reconciledGroups = collapsedGroups.filter((label) => !activeLabels.has(label));

        if (! storedState.valid || JSON.stringify(collapsedGroups) !== JSON.stringify(reconciledGroups)) {
            windowObject.localStorage.setItem(
                COLLAPSED_GROUPS_STORAGE_KEY,
                JSON.stringify(reconciledGroups),
            );
        }

        const sidebarStore = windowObject.Alpine?.store?.('sidebar');

        if (sidebarStore && 'collapsedGroups' in sidebarStore) {
            sidebarStore.collapsedGroups = reconciledGroups;
        }

        return reconciledGroups;
    };

    if (! controller.navigationListenerRegistered) {
        windowObject.addEventListener('livewire:navigated', controller.reconcile);
        controller.navigationListenerRegistered = true;
    }

    const documentObject = windowObject.document;

    if (documentObject && ! controller.alpineListenerRegistered) {
        documentObject.addEventListener('alpine:initialized', controller.reconcile);
        controller.alpineListenerRegistered = true;
    }

    return controller.reconcile();
};

if (typeof window !== 'undefined') {
    window.__filamentAdminNavigationSynchronize = synchronizeFilamentNavigationGroups;

    if (window.__filamentAdminNavigationActiveLabels) {
        synchronizeFilamentNavigationGroups(window, window.__filamentAdminNavigationActiveLabels);
    }
}
