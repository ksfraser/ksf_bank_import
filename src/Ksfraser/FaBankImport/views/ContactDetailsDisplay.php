<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Contact Details Display View
 *
 * Shows detailed information about a contact with context.
 * Used in selection workflows and confirmation dialogs.
 *
 * Features:
 * - Comprehensive contact info layout
 * - FA system integration details
 * - Transaction history snippet
 * - Relationship indicators
 * - Edit/manage links
 * - Verification badges
 *
 * @package     Ksfraser\FaBankImport\Views
 * @author      Kevin Fraser
 * @since       20260322
 * @implements  HtmlElementInterface
 */
class ContactDetailsDisplay implements HtmlElementInterface
{
	/**
	 * @var object Contact object with properties
	 */
	private $contact;

	/**
	 * @var array Additional context data
	 */
	private $context = [];

	/**
	 * @var bool Show edit actions
	 */
	private $showActions = true;

	/**
	 * Constructor
	 *
	 * @param object $contact Contact object
	 * @param array $context Additional context
	 * @param bool $showActions Whether to show action buttons
	 */
	public function __construct($contact, array $context = [], $showActions = true)
	{
		$this->contact = $contact;
		$this->context = $context;
		$this->showActions = $showActions;
	}

	/**
	 * Render to HTML string
	 *
	 * @return string HTML markup
	 */
	public function getHtml(): string
	{
		if (!$this->contact) {
			return '<div class="contact-details-empty">No contact data</div>';
		}

		$html = '';
		$html .= '<div class="contact-details-display">';
		$html .= $this->renderHeader();
		$html .= $this->renderMainInfo();
		$html .= $this->renderContactMethods();
		$html .= $this->renderFAIntegration();
		if (!empty($this->context)) {
			$html .= $this->renderContext();
		}
		if ($this->showActions) {
			$html .= $this->renderActions();
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Output HTML directly to screen
	 *
	 * @return void
	 */
	public function toHtml(): void
	{
		echo $this->getHtml();
	}

	/**
	 * Render header with contact name and type
	 *
	 * @return string HTML
	 */
	private function renderHeader(): string
	{
		$type = $this->getContactTypeLabel($this->contact->contact_type ?? '');
		$badge = $type ? '<span class="contact-type-badge type-' . strtolower($type) . '">' . $type . '</span>' : '';

		$html = '';
		$html .= '<div class="details-header">';
		$html .= '  <h3 class="contact-name">' . htmlspecialchars($this->contact->name ?? 'Unknown') . '</h3>';
		$html .= '  ' . $badge;
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render main contact information
	 *
	 * @return string HTML
	 */
	private function renderMainInfo(): string
	{
		$html = '';
		$html .= '<div class="details-section main-info">';
		$html .= '  <dl class="info-list">';

		// Contact ID
		if (!empty($this->contact->id)) {
			$html .= '    <dt>ID</dt>';
			$html .= '    <dd>' . htmlspecialchars($this->contact->id) . '</dd>';
		}

		// Address
		if (!empty($this->contact->address)) {
			$html .= '    <dt>Address</dt>';
			$html .= '    <dd>' . htmlspecialchars($this->contact->address) . '</dd>';
		}

		// Reference
		if (!empty($this->contact->reference)) {
			$html .= '    <dt>Reference</dt>';
			$html .= '    <dd>' . htmlspecialchars($this->contact->reference) . '</dd>';
		}

		// Created date
		if (!empty($this->contact->created_at)) {
			$html .= '    <dt>Created</dt>';
			$html .= '    <dd>' . htmlspecialchars(date('M d, Y', strtotime($this->contact->created_at))) . '</dd>';
		}

		$html .= '  </dl>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render contact methods (email, phone)
	 *
	 * @return string HTML
	 */
	private function renderContactMethods(): string
	{
		$hasEmail = !empty($this->contact->email);
		$hasPhone = !empty($this->contact->phone);

		if (!$hasEmail && !$hasPhone) {
			return '';
		}

		$html = '';
		$html .= '<div class="details-section contact-methods">';
		$html .= '  <h4>Contact Methods</h4>';
		$html .= '  <div class="methods-grid">';

		if ($hasEmail) {
			$html .= '    <div class="method-item email">';
			$html .= '      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
			$html .= '        <rect x="2" y="4" width="20" height="16" rx="2"></rect>';
			$html .= '        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>';
			$html .= '      </svg>';
			$html .= '      <a href="mailto:' . htmlspecialchars($this->contact->email) . '" class="method-link">';
			$html .= '        ' . htmlspecialchars($this->contact->email);
			$html .= '      </a>';
			$html .= '    </div>';
		}

		if ($hasPhone) {
			$html .= '    <div class="method-item phone">';
			$html .= '      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
			$html .= '        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>';
			$html .= '      </svg>';
			$html .= '      <a href="tel:' . htmlspecialchars($this->contact->phone) . '" class="method-link">';
			$html .= '        ' . htmlspecialchars($this->contact->phone);
			$html .= '      </a>';
			$html .= '    </div>';
		}

		$html .= '  </div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render FA system integration details
	 *
	 * @return string HTML
	 */
	private function renderFAIntegration(): string
	{
		$hasFA = !empty($this->contact->fa_customer_id) || !empty($this->contact->fa_supplier_id);

		if (!$hasFA) {
			return '';
		}

		$html = '';
		$html .= '<div class="details-section fa-integration">';
		$html .= '  <h4>FA System</h4>';
		$html .= '  <div class="fa-links">';

		if (!empty($this->contact->fa_customer_id)) {
			$html .= '    <div class="fa-link-item">';
			$html .= '      <span class="fa-type">Customer ID:</span>';
			$html .= '      <a href="#" onclick="return openFACustomer(' . (int)$this->contact->fa_customer_id . ')" class="fa-link">';
			$html .= '        ' . htmlspecialchars($this->contact->fa_customer_id);
			$html .= '      </a>';
			$html .= '    </div>';
		}

		if (!empty($this->contact->fa_supplier_id)) {
			$html .= '    <div class="fa-link-item">';
			$html .= '      <span class="fa-type">Supplier ID:</span>';
			$html .= '      <a href="#" onclick="return openFASupplier(' . (int)$this->contact->fa_supplier_id . ')" class="fa-link">';
			$html .= '        ' . htmlspecialchars($this->contact->fa_supplier_id);
			$html .= '      </a>';
			$html .= '    </div>';
		}

		$html .= '  </div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render additional context
	 *
	 * @return string HTML
	 */
	private function renderContext(): string
	{
		$html = '';
		$html .= '<div class="details-section context">';

		if (isset($this->context['match_score'])) {
			$score = $this->context['match_score'];
			$scorePercent = round($score * 100);

			$html .= '  <div class="context-item match-score">';
			$html .= '    <span class="label">Match Score:</span>';
			$html .= '    <span class="value score-' . $this->getScoreClass($score) . '">' . $scorePercent . '%</span>';
			$html .= '  </div>';
		}

		if (isset($this->context['match_method'])) {
			$html .= '  <div class="context-item match-method">';
			$html .= '    <span class="label">Found by:</span>';
			$html .= '    <span class="value">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $this->context['match_method']))) . '</span>';
			$html .= '  </div>';
		}

		if (isset($this->context['transaction_context'])) {
			$html .= '  <div class="context-item transaction">';
			$html .= '    <span class="label">Related to:</span>';
			$html .= '    <span class="value">' . htmlspecialchars($this->context['transaction_context']) . '</span>';
			$html .= '  </div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render action buttons
	 *
	 * @return string HTML
	 */
	private function renderActions(): string
	{
		$contactId = $this->contact->id ?? 0;

		$html = '';
		$html .= '<div class="details-actions">';
		$html .= '  <button type="button" class="btn btn-secondary" onclick="editContact(' . $contactId . ')">Edit Contact</button>';
		$html .= '  <button type="button" class="btn btn-secondary" onclick="viewContactHistory(' . $contactId . ')">View History</button>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get contact type label
	 *
	 * @param string $type Contact type code
	 * @return string Label
	 */
	private function getContactTypeLabel($type): string
	{
		$labels = [
			'C' => 'Customer',
			'S' => 'Supplier',
			'E' => 'Employee',
		];

		return $labels[$type] ?? 'Contact';
	}

	/**
	 * Get CSS class for score
	 *
	 * @param float $score Score (0-1)
	 * @return string CSS class
	 */
	private function getScoreClass($score): string
	{
		if ($score >= 0.95) {
			return 'excellent';
		} elseif ($score >= 0.85) {
			return 'very-good';
		} elseif ($score >= 0.75) {
			return 'good';
		} else {
			return 'fair';
		}
	}
}
