import assert from 'node:assert/strict';
import test from 'node:test';

import { synchronizeFilamentNavigationGroups } from '../../resources/js/filament-navigation-state.js';

class StorageStub {
    values = new Map();

    getItem(key) {
        return this.values.get(key) ?? null;
    }

    setItem(key, value) {
        this.values.set(key, String(value));
    }
}

class WindowStub extends EventTarget {
    localStorage = new StorageStub();
    sidebarStore = null;
    listenerCounts = new Map();

    addEventListener(type, listener, options) {
        this.listenerCounts.set(type, (this.listenerCounts.get(type) ?? 0) + 1);

        super.addEventListener(type, listener, options);
    }

    Alpine = {
        store: () => this.sidebarStore,
    };
}

test('opens active persisted group while preserving inactive collapsed choices', () => {
    const windowObject = new WindowStub();
    windowObject.localStorage.setItem('collapsedGroups', JSON.stringify(['Finance', 'Reports']));
    windowObject.sidebarStore = { collapsedGroups: ['Finance', 'Reports'] };

    synchronizeFilamentNavigationGroups(windowObject, ['Finance']);

    assert.deepEqual(JSON.parse(windowObject.localStorage.getItem('collapsedGroups')), ['Reports']);
    assert.deepEqual(windowObject.sidebarStore.collapsedGroups, ['Reports']);
});

test('reconciles on Livewire navigation with one idempotent listener', () => {
    const windowObject = new WindowStub();
    windowObject.localStorage.setItem('collapsedGroups', JSON.stringify(['Finance', 'Reports']));
    windowObject.sidebarStore = { collapsedGroups: ['Finance', 'Reports'] };

    synchronizeFilamentNavigationGroups(windowObject, ['Finance']);
    windowObject.localStorage.setItem('collapsedGroups', JSON.stringify(['Finance', 'Reports']));
    windowObject.sidebarStore.collapsedGroups = ['Finance', 'Reports'];
    synchronizeFilamentNavigationGroups(windowObject, ['Reports']);
    windowObject.dispatchEvent(new Event('livewire:navigated'));

    assert.equal(windowObject.listenerCounts.get('livewire:navigated'), 1);
    assert.deepEqual(JSON.parse(windowObject.localStorage.getItem('collapsedGroups')), ['Finance']);
    assert.deepEqual(windowObject.sidebarStore.collapsedGroups, ['Finance']);
});
