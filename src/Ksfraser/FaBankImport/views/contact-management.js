/**
 * Contact Management UI JavaScript Handlers
 *
 * Provides event handlers and utilities for contact matching and selection workflows.
 *
 * @package Ksfraser\FaBankImport\Views
 * @author Kevin Fraser
 * @since 20260322
 */

/**
 * Handle contact search form submission
 *
 * @param {HTMLFormElement} form The form element
 * @param {number} transactionId The transaction ID being processed
 * @returns {boolean} False to prevent default form submission
 */
function contactSearchSubmit(form, transactionId) {
	const searchTerm = form.querySelector('input[name="contact_search"]').value.trim();
	const searchBy = form.querySelector('input[name="search_by"]:checked').value;
	const threshold = parseInt(form.querySelector('input[name="threshold"]').value) || 75;

	if (!searchTerm) {
		alert('Please enter a search term');
		return false;
	}

	// Show loading indicator
	const loadingIndicator = form.querySelector('.loading-indicator');
	if (loadingIndicator) {
		loadingIndicator.style.display = 'flex';
	}

	// Disable submit button
	const submitBtn = form.querySelector('.search-btn');
	if (submitBtn) {
		submitBtn.disabled = true;
	}

	// Prepare search parameters
	const params = new URLSearchParams({
		action: 'contact_search',
		transaction_id: transactionId,
		search_term: searchTerm,
		search_by: searchBy,
		threshold: threshold / 100
	});

	// Perform AJAX search
	fetch('/api/contact-search', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: params.toString()
	})
		.then(response => {
			if (!response.ok) throw new Error('Search failed');
			return response.json();
		})
		.then(data => {
			handleContactSearchResults(transactionId, data);
		})
		.catch(error => {
			console.error('Contact search error:', error);
			alert('Search failed: ' + error.message);
		})
		.finally(() => {
			// Hide loading, re-enable button
			if (loadingIndicator) {
				loadingIndicator.style.display = 'none';
			}
			if (submitBtn) {
				submitBtn.disabled = false;
			}
		});

	return false;
}

/**
 * Handle contact search results
 *
 * @param {number} transactionId Transaction ID
 * @param {object} data Response data with matches
 */
function handleContactSearchResults(transactionId, data) {
	if (!data.success) {
		alert('Search error: ' + (data.message || 'Unknown error'));
		return;
	}

	const resultsContainer = document.getElementById('contact-matches-' + transactionId);
	if (!resultsContainer) {
		console.error('Results container not found');
		return;
	}

	if (data.matches && data.matches.length > 0) {
		// Display matches
		resultsContainer.innerHTML = data.html;
		resultsContainer.classList.remove('hidden');
		resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
	} else {
		// Show no results message
		resultsContainer.innerHTML = '<div class="no-results">No matches found. You can create a new contact.</div>';
		resultsContainer.classList.remove('hidden');
	}
}

/**
 * Accept selected contact match
 *
 * @param {number} transactionId Transaction ID
 * @param {string} radioInputName Name of the radio input group
 */
function contactMatchAccept(transactionId, radioInputName) {
	const radio = document.querySelector('input[name="' + radioInputName + '"]:checked');
	if (!radio) {
		alert('Please select a contact');
		return;
	}

	const contactId = radio.value;
	linkContactToTransaction(transactionId, contactId);
}

/**
 * Skip contact matching for this transaction
 *
 * @param {number} transactionId Transaction ID
 */
function contactMatchSkip(transactionId) {
	if (confirm('Skip contact matching for this transaction?')) {
		completeTransactionProcessing(transactionId, null);
	}
}

/**
 * Create new contact for transaction
 *
 * @param {number} transactionId Transaction ID
 */
function contactMatchCreateNew(transactionId) {
	const modal = createContactCreationModal(transactionId);
	document.body.appendChild(modal);
	modal.showModal ? modal.showModal() : modal.style.display = 'block';
}

/**
 * Link contact to transaction
 *
 * @param {number} transactionId Transaction ID
 * @param {number} contactId Contact ID
 */
function linkContactToTransaction(transactionId, contactId) {
	const params = new URLSearchParams({
		action: 'link_contact',
		transaction_id: transactionId,
		contact_id: contactId
	});

	showLoadingOverlay('Linking contact...');

	fetch('/api/contact-link', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: params.toString()
	})
		.then(response => {
			hideLoadingOverlay();
			if (!response.ok) throw new Error('Link failed');
			return response.json();
		})
		.then(data => {
			if (data.success) {
				showNotification('Contact linked successfully', 'success');
				completeTransactionProcessing(transactionId, contactId);
			} else {
				showNotification('Failed to link contact: ' + (data.message || 'Unknown error'), 'error');
			}
		})
		.catch(error => {
			console.error('Contact link error:', error);
			showNotification('Error linking contact: ' + error.message, 'error');
		});
}

