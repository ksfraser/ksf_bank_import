<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Views;

/**
 * The screen that maps a detected account id to a FrontAccounting bank account.
 *
 * Rendered after the upload step when the detected identifiers of the parsed
 * statements cannot be resolved automatically (FR-002). The user picks a bank
 * account per identifier (FR-003) and can flag the choice to be remembered
 * (FR-006). The step is blocking: the import is only offered once the
 * resolution is applied.
 *
 * The screen emits no <form> of its own: the import screen already wraps the
 * step in one form, and the Proceed/Cancel buttons submit that outer form
 * (a nested form would break the outer one).
 *
 * @package Ksfraser\FaBankImport\Views
 * @since 20261011
 * @BABOK FR-002, FR-003, FR-005, FR-006 | BR-ACCT-001 | UAT-ACCT-001
 */
class AccountResolutionView
{
    /** The submitted mapping (field key => the FA bank account id). */
    public const FIELD_RESOLVED = 'resolved_bank_account';

    /** The submitted remember flags. */
    public const FIELD_REMEMBER = 'remember_mapping';

    /** The submitted detected identifier per field key. */
    public const FIELD_DETECTED = 'detected_account';

    /** The submit button that applies the resolution. */
    public const SUBMIT_PROCEED = 'resolve_accounts';

    /** The submit button that aborts the import. */
    public const SUBMIT_CANCEL = 'cancel_account_resolution';

    /** The session slot that keeps the pending resolution. */
    public const SESSION_KEY = 'bank_import_account_resolution';

    /**
     * Render the blocking resolution table.
     *
     * @param array<string, array<int, int>> $unresolved    The detected account id => the files it was detected in
     * @param array<int, array{id: int, number: string, name: string}> $bankAccounts The bank accounts the user can pick
     * @param array<int, string>             $fileNames     File index => uploaded file name
     * @return void
     * @BABOK FR-002, FR-003, FR-005, FR-006 | BR-ACCT-001 | UAT-ACCT-001
     */
    public function renderBlocking(array $unresolved, array $bankAccounts, array $fileNames): void
    {
        echo '<tr><td>';
        echo '<div style="background-color:#fff3cd;border:1px solid #ffc107;padding:15px;margin:10px 0;">';
        echo '<h3 style="color:#856404;margin-top:0;">' . _('Bank Account Resolution Required') . '</h3>';
        echo '<p>' . _("Some files contain detected account numbers that don't match any FrontAccounting bank account. Please choose which FA bank account to use.") . '</p>';

        echo '<table>';
        echo '<tr><th>' . _('File(s)') . '</th><th>' . _('Detected Account') . '</th><th>' . _('Use FA Bank Account') . '</th><th>' . _('Remember') . '</th></tr>';

        foreach ($unresolved as $detected => $fileIndexes) {
            $fieldKey = self::fieldKeyFor((string) $detected);
            $fileLabel = $this->labelForFiles($fileIndexes, $fileNames);
            $options = $this->optionsFor($bankAccounts);

            echo '<tr>';
            echo '<td>' . htmlspecialchars($fileLabel) . '</td>';
            echo '<td><code>' . htmlspecialchars((string) $detected) . '</code></td>';
            echo '<td>';
            self::select(self::FIELD_RESOLVED . '[' . $fieldKey . ']', $options);
            echo '</td>';
            echo '<td>';
            echo '<input type="checkbox" name="' . self::FIELD_REMEMBER . '[' . $fieldKey . ']" value="1" checked>';
            echo '</td>';
            echo '</tr>';
            echo '<input type="hidden" name="' . self::FIELD_DETECTED . '[' . $fieldKey . ']" value="' . htmlspecialchars((string) $detected) . '">';
        }

        echo '</table>';

        echo '<div style="margin-top:12px;">';
        echo '<button type="submit" name="' . self::SUBMIT_PROCEED . '" value="1" style="background-color:#0d6efd;color:white;padding:10px 20px;border:none;cursor:pointer;margin-right:10px;">'
            . _('Proceed') . '</button>';
        echo '<button type="submit" name="' . self::SUBMIT_CANCEL . '" value="1" style="background-color:#6c757d;color:white;padding:10px 20px;border:none;cursor:pointer;">'
            . _('Cancel') . '</button>';
        echo '</div>';

        echo '</div>';
        echo '</td></tr>';
    }

