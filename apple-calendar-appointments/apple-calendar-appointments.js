(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var el = document.getElementById('aca-calendar');
        if(!el || typeof FullCalendar === 'undefined') return;
        var calendar = new FullCalendar.Calendar(el, {
            initialView: 'timeGridWeek',
            headerToolbar: false,
            events: Array.isArray(window.acaEvents) ? window.acaEvents : []
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