/**
 * Suggest a search term in the search form
 *
 * @param {string} suggestion The suggested search term
 * @returns {boolean} False to prevent default link behavior
 */
function suggestSearch(suggestion) {
	// Find all search input fields and update them
	document.querySelectorAll('input[name="contact_search"]').forEach(input => {
		input.value = suggestion;
		input.focus();
	});

	return false;
}

/**
 * Set match threshold value
 *
 * @param {number} value Threshold value (0-100)
 */
function setMatchThreshold(value) {
	const display = document.getElementById('threshold-value');
	if (display) {
		display.textContent = value;
	}
}

/**
 * Edit a contact
 *
 * @param {number} contactId Contact ID
 */
function editContact(contactId) {
	// Navigate to contact edit page or open modal
	window.location.href = '/contact/edit/' + contactId;
}

/**
 * View contact transaction history
 *
 * @param {number} contactId Contact ID
 */
function viewContactHistory(contactId) {
	const modal = createHistoryModal(contactId);
	document.body.appendChild(modal);
	modal.showModal ? modal.showModal() : modal.style.display = 'block';
}

/**
 * Open FA Customer in new window
 *
 * @param {number} customerId FA Customer ID
 * @returns {boolean} False to prevent default link behavior
 */
function openFACustomer(customerId) {
	window.open('/fa/customer/' + customerId, '_blank', 'width=1024,height=768');
	return false;
}

/**
 * Open FA Supplier in new window
 *
 * @param {number} supplierId FA Supplier ID
 * @returns {boolean} False to prevent default link behavior
 */
function openFASupplier(supplierId) {
	window.open('/fa/supplier/' + supplierId, '_blank', 'width=1024,height=768');
	return false;
}

/**
 * Complete transaction processing
 *
 * @param {number} transactionId Transaction ID
 * @param {number|null} contactId Contact ID (if any)
 */
function completeTransactionProcessing(transactionId, contactId) {
	// Trigger AJAX to mark transaction as processed
	const params = new URLSearchParams({
		action: 'complete_processing',
		transaction_id: transactionId,
		contact_id: contactId || ''
	});

	fetch('/api/transaction-complete', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: params.toString()
	})
		.then(response => {
			if (response.ok) {
				// Move to next transaction or reload
				moveToNextTransaction();
			} else {
				throw new Error('Failed to complete processing');
			}
		})
		.catch(error => {
			console.error('Processing error:', error);
		});
}

/**
 * Move to next transaction in the list
 */
function moveToNextTransaction() {
	// Find the next unprocessed row and navigate to it
	const nextRow = document.querySelector('.transaction-row.unprocessed');
	if (nextRow) {
		nextRow.click();
		nextRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
	} else {
		// All done - show completion message
		showNotification('All transactions processed!', 'success');
		setTimeout(() => {
			window.location.href = '/bank-import/results';
		}, 2000);
	}
}

/**
 * Show loading overlay
 *
 * @param {string} message Message to display
 */
function showLoadingOverlay(message) {
	let overlay = document.getElementById('loading-overlay');
	if (!overlay) {
		overlay = document.createElement('div');
		overlay.id = 'loading-overlay';
		overlay.className = 'loading-overlay';
		document.body.appendChild(overlay);
	}

	overlay.innerHTML = `
		<div class="loading-content">
			<div class="spinner"></div>
			<p>${message}</p>
		</div>
	`;
	overlay.style.display = 'flex';
}

/**
 * Hide loading overlay
 */
function hideLoadingOverlay() {
	const overlay = document.getElementById('loading-overlay');
	if (overlay) {
		overlay.style.display = 'none';
	}
}

/**
 * Show notification/toast message
 *
 * @param {string} message Message text
 * @param {string} type 'success', 'error', 'warning', or 'info'
 * @param {number} duration Duration in milliseconds (0 for persistent)
 */
function showNotification(message, type = 'info', duration = 3000) {
	const notification = document.createElement('div');
	notification.className = 'notification notification-' + type;
	notification.innerHTML = `
		<span class="notification-message">${message}</span>
		<button class="notification-close" onclick="this.parentNode.remove()">×</button>
	`;

	// Add to page
	const container = document.getElementById('notification-container') || createNotificationContainer();
	container.appendChild(notification);

	// Auto-remove after duration
	if (duration > 0) {
		setTimeout(() => {
			notification.remove();
		}, duration);
	}

	return notification;
}

/**
 * Create notification container
 *
 * @returns {HTMLElement} Container element
 */
function createNotificationContainer() {
	const container = document.createElement('div');
	container.id = 'notification-container';
	container.className = 'notification-container';
	document.body.appendChild(container);
	return container;
}

/**
 * Create contact creation modal
 *
 * @param {number} transactionId Transaction ID
 * @returns {HTMLElement} Modal element
 */
