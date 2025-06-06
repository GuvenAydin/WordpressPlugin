(function(){
    function init(){
        var textarea = document.getElementById('aca_services');
        var table = document.getElementById('aca-services-table');
        if(textarea && table){
            var nameField = document.getElementById('aca-service-name');
            var priceField = document.getElementById('aca-service-price');
            var minField = document.getElementById('aca-service-minutes');
            var addBtn = document.getElementById('aca-service-add');
            var indexField = document.getElementById('aca-service-index');

            function parse(){
                var lines = textarea.value ? textarea.value.split(/\n+/) : [];
                var data = [];
                lines.forEach(function(l){
                    var p = l.split('|');
                    data.push({name:p[0]||'', price:p[1]||'', minutes:p[2]||''});
                });
                return data;
            }

            function render(){
                var data = parse();
                var tbody = table.querySelector('tbody');
                tbody.innerHTML = '';
                data.forEach(function(item,i){
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>'+item.name+'</td>'+
                                   '<td>'+item.price+'</td>'+
                                   '<td>'+item.minutes+'</td>'+
                                   '<td><button type="button" class="edit" data-index="'+i+'">Edit</button> '+
                                   '<button type="button" class="delete" data-index="'+i+'">Delete</button></td>';
                    tbody.appendChild(tr);
                });
            }

            function saveData(data){
                var lines = data.map(function(it){
                    return it.name+'|'+it.price+'|'+it.minutes;
                });
                textarea.value = lines.join('\n');
                render();
            }

            addBtn.addEventListener('click', function(){
                var name = nameField.value.trim();
                if(!name) return;
                var price = priceField.value.trim();
                var minutes = minField.value.trim();
                var data = parse();
                var idx = indexField.value;
                if(idx !== ''){
                    data[idx] = {name:name, price:price, minutes:minutes};
                    indexField.value = '';
                    addBtn.textContent = 'Add Service';
                } else {
                    data.push({name:name, price:price, minutes:minutes});
                }
                nameField.value = priceField.value = minField.value = '';
                saveData(data);
            });

            table.addEventListener('click', function(e){
                if(e.target.classList.contains('delete')){
                    var data = parse();
                    data.splice(parseInt(e.target.getAttribute('data-index')),1);
                    saveData(data);
                } else if(e.target.classList.contains('edit')){
                    var data = parse();
                    var idx = parseInt(e.target.getAttribute('data-index'));
                    var item = data[idx];
                    nameField.value = item.name;
                    priceField.value = item.price;
                    minField.value = item.minutes;
                    indexField.value = idx;
                    addBtn.textContent = 'Update Service';
                }
            });

            render();
        }

        var dayTextarea = document.getElementById('aca_days_off');
        var dayTable = document.getElementById('aca-dayoff-table');
        if(dayTextarea && dayTable){
            var dayField = document.getElementById('aca-dayoff-date');
            var dayNameField = document.getElementById('aca-dayoff-name');
            var dayAdd = document.getElementById('aca-dayoff-add');
            var dayIndex = document.getElementById('aca-dayoff-index');

            function parseDays(){
                if(!dayTextarea.value) return [];
                return dayTextarea.value.split(/\n+/).map(function(l){
                    var p = l.split('|');
                    return {date:(p[0]||'').trim(), name:(p[1]||'').trim()};
                }).filter(function(o){ return o.date; });
            }

            function renderDays(){
                var arr = parseDays();
                var tbody = dayTable.querySelector('tbody');
                tbody.innerHTML = '';
                arr.forEach(function(d,i){
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td>'+d.date+'</td>'+
                                   '<td>'+d.name+'</td>'+
                                   '<td><button type="button" class="edit" data-index="'+i+'">Edit</button> '+
                                   '<button type="button" class="delete" data-index="'+i+'">Delete</button></td>';
                    tbody.appendChild(tr);
                });
            }

            function saveDays(arr){
                var lines = arr.map(function(o){ return o.date+'|'+o.name; });
                dayTextarea.value = lines.join('\n');
                renderDays();
            }

            dayAdd.addEventListener('click', function(){
                var dateVal = dayField.value.trim();
                if(!dateVal) return;
                var nameVal = dayNameField.value.trim();
                var arr = parseDays();
                var idx = dayIndex.value;
                if(idx !== ''){
                    arr[idx] = {date:dateVal, name:nameVal};
                    dayIndex.value = '';
                    dayAdd.textContent = 'Add Day';
                } else {
                    arr.push({date:dateVal, name:nameVal});
                }
                dayField.value = '';
                dayNameField.value = '';
                saveDays(arr);
            });

            dayTable.addEventListener('click', function(e){
                if(e.target.classList.contains('delete')){
                    var arr = parseDays();
                    arr.splice(parseInt(e.target.getAttribute('data-index')),1);
                    saveDays(arr);
                } else if(e.target.classList.contains('edit')){
                    var arr = parseDays();
                    var idx = parseInt(e.target.getAttribute('data-index'));
                    var item = arr[idx];
                    dayField.value = item.date;
                    dayNameField.value = item.name;
                    dayIndex.value = idx;
                    dayAdd.textContent = 'Update Day';
                }
            });

            renderDays();
        }
    }

    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
