(function(){
    document.addEventListener('DOMContentLoaded', function(){
        var el = document.getElementById('aca-calendar');
        if(!el || typeof FullCalendar === 'undefined') return;
        var opts = window.acaOptions || {};
        var services = Array.isArray(opts.services) ? opts.services : [];
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
            allDaySlot: false,
            businessHours: {
                startTime: opts.workStart || '09:00',
                endTime: opts.workEnd || '20:00',
                daysOfWeek: [0,1,2,3,4,5,6]
            },
            selectable: true,
            selectOverlap: false,
            selectAllow: function(info){
                return info.view.type !== 'dayGridMonth';
            },
            events: events,
            select: function(info){
                if(info.view.type === 'dayGridMonth') return;
                showReservationForm(info.startStr);
            }
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
        function showReservationForm(start){
            var modal = document.createElement('div');
            modal.id = 'aca-reserve-modal';
            var html = '<div class="aca-modal-content"><h3>Select Services</h3>';
            services.forEach(function(s,i){
                html += '<label><input type="checkbox" value="'+s.name+'"> '+s.name+' ('+s.price+' '+(s.duration?'/ '+s.duration+'min':'')+')</label><br>';
            });
            html += '<label>Name:<br><input type="text" id="aca-name"></label><br>';
            html += '<label>Phone:<br><input type="text" id="aca-phone"></label><br>';
            html += '<button type="button" id="aca-reserve-cancel">Cancel</button>';
            html += '<button type="button" id="aca-reserve-save">Reserve</button>';
            html += '</div>';
            modal.innerHTML = html;
            document.body.appendChild(modal);
            modal.querySelector('#aca-reserve-cancel').addEventListener('click', function(){ modal.remove(); });
            modal.querySelector('#aca-reserve-save').addEventListener('click', function(){
                var selected = [];
                modal.querySelectorAll('input[type=checkbox]:checked').forEach(function(c){ selected.push(c.value); });
                var name = modal.querySelector('#aca-name').value.trim();
                var phone = modal.querySelector('#aca-phone').value.trim();
                if(!selected.length || !name || !phone){ return; }
                var minutes = 0;
                selected.forEach(function(v){
                    var s = services.find(function(o){ return o.name === v; });
                    if(s && parseInt(s.duration)) minutes += parseInt(s.duration);
                });
                if(!minutes) return;
                var end = new Date(new Date(start).getTime() + minutes*60000).toISOString();
                var data = new FormData();
                data.append('action','aca_save_reservation');
                data.append('start', start);
                data.append('name', name);
                data.append('phone', phone);
                selected.forEach(function(v){ data.append('services[]', v); });
                fetch(opts.ajaxUrl, {method:'POST', body:data})
                    .then(function(r){ return r.json(); })
                    .then(function(res){
                        var eend = end;
                        if(res && res.success && res.data && res.data.end){ eend = res.data.end; }
                        calendar.addEvent({title:'Reserved', start:start, end:eend, color:'gray'});
                        modal.remove();
                    })
                    .catch(function(){ modal.remove(); });
            });
        }
    });
})();