function createContactCreationModal(transactionId) {
	const modal = document.createElement('dialog');
	modal.className = 'modal';
	modal.id = 'contact-creation-' + transactionId;

	modal.innerHTML = `
		<form method="dialog" class="modal-content">
			<h2 class="modal-title">Create New Contact</h2>
			<p>Enter the contact information below or go back to search for an existing contact.</p>

			<div class="form-group">
				<label for="contact-name">Name *</label>
				<input type="text" id="contact-name" name="name" required placeholder="Contact name">
			</div>

			<div class="form-group">
				<label for="contact-email">Email</label>
				<input type="email" id="contact-email" name="email" placeholder="contact@example.com">
			</div>

			<div class="form-group">
				<label for="contact-phone">Phone</label>
				<input type="tel" id="contact-phone" name="phone" placeholder="555-1234567">
			</div>

			<div class="form-group">
				<label for="contact-type">Type</label>
				<select id="contact-type" name="type">
					<option value="C">Customer</option>
					<option value="S">Supplier</option>
					<option value="E">Employee</option>
				</select>
			</div>

			<div class="modal-actions">
				<button type="button" class="btn btn-secondary" onclick="this.closest('dialog').close()">Cancel</button>
				<button type="button" class="btn btn-primary" onclick="saveNewContact(${transactionId})">Create & Link</button>
			</div>
		</form>
	`;

	return modal;
}

/**
 * Save new contact from modal
 *
 * @param {number} transactionId Transaction ID
 */
function saveNewContact(transactionId) {
	const modal = document.getElementById('contact-creation-' + transactionId);
	const form = modal.querySelector('form');

	const data = {
		action: 'create_contact',
		transaction_id: transactionId,
		name: form.querySelector('[name="name"]').value,
		email: form.querySelector('[name="email"]').value || '',
		phone: form.querySelector('[name="phone"]').value || '',
		type: form.querySelector('[name="type"]').value
	};

	showLoadingOverlay('Creating contact...');

	fetch('/api/contact-create', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: JSON.stringify(data)
	})
		.then(response => {
			hideLoadingOverlay();
			if (!response.ok) throw new Error('Creation failed');
			return response.json();
		})
		.then(result => {
			if (result.success && result.contact_id) {
				showNotification('Contact created successfully', 'success');
				modal.close();
				linkContactToTransaction(transactionId, result.contact_id);
			} else {
				showNotification('Failed to create contact: ' + (result.message || 'Unknown error'), 'error');
			}
		})
		.catch(error => {
			console.error('Contact creation error:', error);
			showNotification('Error creating contact: ' + error.message, 'error');
		});
}

/**
 * Create transaction history modal
 *
 * @param {number} contactId Contact ID
 * @returns {HTMLElement} Modal element
 */
function createHistoryModal(contactId) {
	const modal = document.createElement('dialog');
	modal.className = 'modal history-modal';
	modal.id = 'history-' + contactId;

	modal.innerHTML = `
		<form method="dialog" class="modal-content">
			<h2 class="modal-title">Contact Transaction History</h2>
			<div class="history-loading">Loading transactions...</div>

			<div class="modal-actions">
				<button type="button" class="btn btn-secondary" onclick="this.closest('dialog').close()">Close</button>
			</div>
		</form>
	`;

	// Load history asynchronously
	const loadingDiv = modal.querySelector('.history-loading');
	fetch('/api/contact-history/' + contactId)
		.then(r => r.json())
		.then(data => {
			if (data.transactions && data.transactions.length > 0) {
				const html = data.transactions.map(tx => `
					<div class="history-item">
						<span class="history-date">${tx.date}</span>
						<span class="history-type">${tx.type}</span>
						<span class="history-amount">${tx.amount}</span>
					</div>
				`).join('');
				loadingDiv.innerHTML = html;
			} else {
				loadingDiv.innerHTML = '<p>No transaction history</p>';
			}
		})
		.catch(() => {
			loadingDiv.innerHTML = '<p>Error loading history</p>';
		});

	return modal;
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', function() {
	// Add event listeners to any dynamically added elements
	initializeContactUI();
});

/**
 * Initialize contact UI elements
 */
function initializeContactUI() {
	// Add focus/blur handlers to match cards
	document.querySelectorAll('.match-card').forEach(card => {
		card.addEventListener('click', function() {
			const radio = this.querySelector('input[type="radio"]');
			if (radio) radio.checked = true;
		});

		card.addEventListener('keydown', function(e) {
			if (e.key === 'Enter' || e.key === ' ') {
				const radio = this.querySelector('input[type="radio"]');
				if (radio) radio.checked = true;
				e.preventDefault();
			}
		});
	});

	// Auto-focus on search input
	const searchInput = document.querySelector('.search-input');
	if (searchInput) {
		setTimeout(() => searchInput.focus(), 100);
	}
}
