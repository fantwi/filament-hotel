<?php

namespace App\Observers;

use App\Models\MenuItem;

/**
 * Maintains the append-only low-stock threshold history for menu items.
 */
class MenuItemObserver
{
    /**
     * Records the threshold that applies from the menu item's creation time.
     */
    public function created(MenuItem $menuItem): void
    {
        $this->recordThreshold($menuItem, $menuItem->created_at ?? now());
    }

    /**
     * Records only actual threshold changes, leaving unrelated edits out of history.
     */
    public function updated(MenuItem $menuItem): void
    {
        if (! $menuItem->wasChanged('low_stock_threshold')) {
            return;
        }

        $this->recordThreshold($menuItem, $menuItem->updated_at ?? now());
    }

    /**
     * Persists a threshold snapshot without modifying the current menu-item value.
     */
    private function recordThreshold(MenuItem $menuItem, mixed $effectiveFrom): void
    {
        $menuItem->stockThresholdHistory()->create([
            'threshold' => $menuItem->low_stock_threshold ?? 0,
            'effective_from' => $effectiveFrom,
        ]);
    }
}