    /**
     * The bounded form field key of a detected account id.
     *
     * @param string $detected The detected account id
     * @return string A 12 character key, stable for the same input
     * @BABOK FR-005, FR-006 | BR-ACCT-001, BR-ACCT-003 | UAT-ACCT-001, UAT-ACCT-003
     */
    public static function fieldKeyFor(string $detected): string
    {
        return substr(sha1($detected), 0, 12);
    }

    /**
     * Collect the mapping the user submitted.
     *
     * @param array<int|string, mixed> $post The POST array
     * @return array<string, array{detected: string, selected: int, remember: bool}> The keyed decisions
     * @BABOK FR-002, FR-005, FR-006 | BR-ACCT-001, BR-ACCT-003 | UAT-ACCT-001, UAT-ACCT-003
     */
    public static function collectSubmittedMapping(array $post): array
    {
        $detected = isset($post[self::FIELD_DETECTED]) && is_array($post[self::FIELD_DETECTED]) ? $post[self::FIELD_DETECTED] : [];
        $resolved = isset($post[self::FIELD_RESOLVED]) && is_array($post[self::FIELD_RESOLVED]) ? $post[self::FIELD_RESOLVED] : [];
        $remember = isset($post[self::FIELD_REMEMBER]) && is_array($post[self::FIELD_REMEMBER]) ? $post[self::FIELD_REMEMBER] : [];

        $mapping = [];

        foreach ($detected as $fieldKey => $identifier) {
            $mapping[(string) $fieldKey] = [
                'detected' => trim((string) $identifier),
                'selected' => isset($resolved[$fieldKey]) ? (int) $resolved[$fieldKey] : 0,
                'remember' => !empty($remember[$fieldKey]),
            ];
        }

        return $mapping;
    }

    /**
     * The comma separated names of the files a detected id was found in.
     *
     * @param array<int, int>    $fileIndexes The file indexes
     * @param array<int, string> $fileNames   File index => uploaded file name
     * @return string The label of the files
     * @BABOK FR-002 | BR-ACCT-001 | UAT-ACCT-001
     */
    private function labelForFiles(array $fileIndexes, array $fileNames): string
    {
        $names = [];

        foreach ($fileIndexes as $fileIndex) {
            $names[] = isset($fileNames[$fileIndex]) ? $fileNames[$fileIndex] : ('#' . $fileIndex);
        }

        return implode(', ', $names);
    }

    /**
     * The select options of the bank accounts.
     *
     * @param array<int, array{id: int, number: string, name: string}> $bankAccounts The bank accounts
     * @return array<int, string> Bank account id => the option label
     * @BABOK FR-003 | BR-ACCT-001 | UAT-ACCT-001
     */
    private function optionsFor(array $bankAccounts): array
    {
        $options = [];

        foreach ($bankAccounts as $account) {
            $options[$account['id']] = $account['name'] . ' (' . $account['number'] . ')';
        }

        return $options;
    }

    /**
     * Render the bank account select of one row.
     *
     * @param string             $fieldName The select name
     * @param array<int, string> $options   Bank account id => label
     * @return void
     * @BABOK FR-003 | BR-ACCT-001 | UAT-ACCT-001
     */
    private static function select(string $fieldName, array $options): void
    {
        echo '<select name="' . htmlspecialchars($fieldName) . '">';
        foreach ($options as $value => $label) {
            echo '<option value="' . htmlspecialchars((string) $value) . '">' . htmlspecialchars($label) . '</option>';
        }
        echo '</select>';
    }
}
