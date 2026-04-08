<?php

namespace Ksfraser\HTML\Elements;

use Ksfraser\HTML\Elements\HtmlA;

/**
 * HtmlEmail: Anchor tag wrapper for email links
 * <a href="mailto:...">...</a>
 */
class HtmlEmail extends HtmlA
{
    /**
     * Set the email address and link text
     * Stores the address as HtmlString for templating/recursion
     * @param string $email
     * @param string|null $text
     * @return self
     */
    public function setAddress(string $email, ?string $text = null): self
    {
        $this->setHref($email);
        $this->setContent(new \Ksfraser\HTML\Elements\HtmlString($text ?? $email));
        return $this;
    }

    /**
     * Set href attribute for email link
     * Ensures mailto: is prepended
     * @param string $email
     * @return self
     */
    public function setHref(string $email): self
    {
        $mailto = (stripos($email, 'mailto:') === 0) ? $email : 'mailto:' . $email;
        return parent::setHref($mailto);
    }
    // Optionally validate email address
}