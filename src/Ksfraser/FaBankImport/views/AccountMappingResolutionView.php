<?php
namespace Ksfraser\FaBankImport\Views;

class AccountMappingResolutionView
{
    /**
     * Render the account resolution UI.
     *
     * @param array $unresolved
     * @param array $uploaded_filenames
     * @param string $parserType
     * @param int|null $bankAccountId
     */
    public static function render(array $unresolved, array $uploaded_filenames, string $parserType, $bankAccountId = null)
    {
        start_table(TABLESTYLE);
        start_row();
        echo "<td width=100%><pre>\n";
        echo '<tr><td>';
        echo '<div style="background-color:#fff3cd;border:1px solid #ffc107;padding:15px;margin:10px 0;">';
        echo '<h3 style="color:#856404;margin-top:0;">' . _("Bank Account Resolution Required") . '</h3>';
        echo '<p>' . _("Some files contain detected account numbers that don't match any FrontAccounting bank account. Please choose which FA bank account to use.") . '</p>';
        echo '<form method="post">';
        echo '<input type="hidden" name="parser" value="' . htmlspecialchars((string)$parserType) . '">';
        if ($bankAccountId !== null) {
            echo '<input type="hidden" name="bank_account" value="' . htmlspecialchars((string)$bankAccountId) . '">';
        }
        echo '<table class="tablestyle" style="width:100%;">';
        echo '<tr><th>' . _("File(s)") . '</th><th>' . _("Detected Account") . '</th><th>' . _("Use FA Bank Account") . '</th><th>' . _("Remember") . '</th></tr>';
        foreach ($unresolved as $detected => $fileMap) {
            $detKey = substr(sha1($detected), 0, 12);
            $fileNames = [];
            foreach (array_keys($fileMap) as $fileIndex) {
                $fileNames[] = $uploaded_filenames[$fileIndex] ?? ('#' . $fileIndex);
            }
            $fileLabel = htmlspecialchars(implode(', ', $fileNames));
            echo '<tr>';
            echo '<td>' . $fileLabel . '</td>';
            echo '<td><code>' . htmlspecialchars($detected) . '</code></td>';
            echo '<td>';
            // bank_accounts_list(name, selected_id, submit_on_change, spec_option)
            echo bank_accounts_list('resolved_bank_account[' . $detKey . ']', null, false, false);
            echo '</td>';
            echo '<td style="text-align:center;">'
                . '<input type="checkbox" name="remember_mapping[' . $detKey . ']" value="1" checked>'
                . '</td>';
            echo '</tr>';
            // include original detected value for this row
            echo '<input type="hidden" name="detected_account[' . $detKey . ']" value="' . htmlspecialchars($detected) . '">';
        }
        echo '</table>';
        echo '<div style="margin-top:12px;">';
        echo '<button type="submit" name="resolve_accounts" value="1" style="background-color:#0d6efd;color:white;padding:10px 20px;border:none;cursor:pointer;margin-right:10px;">'
            . _("Proceed")
            . '</button>';
        echo '<button type="submit" name="cancel_account_resolution" value="1" style="background-color:#6c757d;color:white;padding:10px 20px;border:none;cursor:pointer;">'
            . _("Cancel")
            . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</td></tr>';
        echo '</pre></td>';
        end_row();
        end_table(1);
    }
}
