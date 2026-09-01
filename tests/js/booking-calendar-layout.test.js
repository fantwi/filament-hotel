import assert from 'node:assert/strict';
import test from 'node:test';

import { calendarLayout } from '../../resources/js/booking-calendar-layout.js';

test('uses a focused day view and compact toolbar on a narrow viewport', () => {
    assert.equal(calendarLayout(390).initialView, 'dayGridDay');
    assert.deepEqual(calendarLayout(390).headerToolbar, {
        left: 'prev,next',
        center: 'title',
        right: 'today',
    });
});

test('uses month and week controls on a desktop viewport', () => {
    assert.equal(calendarLayout(1024).initialView, 'dayGridMonth');
    assert.equal(calendarLayout(1024).headerToolbar.right, 'dayGridMonth,dayGridWeek');
});
