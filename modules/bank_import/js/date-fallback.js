;(function(){
    'use strict';
    try {
        var inputs = document.querySelectorAll('input[name*="Date"], input[id*="Date"], input[class*="date"], input.date-picker, input.datepicker');
        Array.prototype.forEach.call(inputs, function(inp){
            if (!inp) return;
            try { inp.setAttribute('data-original-type', inp.type || 'text'); } catch(e){}
            var icon = inp.parentElement && (inp.parentElement.querySelector('img.calendar, img[alt*="Date"], button.calendar, a.calendar') || inp.parentElement.querySelector('img, button, a'));
            if (icon) {
                icon.style.cursor = 'pointer';
                icon.addEventListener('click', function(e){
                    try { inp.type = 'date'; inp.focus(); } catch(err) { console.log('date hotfix toggle error', err); }
                    try { e.preventDefault(); e.stopPropagation(); } catch(_){}
                    setTimeout(function(){ try { inp.type = inp.getAttribute('data-original-type') || 'text'; } catch(e){} }, 800);
                });
            } else {
                inp.addEventListener('focus', function(){ try { inp.type = 'date'; } catch(e){} });
                inp.addEventListener('blur', function(){ setTimeout(function(){ try { inp.type = inp.getAttribute('data-original-type') || 'text'; } catch(e){} }, 500); });
            }
        });
    } catch(e){ if (window && window.console) console.log('date hotfix init error', e); }
})();
