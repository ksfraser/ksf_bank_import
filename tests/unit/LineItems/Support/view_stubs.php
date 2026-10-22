<?php

/**
 * Doubles for the legacy view classes under
 * src/Ksfraser/FaBankImport/views/.
 *
 * The views were written against an older HTML element contract: they
 * implement Ksfraser\HTML\HtmlElementInterface without declaring the return
 * types the current interface asks for. The interface is therefore declared
 * here without return types, which the current implementations are
 * compatible with.
 *
 * The classes also use a handful of collaborators unqualified, which resolves
 * to the Ksfraser\FaBankImport namespace; aliases point those names at the
 * shared implementations.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace {

    if (!interface_exists('HtmlElementInterface', false)) {
        /**
         * The legacy global HTML element contract.
         *
         * The global views only implement the output method, so the contract
         * only asks for that one.
         */
        interface HtmlElementInterface
        {
            /**
             * @return void
             */
            public function toHtml();
        }
    }

    if (!class_exists('HTML_ROW_LABEL', false)) {
        /**
         * The legacy global label row.
         */
        class HTML_ROW_LABEL
        {
            /** @var mixed */
            private $data;

            /** @var mixed */
            private $label;

            /**
             * @param mixed $data Row content.
             * @param mixed $label Row label.
             * @param int $width Label width.
             * @param string $class Label class.
             */
            public function __construct($data, $label, $width = 25, $class = 'label')
            {
                $this->data = $data;
                $this->label = $label;
            }

            /**
             * @return string
             */
            public function toHTML()
            {
                return '';
            }

            /**
             * @return string
             */
            public function getHTML()
            {
                return '';
            }
        }
    }
}

namespace Ksfraser\HTML {

    if (!interface_exists('Ksfraser\HTML\HtmlElementInterface', false)) {
        /**
         * The HTML element contract without return types.
         */
        interface HtmlElementInterface
        {
            /**
             * @return string
             */
            public function getHtml();

            /**
             * @return void
             */
            public function toHtml();
        }
    }
}

namespace Ksfraser\FaBankImport {

    if (!class_exists('Ksfraser\FaBankImport\HtmlBold', false)) {
        /**
         * Stand-in for the FA bold helper the settled view uses.
         */
        class HtmlBold implements \Ksfraser\HTML\HtmlElementInterface
        {
            /** @var mixed */
            private $text;

            /**
             * @param mixed $text Bold text.
             */
            public function __construct($text = '')
            {
                $this->text = $text;
            }

            /**
             * @return string
             */
            public function getHtml()
            {
                return '<b>' . $this->text . '</b>';
            }

            /**
             * @return void
             */
            public function toHtml()
            {
                echo $this->getHtml();
            }
        }
    }
}

namespace {

    if (!class_exists('HtmlString', false)) {
        /**
         * The global alias the legacy HTML_ROW view uses.
         *
         * The view imports the table row but not the string, so the string
         * is looked up in the global namespace. The alias is declared after
         * the HTML element contract, because the shared string class
         * implements that contract.
         */
        class HtmlString extends \Ksfraser\HTML\Elements\HtmlString
        {
        }
    }
}

namespace Ksfraser\FaBankImport {

    if (!class_exists('Ksfraser\FaBankImport\HTML_ROW_LABEL', false)) {
        /**
         * The label row the legacy views use.
         *
         * The views build the row in the Ksfraser\FaBankImport namespace,
         * where no label row exists. The stand-in accepts any content, so
         * that a view may hand it an object it does not expect.
         */
        class HTML_ROW_LABEL extends \Ksfraser\HTML\Composites\HTML_ROW_LABEL
        {
            /**
             * @param mixed $data Row content.
             * @param string $label Row label.
             * @param int|null $width Label width.
             * @param string|null $class Label class.
             */
            public function __construct($data, string $label, ?int $width = null, ?string $class = null)
            {
                if (!is_string($data) && !($data instanceof \Ksfraser\HTML\HtmlElementInterface)) {
                    $data = new \Ksfraser\HTML\Elements\HtmlString(print_r($data, true));
                }

                parent::__construct($data, $label, $width, $class);
            }
        }
    }
}
