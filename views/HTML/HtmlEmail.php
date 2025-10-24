<?php

namespace Ksfraser\HTML\HTMLAtomic;

use Ksfraser\HTML\HtmlElementInterface;

/**//****************************
* Email Links
*
* Creates: <a href="mailto:email@example.com">LINK TEXT</a>
*
* Email addresses are validated and automatically prefixed with "mailto:"
* Extends HtmlA since email links are just specialized anchor tags
*
* USAGE EXAMPLES:
*
*   // Simple email link
*   $email = new HtmlEmail("info@company.com", "Contact Us");
*
*   // Email using address as text (null content)
*   $email = new HtmlEmail("support@example.com");
*
*   // Email with subject and body parameters
*   $email = new HtmlEmail("help@example.com", "Get Help");
*   $email->addParam("subject", "Support Request");
*   $email->addParam("body", "I need assistance with...");
*
*   // Email with cc and bcc
*   $email = new HtmlEmail("sales@company.com", "Email Sales");
*   $email->addParam("cc", "manager@company.com");
*   $email->addParam("bcc", "archive@company.com");
*
*   // Email with custom validation disabled
*   $email = new HtmlEmail("custom-format", "Contact", false);
*
* COMMON VALID CONTENT TYPES:
*   ✓ string - Auto-wrapped in HtmlString
*   ✓ null - Uses email address as link text
*   ✓ HtmlString - Escaped text content
*   ✓ HtmlRawString - Unescaped HTML content
*   ✓ HtmlImage - Image inside email link
*
* MAILTO PARAMETERS (use addParam or setParams):
*   - subject: Email subject line
*   - body: Email body text
*   - cc: Carbon copy addresses
*   - bcc: Blind carbon copy addresses
*
* VALIDATION:
*   By default, email addresses are validated using PHP's filter_var().
*   Set $validateEmail = false to disable validation for custom formats.
*/
class HtmlEmail extends HtmlA
{
	/**
	 * Create an email link
	 * 
	 * @param string $emailAddress The email address (without mailto:)
	 * @param HtmlElementInterface|string|null $linkContent The content to display. 
	 *        - HtmlElementInterface: any HTML element (text, image, etc.)
	 *        - string: will be wrapped in HtmlString
	 *        - null: email address will be used as link text
	 * @param bool $validateEmail Whether to validate the email format (default: true)
	 * @throws \Exception if email is invalid or content type is invalid
	 */
	function __construct( string $emailAddress, $linkContent = null, bool $validateEmail = true )
	{
		// Validate email address if requested
		if( $validateEmail && !filter_var( $emailAddress, FILTER_VALIDATE_EMAIL ) )
		{
			throw new \Exception( "Invalid email address: $emailAddress" );
		}
		
		// Build mailto URL
		$mailtoUrl = "mailto:" . $emailAddress;
		
		// If no content provided, use email address as link text
		if( $linkContent === null )
		{
			$linkContent = $emailAddress;
		}
		
		// Call parent HtmlA constructor - it handles string/HtmlElementInterface/null
		parent::__construct( $mailtoUrl, $linkContent );
	}
}
