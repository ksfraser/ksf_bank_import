<?php
use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Views\UploadFormView;

class UploadFormViewTest extends TestCase
{
    public function testGetHtmlContainsForm()
    {
        $view = new UploadFormView(['parser1' => 'Parser 1'], 'parser1');
        $html = $view->getHtml();
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('parser1', $html);
    }
}
