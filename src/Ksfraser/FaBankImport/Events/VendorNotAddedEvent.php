<?php

namespace Ksfraser\FaBankImport\Events;

class VendorNotAddedEvent
{
    private int $vendorId;
    private string $timestamp;

    public function __construct(int $vendorId)
    {
        $this->vendorId = $vendorId;
        $this->timestamp = date('Y-m-d H:i:s');
        trigger_error("Could not create Supplier ID $vendorId", E_USER_NOTICE);
    }

    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
}
