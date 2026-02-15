<?php

$page_security = 'SA_BANKTRANSVIEW';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");

require_once(__DIR__ . '/class.bi_transactions.php');
require_once(__DIR__ . '/class.bi_transfer_matches.php');
require_once(__DIR__ . '/Services/TransferMatchService.php');

page(_($help_context = "Transfer Match Review Queue"), false, false, "", $js="");

$moduleMenuView = __DIR__ . '/views/module_menu_view.php';
if (!is_file($moduleMenuView)) {
    $moduleMenuView = __DIR__ . '/Views/module_menu_view.php';
}
include_once $moduleMenuView;
$menu = new \Views\ModuleMenuView();
$menu->renderMenu();

$transactions = new bi_transactions_model();
$transferMatches = new bi_transfer_matches_model();
$transferMatcher = new \KsfBankImport\Services\TransferMatchService($transactions, $transferMatches);

if (isset($_POST['ConfirmTransferMatch']) && is_array($_POST['ConfirmTransferMatch'])) {
    $tid = 0;
    $peerId = 0;

    foreach ($_POST['ConfirmTransferMatch'] as $transactionId => $peerList) {
        $tid = (int)$transactionId;
        if (is_array($peerList)) {
            $peerKeys = array_keys($peerList);
            if (!empty($peerKeys)) {
                $peerId = (int)$peerKeys[0];
            }
        }
        break;
    }

    if ($tid > 0 && $peerId > 0) {
        $transferMatcher->confirmMatch($tid, $peerId, 100.0);
        $transferMatches->set_requires_review_by_transaction($tid, 0);
        $transferMatches->set_requires_review_by_transaction($peerId, 0);
        display_notification('Confirmed transfer pair: #' . $tid . ' ↔ #' . $peerId . '.');
    }
}

if (isset($_POST['ResetForReprocess']) && is_array($_POST['ResetForReprocess'])) {
    $tid = (int)key($_POST['ResetForReprocess']);
    if ($tid > 0) {
        $transferMatches->clear_by_transaction($tid);
        $transactions->reset_transaction_association($tid);
        display_notification('Transaction #' . $tid . ' reset for reprocess.');
    }
}

if (isset($_POST['ClearReviewFlag']) && is_array($_POST['ClearReviewFlag'])) {
    $tid = (int)key($_POST['ClearReviewFlag']);
    if ($tid > 0) {
        $transferMatches->set_requires_review_by_transaction($tid, 0);
        display_notification('Cleared review flag for #' . $tid . '.');
    }
}

if (isset($_POST['RejectTransferMatch']) && is_array($_POST['RejectTransferMatch'])) {
    $tid = (int)key($_POST['RejectTransferMatch']);
    if ($tid > 0) {
        $transferMatches->reject_by_transaction($tid);
        display_notification('Marked transfer match as rejected for #' . $tid . '.');
    }
}

$reviewRows = $transactions->get_transactions_requiring_review(500);

start_form();

echo '<h3>Check Needed Queue</h3>';

if (empty($reviewRows)) {
    display_notification('No transactions currently require review.');
} else {
    start_table(TABLESTYLE2);
    table_header([
        _('ID'),
        _('Date'),
        _('Bank Account'),
        _('Title'),
        _('Amount'),
        _('Transfer Status'),
        _('Confirmed Peer'),
        _('Candidates'),
        _('Actions')
    ]);

    foreach ($reviewRows as $row) {
        start_row();

        label_cell((string)$row['id']);
        label_cell(isset($row['valueTimestamp']) ? sql2date($row['valueTimestamp']) : '');
        label_cell((string)($row['our_account'] ?? ''));
        label_cell((string)($row['transactionTitle'] ?? ''));
        label_cell(number_format((float)($row['transactionAmount'] ?? 0), 2));
        $confirmedPeer = $transferMatches->get_confirmed_peer_for_transaction((int)$row['id']);
        label_cell($confirmedPeer ? 'confirmed' : 'unmatched');
        label_cell($confirmedPeer ? (string)$confirmedPeer : '');

        $candidatesHtml = '';
        $decodedCandidates = $transferMatches->get_candidates_for_transaction((int)$row['id']);
        if (!is_array($decodedCandidates) || empty($decodedCandidates)) {
            $candidatesHtml = '<span style="color:#666;">None</span>';
        } else {
            $candidateLines = [];
            foreach (array_slice($decodedCandidates, 0, 5) as $candidate) {
                if (!is_array($candidate)) {
                    continue;
                }

                $peerId = (int)($candidate['peer_id'] ?? ($candidate['id'] ?? 0));
                $score = isset($candidate['score']) ? (float)$candidate['score'] : null;
                $peerDate = (string)($candidate['valueTimestamp'] ?? '');

                if ($peerId <= 0) {
                    continue;
                }

                $line = '#'.$peerId;
                if ($peerDate !== '') {
                    $line .= ' (' . $peerDate . ')';
                }
                if ($score !== null) {
                    $line .= ' score=' . number_format($score, 1);
                }
                $line .= '&nbsp;';
                $line .= submit(
                    'ConfirmTransferMatch[' . (int)$row['id'] . '][' . $peerId . ']',
                    _('Confirm'),
                    false,
                    '',
                    'default'
                );
                $candidateLines[] = $line;
            }

            if (empty($candidateLines)) {
                $candidatesHtml = '<span style="color:#666;">None</span>';
            } else {
                $candidatesHtml = implode('<br />', $candidateLines);
            }
        }
        label_cell($candidatesHtml);

        $actions = '';
        $actions .= submit('ResetForReprocess[' . (int)$row['id'] . ']', _('Reset/Reprocess'), false, '', 'default');
        $actions .= '&nbsp;';
        $actions .= submit('RejectTransferMatch[' . (int)$row['id'] . ']', _('Reject Match'), false, '', 'default');
        $actions .= '&nbsp;';
        $actions .= submit('ClearReviewFlag[' . (int)$row['id'] . ']', _('Clear Flag'), false, '', 'default');
        label_cell($actions);

        end_row();
    }

    end_table(1);
}

end_form();

end_page();
