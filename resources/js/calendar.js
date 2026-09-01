// Expose the FullCalendar modules needed by Filament calendar pages without
// booting a second Alpine instance inside the Filament application shell.
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { calendarLayout } from './booking-calendar-layout';
import {
    BOOKING_CALENDAR_MODULE_READY_EVENT,
    synchronizeBookingCalendarStartup,
} from './booking-calendar-startup';

window.Calendar = Calendar;
window.dayGridPlugin = dayGridPlugin;
window.interactionPlugin = interactionPlugin;
window.calendarLayout = calendarLayout;

synchronizeBookingCalendarStartup(window);
window.dispatchEvent(new Event(BOOKING_CALENDAR_MODULE_READY_EVENT));
