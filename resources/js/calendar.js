// Expose the FullCalendar modules needed by Filament calendar pages without
// booting a second Alpine instance inside the Filament application shell.
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { calendarLayout } from './booking-calendar-layout';

window.Calendar = Calendar;
window.dayGridPlugin = dayGridPlugin;
window.interactionPlugin = interactionPlugin;
window.calendarLayout = calendarLayout;
