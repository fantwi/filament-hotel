import assert from 'node:assert/strict';
import test from 'node:test';

import {
    BOOKING_CALENDAR_MODULE_READY_EVENT,
    synchronizeBookingCalendarStartup,
} from '../../resources/js/booking-calendar-startup.js';

class WindowStub extends EventTarget {
    listenerCounts = new Map();

    addEventListener(type, listener, options) {
        this.listenerCounts.set(type, (this.listenerCounts.get(type) ?? 0) + 1);

        super.addEventListener(type, listener, options);
    }
}

test('creates one calendar when the first Livewire navigation precedes module readiness', () => {
    const windowObject = new WindowStub();
    let moduleReady = false;
    let creations = 0;

    windowObject.__bookingCalendarController = {
        initialize: () => {
            if (! moduleReady || creations) {
                return;
            }

            creations++;
        },
    };

    windowObject.__bookingCalendarController.initialize();
    synchronizeBookingCalendarStartup(windowObject);
    synchronizeBookingCalendarStartup(windowObject);

    assert.equal(creations, 0);
    assert.equal(windowObject.listenerCounts.get(BOOKING_CALENDAR_MODULE_READY_EVENT), 1);

    moduleReady = true;
    windowObject.dispatchEvent(new Event(BOOKING_CALENDAR_MODULE_READY_EVENT));
    windowObject.dispatchEvent(new Event(BOOKING_CALENDAR_MODULE_READY_EVENT));

    assert.equal(creations, 1);
});

test('initializes immediately when the calendar module is already available', () => {
    const windowObject = new WindowStub();
    let creations = 0;

    windowObject.__bookingCalendarController = {
        initialize: () => {
            creations++;
        },
    };

    synchronizeBookingCalendarStartup(windowObject);

    assert.equal(creations, 1);
});
