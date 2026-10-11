<?php

/**
 * URL Builder Utility
 *
 * Provides a fluent interface for building URLs with parameters and generating HTML anchor tags.
 * Replaces repetitive URL construction code with a reusable, testable component.
 *
 * @package    Ksfraser
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      October 19, 2025
 */

declare(strict_types=1);

namespace Ksfraser;

/**
 * URL Builder
 *
 * Fluent interface for constructing URLs and HTML anchor tags.
 *
 * Example usage:
 * ```php
 * $link = (new UrlBuilder('/banking/transaction_inquiry.php'))
 *     ->addParam('trans_no', 123)
 *     ->addParam('trans_type', ST_BANKPAYMENT)
 *     ->setText('View Transaction')
 *     ->addClass('btn btn-view')
 *     ->toHtml();
 * ```
 */
class UrlBuilder
{
    /**
     * @var string Base URL path
     */
    private $url;

    /**
     * @var array<string, mixed> URL parameters
     */
    private $params = [];

    /**
     * @var string Link text
     */
    private $text = '';

    /**
     * @var string|null CSS class attribute
     */
    private $class = null;

    /**
     * @var string|null Target attribute (_blank, _self, etc.)
     */
    private $target = null;

    /**
     * Constructor
     *
     * @param string $url Base URL path
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Add a URL parameter
     *
     * @param string $name Parameter name
     * @param mixed $value Parameter value (will be converted to string)
     * @return self For fluent interface
     */
    public function addParam(string $name, $value): self
    {
        // Convert boolean to 1/0
        if (is_bool($value)) {
            $value = $value ? 1 : 0;
        }
        
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Add multiple URL parameters at once
     *
     * @param array<string, mixed> $params Associative array of parameters
     * @return self For fluent interface
     */
    public function addParams(array $params): self
    {
        foreach ($params as $name => $value) {
            $this->addParam($name, $value);
        }
        return $this;
    }

    /**
     * Set link text
     *
     * @param string $text The text to display in the anchor tag
     * @return self For fluent interface
     */
    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Add CSS class(es) to anchor tag
     *
     * @param string $class CSS class name(s)
     * @return self For fluent interface
     */
    public function addClass(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Set target attribute
     *
     * @param string $target Target value (_blank, _self, _parent, _top)
     * @return self For fluent interface
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Build the complete URL with parameters
     *
     * @return string The complete URL
     */
    public function getUrl(): string
    {
        $url = $this->url;
        
        if (!empty($this->params)) {
            $queryString = http_build_query($this->params);
            $url .= '?' . $queryString;
        }
        
        return $url;
    }

    /**
     * Generate HTML anchor tag
     *
     * @return string Complete HTML anchor tag
     */
    public function toHtml(): string
    {
        $url = $this->getUrl();
        $attributes = ['href' => $url];
        
        if ($this->class !== null) {
            $attributes['class'] = $this->class;
        }
        
        if ($this->target !== null) {
            $attributes['target'] = $this->target;
        }
        
        // Build attributes string
        $attrString = '';
        foreach ($attributes as $name => $value) {
            $attrString .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        // HTML-escape the link text
        $escapedText = htmlspecialchars($this->text, ENT_QUOTES, 'UTF-8');
        
        return '<a' . $attrString . '>' . $escapedText . '</a>';
    }

    /**
     * Convert to string (alias for toHtml())
     *
     * @return string HTML anchor tag
     */
    public function __toString(): string
    {
        return $this->toHtml();
    }
}
