<?php

namespace Ksfraser\Views;

use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlRaw;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlFragment;

/**
 * SRP view for rendering transfer match candidates in the 4th column.
 */
class TransferMatchCandidatesView
{
    /** @var array<int, array<string, mixed>> */
    private $candidates;

    /** @var string */
    private $status;

    /** @var int */
    private $transactionId;

    /**
     * @param array<int, array<string, mixed>> $candidates
     */
    public function __construct(array $candidates, string $status = 'unmatched', int $transactionId = 0)
    {
        $this->candidates = $candidates;
        $this->status = $status;
        $this->transactionId = $transactionId;
    }

    public function render(): HtmlFragment
    {
        $fragment = new HtmlFragment();

        if (empty($this->candidates)) {
            return $fragment;
        }

        $lines = [];
        foreach ($this->candidates as $candidate) {
            $peerId = (int)($candidate['peer_id'] ?? 0);
            $peerDate = (string)($candidate['valueTimestamp'] ?? '');
            $peerAccount = (string)($candidate['our_account'] ?? '');
            $peerDc = (string)($candidate['transactionDC'] ?? '');
            $peerAmount = (string)($candidate['transactionAmount'] ?? '');
            $score = isset($candidate['score']) ? (float)$candidate['score'] : null;

            $line = '#' . $peerId
                . ' | ' . $peerDate
                . ' | ' . $peerAccount
                . ' | ' . $peerDc
                . ' | ' . $peerAmount;

            if ($score !== null) {
                $line .= ' | score=' . number_format($score, 1);
            }

            $lines[] = $line;
        }

        $title = 'Transfer Candidates';
        if ($this->status !== '') {
            $title .= ' [' . $this->status . ']';
        }

        $label = new HtmlString($title);
        $content = new HtmlRaw(implode('<br />', $lines));
        $fragment->addChild(new HtmlLabelRow($label, $content));

        return $fragment;
    }
}
