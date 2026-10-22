<?php

namespace Ksfraser\FaBankImport;

use Ksfraser\HTML\Composites\LabelRowBase;
if ( !class_exists( LabelRowBase::class ) && file_exists( __DIR__ . '/../../../HTML/Composites/LabelRowBase.php' ) )
{
	require_once( __DIR__ . '/../../../HTML/Composites/LabelRowBase.php' );
}

/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
 */
class TransType extends LabelRowBase
{
	/**
 * @BABOK FR-PROC-001 | BR-PROC-001 | UAT-PROC-001
	 */
	function __construct( $bi_lineitem )
	{
		switch( $bi_lineitem->transactionDC )
		{
			case 'C':
				$label = "Credit";
			break;
			case 'B':
				$label = "Bank Transfer";
			break;
			case 'D':
			default:
				$label = "Debit";
			break;
		}
                $this->label = "Trans Type:";
                $this->data = $label;
                parent::__construct( "" );
	}
}
