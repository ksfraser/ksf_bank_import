<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exception;

/**
 * @BABOK RTM: FR-050 / ARCH-003; file: Exception/PartnerNotFoundException.php; coverage: docs/TestCases/ (file rollup)
 * PartnerNotFoundException - Thrown when a partner cannot be found
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerNotFoundException extends PartnerException
{
    /** @BABOK FR-050 / ARCH-003 — method: byId */
    public static function byId(int $id): self
    {
        return new self(
            sprintf('Partner with ID %d not found', $id)
        );
    }

    /** @BABOK FR-050 / ARCH-003 — method: byName */
    public static function byName(string $name): self
    {
        return new self(
            sprintf('Partner with name "%s" not found', $name)
        );
    }
}
