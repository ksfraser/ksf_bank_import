<?php
// Fix QuickEntryPartnerTypeViewTest to work with V2 (HtmlLabelRow return type)

$file = 'tests/unit/views/QuickEntryPartnerTypeViewTest.php';
$content = file_get_contents($file);

// Pattern 1: Fix testGetHtmlReturnsString to check for HtmlLabelRow
$content = preg_replace(
    '/public function testGetHtmlReturnsString\(\): void\s*\{.*?\$html = \$view->getHtml\(\);\s*\$this->assertIsString\(\$html\);\s*\$this->assertNotEmpty\(\$html\);\s*\}/s',
    'public function testGetHtmlReturnsString(): void
    {
        $view = new QuickEntryPartnerTypeView(
            1,
            \'C\',
            $this->dataProvider
        );
        
        $htmlObject = $view->getHtml();
        
        // V2: Returns HtmlLabelRow object, not string
        $this->assertInstanceOf(\Ksfraser\HTML\Composites\HtmlLabelRow::class, $htmlObject);
        
        // Can be converted to string
        $html = $htmlObject->getHtml();
        $this->assertIsString($html);
        $this->assertNotEmpty($html);
    }',
    $content
);

// Pattern 2: Add conversion after every $html = $view->getHtml();
$content = preg_replace(
    '/(\$html = \$view->getHtml\(\);)\s*\n\s*\n/m',
    '$1' . "\n        " . '$html = $html->getHtml(); // V2: Convert HtmlLabelRow to string' . "\n\n",
    $content
);

file_put_contents($file, $content);
echo "Fixed $file\n";
