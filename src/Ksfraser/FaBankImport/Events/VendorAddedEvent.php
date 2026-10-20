<?php

namespace Ksfraser\FaBankImport\Events;

/**
 * @BABOK RTM: BR-003 / ARCH-004; file: Events/VendorAddedEvent.php; coverage: docs/TestCases/ (file rollup)
 */
class VendorAddedEvent
{
    private int $vendorId;
    private string $timestamp;

    /** @BABOK BR-003 / ARCH-004 — method: __construct */
    public function __construct(int $vendorId)
    {
        $this->vendorId = $vendorId;
        $this->timestamp = date('Y-m-d H:i:s');
        trigger_error("Created Supplier ID $vendorId", E_USER_NOTICE);
    }

    /** @BABOK BR-003 / ARCH-004 — method: getVendorId */
    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    /** @BABOK BR-003 / ARCH-004 — method: getTimestamp */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
}
