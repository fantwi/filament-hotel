import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

window.Calendar = Calendar;
window.dayGridPlugin = dayGridPlugin;
window.interactionPlugin = interactionPlugin;

window.flatpickr = flatpickr;

window.Alpine = Alpine;

Alpine.start();
