<?php

/**
 * REST API Route Definitions
 *
 * Defines all /api/* routes for contact management workflow.
 * Routes are loaded and dispatched by the request router middleware.
 *
 * @package    Ksfraser\FaBankImport\Config
 * @since      20260323
 */

use Ksfraser\FaBankImport\Controllers\Api\ContactController;

return [
	// Contact Management Endpoints
	'POST /api/contact-search' => [ContactController::class, 'search'],
	'POST /api/contact-link' => [ContactController::class, 'link'],
	'POST /api/contact-create' => [ContactController::class, 'create'],
	'GET /api/contact-history/{contactId}' => [ContactController::class, 'history'],
	'POST /api/transaction-complete' => [ContactController::class, 'completeProcessing'],

	// Additional contact operations
	'GET /api/contact/{contactId}' => [ContactController::class, 'get'],
	'PUT /api/contact/{contactId}' => [ContactController::class, 'update'],
	'DELETE /api/contact/{contactId}' => [ContactController::class, 'delete'],
];
