(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var el = document.getElementById('aca-calendar');
        if(!el || typeof FullCalendar === 'undefined') return;
        var opts = window.acaOptions || {};
        var events = Array.isArray(window.acaEvents) ? window.acaEvents.slice() : [];
        if(Array.isArray(opts.closedEvents)) {
            events = events.concat(opts.closedEvents);
        }
        var calendar = new FullCalendar.Calendar(el, {
            initialView: 'timeGridWeek',
            headerToolbar: false,
            slotMinTime: opts.workStart || '09:00',
            slotMaxTime: opts.workEnd || '20:00',
            slotLabelFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            eventColor: 'orange',
            businessHours: {
                startTime: opts.workStart || '09:00',
                endTime: opts.workEnd || '20:00',
                daysOfWeek: [0,1,2,3,4,5,6]
            },
            events: events
        });
        calendar.render();
        var controls = document.getElementById('aca-calendar-controls');
        if(controls){
            controls.addEventListener('click', function(e){
                var view = e.target.getAttribute('data-view');
                if(!view) return;
                if(view === 'day') calendar.changeView('timeGridDay');
                else if(view === 'week') calendar.changeView('timeGridWeek');
                else if(view === 'month') calendar.changeView('dayGridMonth');
            });
        }
    });
})();
