export const BOOKING_CALENDAR_MODULE_READY_EVENT = 'booking-calendar-module-ready';

export const synchronizeBookingCalendarStartup = (windowObject) => {
    const controller = windowObject.__bookingCalendarController ??= {};

    controller.handleCalendarModuleReady ??= () => controller.initialize?.();

    if (! controller.calendarModuleReadyListenerRegistered) {
        windowObject.addEventListener(BOOKING_CALENDAR_MODULE_READY_EVENT, controller.handleCalendarModuleReady);
        controller.calendarModuleReadyListenerRegistered = true;
    }

    controller.handleCalendarModuleReady();
};
