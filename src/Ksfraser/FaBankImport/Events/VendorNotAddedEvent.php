<?php

namespace Ksfraser\FaBankImport\Events;

/**
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013

 */
class VendorNotAddedEvent
{
    private int $vendorId;
    private string $timestamp;

    /** * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
*/
    public function __construct(int $vendorId)
    {
        $this->vendorId = $vendorId;
        $this->timestamp = date('Y-m-d H:i:s');
        trigger_error("Could not create Supplier ID $vendorId", E_USER_NOTICE);
    }

    /** * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
*/
    public function getVendorId(): int
    {
        return $this->vendorId;
    }

    /** * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
 * @BABOK FR-PROC-013 | BR-PROC-006 | UAT-PROC-013
*/
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
}
