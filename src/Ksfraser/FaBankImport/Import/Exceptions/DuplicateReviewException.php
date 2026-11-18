<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Exceptions;

use RuntimeException;

/**
 * Base exception for all duplicate review service errors
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
class DuplicateReviewException extends RuntimeException
{
}
