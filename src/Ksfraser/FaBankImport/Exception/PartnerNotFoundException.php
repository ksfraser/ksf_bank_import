<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004

 * PartnerNotFoundException - Thrown when a partner cannot be found
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerNotFoundException extends PartnerException
{
    /** * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
 * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
*/
    public static function byId(int $id): self
    {
        return new self(
            sprintf('Partner with ID %d not found', $id)
        );
    }

    /** * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
 * @BABOK FR-VAL-005 | BR-VAL-003 | UAT-VAL-004
*/
    public static function byName(string $name): self
    {
        return new self(
            sprintf('Partner with name "%s" not found', $name)
        );
    }
}
