;(function(){
    'use strict';
    // Run after DOM ready to avoid racing with FA scripts
    function init() {
        try {
            var inputs = document.querySelectorAll('input[name*="Date"], input[id*="Date"], input[class*="date"], input.date-picker, input.datepicker');
            Array.prototype.forEach.call(inputs, function(inp){
                if (!inp) return;
                try { inp.setAttribute('data-original-type', inp.type || 'text'); } catch(e){}
                // Only target explicit calendar trigger elements to avoid intercepting other click handlers
                var icon = inp.parentElement && inp.parentElement.querySelector('.calendar, .calendar-icon, .ui-datepicker-trigger, .datepicker-trigger');
                if (icon) {
                    icon.style.cursor = 'pointer';
                    icon.addEventListener('click', function _dateFallbackClick(e){
                        try {
                            // Prefer the modern showPicker() if available
                            if (typeof inp.showPicker === 'function') {
                                inp.showPicker();
                            } else {
                                // Focus; temporarily switch to date type to trigger native picker on some browsers
                                inp.focus();
                                try { var orig = inp.type; inp.type = 'date'; } catch(err){}
                                setTimeout(function(){ try { inp.type = orig || 'text'; } catch(e){} }, 800);
                            }
                        } catch(err) { console.log('date hotfix toggle error', err); }
                        // Do NOT preventDefault or stopPropagation — allow other handlers to run
                    });
                } else {
                    inp.addEventListener('focus', function(){ try { inp.type = 'date'; } catch(e){} });
                    inp.addEventListener('blur', function(){ setTimeout(function(){ try { inp.type = inp.getAttribute('data-original-type') || 'text'; } catch(e){} }, 500); });
                }
            });
        } catch(e){ if (window && window.console) console.log('date hotfix init error', e); }
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
