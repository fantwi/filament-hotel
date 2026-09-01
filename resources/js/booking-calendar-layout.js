export const calendarLayout = (width) => width < 640
    ? {
        initialView: 'dayGridDay',
        headerToolbar: { left: 'prev,next', center: 'title', right: 'today' },
    }
    : {
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,dayGridWeek' },
    };
