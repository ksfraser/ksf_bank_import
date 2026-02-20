<?php
namespace Ksfraser\FaBankImport\Views;

class AccountResolutionErrorView
{
    /**
     * Render the error UI for missing account resolution session.
     */
    public static function render()
    {
        start_table(TABLESTYLE);
        start_row();
        echo "<td width=100%><pre>\n";
        display_error(_("No pending account resolution session found. Please upload the file(s) again."));
        echo "</pre></td>";
        end_row();
        end_table(1);
    }
}
