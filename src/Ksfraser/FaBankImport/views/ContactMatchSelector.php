<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\HTML_TABLE;
use Ksfraser\HTML\Primitives\HTML_DIV;

/**
 * Contact Match Selector View
 *
 * Displays contact matching results for user selection during bank transaction processing.
 * Shows match confidence scores, match methods, and allows selection of the best match.
 *
 * Features:
 * - Ranked match results with scores
 * - Match method attribution (name, email, phone, FA ID)
 * - Visual confidence indicators
 * - Selection/acceptance UI
 * - Context about the search criteria
 *
 * @package     Ksfraser\FaBankImport\Views
 * @author      Kevin Fraser
 * @since       20260322
 * @implements  HtmlElementInterface
 */
class ContactMatchSelector implements HtmlElementInterface
{
	/**
	 * @var array Contact match results from ContactMatchingService::findBestMatch()
	 */
	private $matches = [];

	/**
	 * @var string Search term used to find matches
	 */
	private $searchTerm = '';

	/**
	 * @var string HTML ID prefix for form elements
	 */
	private $idPrefix = 'contact_match';

	/**
	 * @var int Transaction ID being processed
	 */
	private $transactionId = 0;

	/**
	 * Constructor
	 *
	 * @param array $matches Array of match results from ContactMatchingService
	 * @param string $searchTerm The term used to search
	 * @param int $transactionId The transaction ID being processed
	 */
	public function __construct(array $matches, $searchTerm = '', $transactionId = 0)
	{
		$this->matches = $matches;
		$this->searchTerm = $searchTerm;
		$this->transactionId = $transactionId;
		$this->idPrefix = "contact_match_{$transactionId}";
	}

	/**
	 * Render the match selector to HTML string
	 *
	 * @return string HTML markup
	 */
	public function getHtml(): string
	{
		if (empty($this->matches)) {
			return $this->renderNoMatches();
		}

		return $this->renderMatches();
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
	 * Render when no matches found
	 *
	 * @return string HTML
	 */
	private function renderNoMatches(): string
	{
		$html = '';
		$html .= '<div class="contact-match-selector contact-match-empty">';
		$html .= '  <div class="match-header">';
		$html .= '    <h4>No Matching Contacts Found</h4>';
		$html .= '    <p class="search-term">Searched for: <strong>' . htmlspecialchars($this->searchTerm) . '</strong></p>';
		$html .= '  </div>';
		$html .= '  <div class="match-actions">';
		$html .= '    <button type="button" class="btn btn-secondary" onclick="contactMatchSkip(' . $this->transactionId . ')">Skip Contact Match</button>';
		$html .= '    <button type="button" class="btn btn-primary" onclick="contactMatchCreateNew(' . $this->transactionId . ')">Create New Contact</button>';
		$html .= '  </div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render match results list
	 *
	 * @return string HTML
	 */
	private function renderMatches(): string
	{
		$html = '';
		$html .= '<div class="contact-match-selector">';
		$html .= $this->renderHeader();
		$html .= $this->renderMatchesList();
		$html .= $this->renderActions();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render header with search context
	 *
	 * @return string HTML
	 */
	private function renderHeader(): string
	{
		$count = count($this->matches);
		$label = $count === 1 ? 'match' : 'matches';

		$html = '';
		$html .= '<div class="match-header">';
		$html .= '  <h4>Contact Matches Found</h4>';
		$html .= '  <p class="match-count">' . $count . ' ' . $label . ' for <strong>"' . htmlspecialchars($this->searchTerm) . '"</strong></p>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render matches list with radio buttons
	 *
	 * @return string HTML
	 */
	private function renderMatchesList(): string
	{
		$html = '';
		$html .= '<div class="matches-container">';

		foreach ($this->matches as $index => $match) {
			$contact = $match['contact'];
			$score = $match['score'];
			$method = $match['match_method'];
			$isSelected = $index === 0 ? 'checked' : '';
			$scorePercent = round($score * 100);
			$scoreClass = $this->getScoreClass($score);

			$html .= '<div class="match-card">';
			$html .= '  <div class="match-selection">';
			$html .= '    <input type="radio" name="' . $this->idPrefix . '_selected" value="' . $contact->id . '" ' . $isSelected . ' class="match-radio" id="' . $this->idPrefix . '_' . $index . '">';
			$html .= '    <label for="' . $this->idPrefix . '_' . $index . '" class="match-label">';

			// Contact info
			$html .= '      <div class="match-info">';
			$html .= '        <span class="contact-name">' . htmlspecialchars($contact->name) . '</span>';

			// Contact details
			if (!empty($contact->email)) {
				$html .= '        <span class="contact-email">' . htmlspecialchars($contact->email) . '</span>';
			}
			if (!empty($contact->phone)) {
				$html .= '        <span class="contact-phone">' . htmlspecialchars($contact->phone) . '</span>';
			}

			$html .= '      </div>';
			$html .= '    </label>';
			$html .= '  </div>';

			// Score indicator
			$html .= '  <div class="match-score-container">';
			$html .= '    <div class="match-score ' . $scoreClass . '">';
			$html .= '      <span class="score-label">Confidence</span>';
			$html .= '      <span class="score-value">' . $scorePercent . '%</span>';
			$html .= '    </div>';
			$html .= '    <div class="match-method">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $method))) . ' match</div>';
			$html .= '  </div>';

			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get CSS class for score indicator
	 *
	 * @param float $score Score value (0-1)
	 * @return string CSS class
	 */
	private function getScoreClass($score): string
	{
		if ($score >= 0.95) {
			return 'score-excellent';
		} elseif ($score >= 0.85) {
			return 'score-very-good';
		} elseif ($score >= 0.75) {
			return 'score-good';
		} else {
			return 'score-fair';
		}
	}

	/**
	 * Render action buttons
	 *
	 * @return string HTML
	 */
	private function renderActions(): string
	{
		$html = '';
		$html .= '<div class="match-actions">';
		$html .= '  <button type="button" class="btn btn-primary" onclick="contactMatchAccept(' . $this->transactionId . ', \'' . $this->idPrefix . '_selected\')">Accept Selected</button>';
		$html .= '  <button type="button" class="btn btn-secondary" onclick="contactMatchSkip(' . $this->transactionId . ')">Skip for Now</button>';
		$html .= '  <button type="button" class="btn btn-outline" onclick="contactMatchCreateNew(' . $this->transactionId . ')">Create New Contact</button>';
		$html .= '</div>';

		return $html;
	}
}
