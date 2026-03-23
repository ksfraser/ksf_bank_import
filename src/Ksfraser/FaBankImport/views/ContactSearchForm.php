<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;

/**
 * Contact Search Form View
 *
 * Provides an interface for searching contacts by various criteria.
 * Supports multiple search strategies: name, email, phone, FA IDs.
 *
 * Features:
 * - Multi-criteria search form
 * - Auto-submit on input
 * - Search history suggestions
 * - Loading indicators
 * - Result count display
 *
 * @package     Ksfraser\FaBankImport\Views
 * @author      Kevin Fraser
 * @since       20260322
 * @implements  HtmlElementInterface
 */
class ContactSearchForm implements HtmlElementInterface
{
	/**
	 * @var int Transaction ID
	 */
	private $transactionId = 0;

	/**
	 * @var string Current search term
	 */
	private $searchTerm = '';

	/**
	 * @var array Search suggestions
	 */
	private $suggestions = [];

	/**
	 * @var string Submit handler callback
	 */
	private $submitHandler = 'contactSearchSubmit';

	/**
	 * Constructor
	 *
	 * @param int $transactionId Transaction ID
	 * @param string $searchTerm Current search term
	 * @param array $suggestions Search suggestions
	 */
	public function __construct($transactionId = 0, $searchTerm = '', array $suggestions = [])
	{
		$this->transactionId = $transactionId;
		$this->searchTerm = $searchTerm;
		$this->suggestions = $suggestions;
	}

	/**
	 * Render to HTML string
	 *
	 * @return string HTML markup
	 */
	public function getHtml(): string
	{
		$html = '';
		$html .= '<div class="contact-search-form">';
		$html .= $this->renderHeader();
		$html .= $this->renderForm();
		$html .= $this->renderSuggestions();
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
	 * Render form header
	 *
	 * @return string HTML
	 */
	private function renderHeader(): string
	{
		$html = '';
		$html .= '<div class="search-header">';
		$html .= '  <h3>Search for Contact</h3>';
		$html .= '  <p class="search-hint">Enter contact name, email, phone, or existing FA ID</p>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render search form
	 *
	 * @return string HTML
	 */
	private function renderForm(): string
	{
		$formId = 'contact_search_' . $this->transactionId;
		$inputId = $formId . '_input';

		$html = '';
		$html .= '<form id="' . $formId . '" class="search-form" onsubmit="return ' . $this->submitHandler . '(this, ' . $this->transactionId . ')">';

		// Search input field
		$html .= '<div class="search-input-container">';
		$html .= '  <div class="search-wrapper">';
		$html .= '    <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
		$html .= '      <circle cx="11" cy="11" r="8"></circle>';
		$html .= '      <path d="m21 21-4.35-4.35"></path>';
		$html .= '    </svg>';
		$html .= '    <input type="text" id="' . $inputId . '" name="contact_search" class="search-input" ';
		$html .= 'placeholder="e.g., Acme Corp, john@acme.com, 555-1234, 42" ';
		$html .= 'value="' . htmlspecialchars($this->searchTerm) . '" ';
		$html .= 'autocomplete="off" required';
		$html .= '    />';

		if (!empty($this->searchTerm)) {
			$html .= '    <button type="button" class="clear-btn" onclick="document.getElementById(\'' . $inputId . '\').value=\'\'; document.getElementById(\'' . $inputId . '\').focus();">×</button>';
		}

		$html .= '  </div>';
		$html .= '</div>';

		// Search criteria tabs
		$html .= '<div class="search-criteria">';
		$html .= '  <label class="criteria-label">';
		$html .= '    <input type="radio" name="search_by" value="auto" checked> Auto-detect';
		$html .= '  </label>';
		$html .= '  <label class="criteria-label">';
		$html .= '    <input type="radio" name="search_by" value="name"> Name';
		$html .= '  </label>';
		$html .= '  <label class="criteria-label">';
		$html .= '    <input type="radio" name="search_by" value="email"> Email';
		$html .= '  </label>';
		$html .= '  <label class="criteria-label">';
		$html .= '    <input type="radio" name="search_by" value="phone"> Phone';
		$html .= '  </label>';
		$html .= '</div>';

		// Search button
		$html .= '<div class="search-controls">';
		$html .= '  <button type="submit" class="btn btn-primary search-btn">Search</button>';
		$html .= '  <span class="loading-indicator" style="display:none;">';
		$html .= '    <span class="spinner"></span> Searching...';
		$html .= '  </span>';
		$html .= '</div>';

		// Threshold control
		$html .= '<div class="search-advanced">';
		$html .= '  <details class="threshold-control">';
		$html .= '    <summary>Match Sensitivity</summary>';
		$html .= '    <div class="threshold-slider">';
		$html .= '      <label>Require at least <span id="threshold-value">75</span>% match:</label>';
		$html .= '      <input type="range" name="threshold" min="50" max="100" value="75" class="threshold-input" ';
		$html .= '             oninput="document.getElementById(\'threshold-value\').textContent = this.value">';
		$html .= '      <span class="threshold-help">Higher = stricter matching (finds fewer results)</span>';
		$html .= '    </div>';
		$html .= '  </details>';
		$html .= '</div>';

		$html .= '</form>';

		return $html;
	}

	/**
	 * Render search suggestions
	 *
	 * @return string HTML
	 */
	private function renderSuggestions(): string
	{
		if (empty($this->suggestions)) {
			return '';
		}

		$html = '';
		$html .= '<div class="search-suggestions">';
		$html .= '  <h4>Suggestions</h4>';
		$html .= '  <ul class="suggestions-list">';

		foreach ($this->suggestions as $suggestion) {
			$html .= '    <li>';
			$html .= '      <a href="#" class="suggestion-link" onclick="return suggestSearch(\'' . htmlspecialchars($suggestion) . '\')">';
			$html .= '        ' . htmlspecialchars($suggestion);
			$html .= '      </a>';
			$html .= '    </li>';
		}

		$html .= '  </ul>';
		$html .= '</div>';

		return $html;
	}
}
