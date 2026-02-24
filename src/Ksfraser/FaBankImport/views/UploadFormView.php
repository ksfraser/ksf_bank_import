<?php
namespace Ksfraser\FaBankImport\Views;

class UploadFormView
{
    /**
     * Render the upload form.
     * @param array $parsers List of available parsers
     * @param string $selectedParser
     * @param string $error Optional error message
     */
    public static function render(array $parsers, string $selectedParser, string $error = ''): void
    {
        if ($error) {
            echo "<div class='error'>" . htmlspecialchars($error) . "</div>";
        }
        echo "<form method='post' enctype='multipart/form-data'>";
        echo "<input type='hidden' name='state' value='parse_upload' />";
        echo "<table class='table'>";
        echo "<tr><th>Select File(s) and type</th><th></th></tr>";
        echo "<tr><td>Format:</td><td>";
        echo $this->renderParserDropdown($parsers, $selectedParser);
        // Link to parser config admin screen (permission check should be in controller)
        echo ' <a href="admin_parsers.php" target="_blank" style="margin-left:1em;font-size:0.9em">Configure Parsers</a>';
        echo "</td></tr>";
        echo "<tr><td>Bank Account</td><td><span class='smalltext'>Determined from file (per statement) using saved account mappings.</span></td></tr>";
        echo "<tr><td>Files</td><td><input type='file' name='files[]' multiple /></td></tr>";
        echo "<tr><td>If duplicates are detected</td><td><label><input type='checkbox' name='force_upload_all' value='1'> Upload anyway (force re-upload)</label><br><span class='smalltext'>When checked, duplicate warnings will be bypassed for all selected files.</span></td></tr>";
        echo "<tr><td class='label'>Upload</td><td><input type='submit' name='upload' value='Upload' /></td></tr>";
        echo "</table>";
        echo "</form>";
    }
}
