<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BiLineItemTdBuilderRefactorTest extends TestCase
{
    /** @var string */
    private $lineItemFile;
    /** @var string */
    private $leftBuilderFile;
    /** @var string */
    private $rightBuilderFile;
    /** @var string */
    private $operationBuilderFile;
    /** @var string */
    private $partnerBuilderFile;
    /** @var string */
    private $matchingBuilderFile;

    protected function setUp(): void
    {
        parent::setUp();
        $root = dirname(__DIR__, 2);
        $this->lineItemFile = $root . '/class.bi_lineitem.php';
        $this->leftBuilderFile = $root . '/src/Ksfraser/Views/LeftTdBuilder.php';
        $this->rightBuilderFile = $root . '/src/Ksfraser/Views/RightTdBuilder.php';
        $this->operationBuilderFile = $root . '/src/Ksfraser/Views/OperationTdBuilder.php';
        $this->partnerBuilderFile = $root . '/src/Ksfraser/Views/PartnerTdBuilder.php';
        $this->matchingBuilderFile = $root . '/src/Ksfraser/Views/MatchingTdBuilder.php';
    }

    public function test_right_content_fragment_method_exists(): void
    {
        $content = (string)file_get_contents($this->lineItemFile);
        $this->assertStringContainsString('function getRightContentFragment()', $content);
    }

    public function test_left_legacy_content_fragment_method_exists(): void
    {
        $content = (string)file_get_contents($this->lineItemFile);
        $this->assertStringContainsString('function getLeftLegacyContentFragment()', $content);
    }

    public function test_operation_partner_matching_fragment_methods_exist(): void
    {
        $content = (string)file_get_contents($this->lineItemFile);
        $this->assertStringContainsString('function getOperationContentFragment()', $content);
        $this->assertStringContainsString('function getPartnerContentFragment()', $content);
        $this->assertStringContainsString('function getMatchingContentFragment()', $content);
    }

    public function test_right_builder_build_accepts_fragment_not_callable(): void
    {
        $content = (string)file_get_contents($this->rightBuilderFile);
        $this->assertStringContainsString('public function build(HtmlFragment $contentFragment): HtmlTd', $content);
        $this->assertStringNotContainsString('callable $renderRightContent', $content);
    }

    public function test_left_builder_build_accepts_fragment_not_callable(): void
    {
        $content = (string)file_get_contents($this->leftBuilderFile);
        $this->assertStringContainsString('public function build(string $labelRowsHtml, HtmlFragment $contentFragment): HtmlTd', $content);
        $this->assertStringNotContainsString('callable $renderComplex', $content);
    }

    public function test_operation_builder_build_accepts_fragment_not_callable(): void
    {
        $content = (string)file_get_contents($this->operationBuilderFile);
        $this->assertStringContainsString('public function build(HtmlFragment $contentFragment): HtmlTd', $content);
        $this->assertStringNotContainsString('callable $renderSettled', $content);
    }

    public function test_partner_builder_build_accepts_fragment_not_callable(): void
    {
        $content = (string)file_get_contents($this->partnerBuilderFile);
        $this->assertStringContainsString('public function build(HtmlFragment $contentFragment): HtmlTd', $content);
        $this->assertStringNotContainsString('callable $renderPartnerSelector', $content);
    }

    public function test_matching_builder_build_accepts_fragment_not_callable(): void
    {
        $content = (string)file_get_contents($this->matchingBuilderFile);
        $this->assertStringContainsString('public function build(HtmlFragment $contentFragment): HtmlTd', $content);
        $this->assertStringNotContainsString('callable $renderMatching', $content);
    }
}
