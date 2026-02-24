<?php
namespace Ksfraser\FaBankImport\Views;

class ParserConfigAdminView
{
    public function render($parsers)
    {
        echo '<h2>Parser Configuration</h2>';
        echo '<form method="post">';
        echo '<table class="tablestyle">';
        echo '<tr><th>Parser</th><th>Description</th><th>Enabled</th></tr>';
        foreach ($parsers as $pid => $pdata) {
            $checked = !empty($pdata['enabled']) ? 'checked' : '';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($pdata['name']) . '</td>';
            echo '<td>' . htmlspecialchars($pdata['description']) . '</td>';
            echo '<td><input type="checkbox" name="enable[' . htmlspecialchars($pid) . ']" value="1" ' . $checked . '></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<input type="submit" name="save_parsers" value="Save">';
        echo '</form>';
    }
}
