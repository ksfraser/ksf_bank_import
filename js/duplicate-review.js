/**
 * Duplicate Review Dashboard JavaScript
 * 
 * Handles:
 * - AJAX actions (confirm, move, reject)
 * - Row state updates
 * - Notifications
 */

document.addEventListener('DOMContentLoaded', function() {
    bindActionButtons();
});

/**
 * Bind click handlers to action buttons.
 */
function bindActionButtons() {
    const actionButtons = document.querySelectorAll('[data-action]');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const action = this.dataset.action;
            const dupeId = parseInt(this.dataset.dupeId);
            const row = document.querySelector(`[data-dupe-id="${dupeId}"]`);
            const notesField = row.querySelector('.notes-field');
            const notes = notesField ? notesField.value.trim() : '';
            
            handleDuplicateAction(action, dupeId, notes, row);
        });
    });
}

/**
 * Handle duplicate action via AJAX.
 */
function handleDuplicateAction(action, dupeId, notes, row) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('dupe_id', dupeId);
    formData.append('notes', notes);
    
    // Show loading state
    disableActionButtons(row);
    
    fetch(window.location.pathname, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            updateRowState(row, action);
            
            // Optionally fade out and remove after success
            setTimeout(() => {
                row.style.opacity = '0.5';
                row.style.textDecoration = 'line-through';
            }, 500);
        } else {
            showNotification('error', data.message);
            enableActionButtons(row);
        }
    })
    .catch(error => {
        showNotification('error', 'Error: ' + error.message);
        enableActionButtons(row);
    });
}

/**
 * Update row visual state after action.
 */
function updateRowState(row, action) {
    const status = getStatusForAction(action);
    const statusElement = row.querySelector('.audit-status');
    
    if (statusElement) {
        statusElement.innerHTML = `Status: <strong>${status}</strong>`;
    }
    
    // Add visual indicator
    row.classList.add(`row-${action}`);
}

/**
 * Map action to display status.
 */
function getStatusForAction(action) {
    const statusMap = {
        'confirm': '✓ Confirmed Duplicate',
        'move': '→ Moved to Statement',
        'reject': '✗ Rejected'
    };
    return statusMap[action] || action;
}

/**
 * Disable action buttons while request is pending.
 */
function disableActionButtons(row) {
    const buttons = row.querySelectorAll('button[data-action]');
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'progress';
    });
}

/**
 * Re-enable action buttons after request completes.
 */
function enableActionButtons(row) {
    const buttons = row.querySelectorAll('button[data-action]');
    buttons.forEach(btn => {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    });
}

/**
 * Show notification message.
 */
function showNotification(type, message) {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem;
        background: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(20px);
        }
    }
`;
document.head.appendChild(style);
