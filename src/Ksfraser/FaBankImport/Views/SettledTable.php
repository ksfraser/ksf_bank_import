<?php

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\Composites\LabelRowBase;
use Ksfraser\HTML\Composites\HTML_TABLE;

/**
 * @BABOK FR-PROC-001, FR-PROC-010, FR-PROC-011 | BR-PROC-001 | UAT-PROC-001
 */

/**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
 */

abstract class SettledTable  implements HtmlElementInterface
{
        protected $table;
        /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
         */
        function __construct( $transaction )
        {
                $this->table = $table = new HTML_TABLE( null, 100 );
                //$table->appendRow( new OperationPayment() );
        }
        /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
         */
        function toHtml(): void
        {
                $this->table->toHtml();
        }
        /**
 * @BABOK FR-PROC-010 | BR-PROC-001 | UAT-PROC-001
         */
        function getHtml(): string
        {
                return $this->table->getHtml();
        }
}

/**
 * @BABOK FR-PROC-004 | BR-PROC-001 | UAT-PROC-001
 */
