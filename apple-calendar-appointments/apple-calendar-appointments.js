(function(){
    function init(){
        var el = document.getElementById('aca-calendar');
        if(!el || typeof FullCalendar === 'undefined') return;
        var opts = window.acaOptions || {};
        var services = Array.isArray(opts.services) ? opts.services : [];
        var events = Array.isArray(window.acaEvents) ? window.acaEvents.slice() : [];
        if(Array.isArray(opts.closedEvents)) {
            events = events.concat(opts.closedEvents);
        }

        function isClosed(date){
            if(!Array.isArray(opts.closedEvents)) return false;
            for(var i=0;i<opts.closedEvents.length;i++){
                var ev = opts.closedEvents[i];
                if(Array.isArray(ev.daysOfWeek) && ev.daysOfWeek.indexOf(date.getUTCDay()) !== -1 && !ev.start && !ev.end){
                    return true;
                }
                if(ev.start && ev.end){
                    var st = new Date(ev.start);
                    var en = new Date(ev.end);
                    if(date >= st && date < en){
                        return true;
                    }
                }
            }
            return false;
        }
        function updateTitle(){
            var titleEl = document.getElementById('aca-calendar-title');
            if(!titleEl) return;
            var view = calendar.view;
            if(view.type === 'timeGridDay'){
                titleEl.textContent = view.title;
            }else if(view.type === 'timeGridWeek' || view.type === 'dayGridMonth'){
                var d = view.currentStart;
                titleEl.textContent = d.toLocaleDateString(undefined, {month:'long', year:'numeric'});
            }else{
                titleEl.textContent = '';
            }
        }

        var calendar = new FullCalendar.Calendar(el, {
            initialView: 'timeGridWeek',
            headerToolbar: false,
            firstDay: 1,
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
            selectable: !!opts.reservationsEnabled,
            selectOverlap: false,
            selectAllow: function(info){
                if(!opts.reservationsEnabled) return false;
                if(info.view.type === 'dayGridMonth') return false;
                var now = new Date();
                if(info.start < now) return false;
                if(isClosed(info.start) || isClosed(info.end)) return false;
                return true;
            },
            events: events,
            select: function(info){
                if(!opts.reservationsEnabled) return;
                if(info.view.type === 'dayGridMonth') return;
                showReservationForm(info.startStr, info.endStr);
            },
            dateClick: function(info){
                if(info.view.type === 'dayGridMonth'){
                    calendar.changeView('timeGridDay', info.dateStr);
                } else if(opts.reservationsEnabled && !isClosed(info.date)){
                    var end = new Date(info.date);
                    end.setMinutes(end.getMinutes() + 30);
                    showReservationForm(info.dateStr, end.toISOString());
                }
            },
            datesSet: updateTitle
        });
        calendar.render();
        updateTitle();
        var controls = document.getElementById('aca-calendar-controls');
        if(controls){
            controls.addEventListener('click', function(e){
                var nav = e.target.getAttribute('data-nav');
                if(nav === 'prev'){ calendar.prev(); return; }
                if(nav === 'next'){ calendar.next(); return; }
                if(nav === 'today'){
                    calendar.today();
                    if(calendar.view.type === 'dayGridMonth' || calendar.view.type === 'timeGridWeek' || calendar.view.type === 'timeGridDay'){
                        blinkCurrentDay();
                    }
                    return;
                }
                var view = e.target.getAttribute('data-view');
                if(!view) return;
                if(view === 'day') calendar.changeView('timeGridDay');
                else if(view === 'week') calendar.changeView('timeGridWeek');
                else if(view === 'month') calendar.changeView('dayGridMonth');
            });
        }

        function blinkCurrentDay(){
            var iso = new Date().toISOString().split('T')[0];
            var nodes = [];
            if(calendar.view.type === 'dayGridMonth'){
                nodes = el.querySelectorAll('.fc-daygrid-day[data-date="'+iso+'"]');
            }else if(calendar.view.type === 'timeGridWeek' || calendar.view.type === 'timeGridDay'){
                nodes = el.querySelectorAll('.fc-timegrid-col[data-date="'+iso+'"], .fc-col-header-cell[data-date="'+iso+'"]');
            }
            nodes.forEach(function(n){
                n.classList.add('aca-blink');
                n.addEventListener('animationend', function rm(){ n.classList.remove('aca-blink'); n.removeEventListener('animationend', rm); }, {once:true});
            });
        }

        function showReservationForm(start, end){
            var startDate = new Date(start);
            var modal = document.createElement('div');
            modal.id = 'aca-reserve-modal';
            function formatRange(min){
                var endDate = new Date(startDate.getTime() + min*60000);
                var datePart = startDate.toLocaleDateString(undefined,{month:'long', day:'numeric', year:'numeric'});
                var st = startDate.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit',hour12:false});
                var en = endDate.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit',hour12:false});
                return datePart+' | '+st+' - '+en;
            }
            var html = '<div class="aca-modal-content"><h3>Select Services</h3>';
            html += '<div id="aca-time-info">'+formatRange(0)+'</div>';
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
            function updateInfo(){
                var mins = 0;
                modal.querySelectorAll('input[type=checkbox]:checked').forEach(function(c){
                    var srv = services.find(function(o){ return o.name === c.value; });
                    if(srv && parseInt(srv.duration)) mins += parseInt(srv.duration);
                });
                document.getElementById('aca-time-info').textContent = formatRange(mins||0);
                return mins;
            }
            modal.addEventListener('change', function(e){
                if(e.target.type === 'checkbox') updateInfo();
            });

            modal.querySelector('#aca-reserve-save').addEventListener('click', function(){
                var selected = [];
                modal.querySelectorAll('input[type=checkbox]:checked').forEach(function(c){ selected.push(c.value); });
                var name = modal.querySelector('#aca-name').value.trim();
                var phone = modal.querySelector('#aca-phone').value.trim();
                if(!selected.length || !name || !phone){ return; }
                var minutes = updateInfo();
                if(!minutes) return;
                var data = new FormData();
                data.append('action','aca_save_reservation');
                data.append('start', start);
                data.append('name', name);
                data.append('phone', phone);
                selected.forEach(function(v){ data.append('services[]', v); });
                fetch(opts.ajaxUrl, {method:'POST', body:data})
                    .then(function(r){ return r.json(); })
                    .then(function(res){
                        if(res && res.success){
                            alert('Thank you! When your appointment is approved it will show up on the calendar.');
                        }
                        modal.remove();
                    })
                    .catch(function(){ modal.remove(); });
            });
        }
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
