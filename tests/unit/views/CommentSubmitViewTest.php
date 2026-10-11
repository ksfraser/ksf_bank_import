<?php
/**
 * CommentSubmitView Test
 * 
 * Tests for the CommentSubmitView component that renders a comment input field
 * and submit button using HTML library classes.
 * 
 * @package Ksfraser
 * @subpackage Tests
 */

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;
use Ksfraser\Views\CommentSubmitView;

class CommentSubmitViewTest extends TestCase
{
    /**
     * Test that render() returns an HtmlFragment
     */
    public function testRenderReturnsHtmlFragment()
    {
        $data = [
            'id' => 123,
            'comment' => 'Test comment',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[123]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        $fragment = $view->render();
        
        $this->assertInstanceOf(\Ksfraser\HTML\HtmlFragment::class, $fragment);
    }
    
    /**
     * Test that display() outputs HTML
     */
    public function testDisplayOutputsHtml()
    {
        $data = [
            'id' => 456,
            'comment' => 'Another test',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[456]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        
        ob_start();
        $view->display();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Another test', $output);
    }
    
    /**
     * Test comment row contains correct input field
     */
    public function testCommentRowContainsInput()
    {
        $data = [
            'id' => 789,
            'comment' => 'My memo text',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[789]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        $html = $view->render()->getHtml();
        
        // Should contain comment input field
        $this->assertStringContainsString('name="comment_789"', $html);
        $this->assertStringContainsString('value="My memo text"', $html);
        $this->assertStringContainsString('Comment:', $html);
    }
    
    /**
     * Test submit button row is rendered correctly
     */
    public function testSubmitButtonRowRendered()
    {
        $data = [
            'id' => 321,
            'comment' => '',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[321]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        $html = $view->render()->getHtml();
        
        // Should contain submit button
        $this->assertStringContainsString('name="ProcessTransaction[321]"', $html);
        $this->assertStringContainsString('value="Process"', $html);
        $this->assertStringContainsString('type="submit"', $html);
    }
    
    /**
     * Test empty comment value
     */
    public function testEmptyCommentValue()
    {
        $data = [
            'id' => 111,
            'comment' => '',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[111]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        $html = $view->render()->getHtml();
        
        // Should have empty value attribute
        $this->assertStringContainsString('name="comment_111"', $html);
        $this->assertStringContainsString('value=""', $html);
    }
    
    /**
     * Test HTML special characters are escaped in comment
     */
    public function testHtmlEscapingInComment()
    {
        $data = [
            'id' => 222,
            'comment' => '<script>alert("XSS")</script>',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[222]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        $html = $view->render()->getHtml();
        
        // HTML should be escaped
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
    
    /**
     * Test that comment input has correct attributes
     */
    public function testCommentInputAttributes()
    {
        $data = [
            'id' => 333,
            'comment' => 'Test',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[333]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        $html = $view->render()->getHtml();
        
        // Input should have type='text'
        $this->assertStringContainsString('type="text"', $html);
        
        // Input should have name attribute
        $this->assertStringContainsString('name="comment_333"', $html);
        
        // Input should have size attribute based on comment length
        $this->assertMatchesRegularExpression('/size="\d+"/', $html);
    }
    
    /**
     * Test that submit button has default class
     */
    public function testSubmitButtonHasDefaultClass()
    {
        $data = [
            'id' => 444,
            'comment' => '',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[444]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        $html = $view->render()->getHtml();
        
        // Button should have 'default' class
        $this->assertStringContainsString('class="default"', $html);
    }
    
    /**
     * Test with localized labels (simulating _() function)
     */
    public function testLocalizedLabels()
    {
        $data = [
            'id' => 555,
            'comment' => 'Comentario',
            'comment_label' => 'Comentario:', // Spanish
            'button_name' => 'ProcessTransaction[555]',
            'button_label' => 'Procesar' // Spanish
        ];
        
        $view = new CommentSubmitView($data);
        $html = $view->render()->getHtml();
        
        $this->assertStringContainsString('Comentario:', $html);
        $this->assertStringContainsString('value="Procesar"', $html);
    }
    
    /**
     * Test composability - render() can be added to another fragment
     */
    public function testComposability()
    {
        $data = [
            'id' => 666,
            'comment' => 'Test',
            'comment_label' => 'Comment:',
            'button_name' => 'ProcessTransaction[666]',
            'button_label' => 'Process'
        ];
        
        $view = new CommentSubmitView($data);
        $fragment = $view->render();
        
        // Should be able to add to another fragment
        $parentFragment = new \Ksfraser\HTML\HtmlFragment();
        $parentFragment->addChild($fragment);
        
        $html = $parentFragment->getHtml();
        $this->assertStringContainsString('comment_666', $html);
        $this->assertStringContainsString('ProcessTransaction[666]', $html);
    }
}
