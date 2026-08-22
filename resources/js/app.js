// Load HTTP defaults before any page-level request is issued.
import './bootstrap';

// Register the front-end libraries exposed to Blade and Alpine components.
import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

// Make calendar dependencies available to inline booking and availability views.
window.Calendar = Calendar;
window.dayGridPlugin = dayGridPlugin;
window.interactionPlugin = interactionPlugin;

// Expose the date picker for pages that initialize it from inline scripts.
window.flatpickr = flatpickr;

// Start Alpine after its shared dependencies are available.
window.Alpine = Alpine;
Alpine.start();
