<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionProcessor [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionProcessor.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

/**
 * Backwards/compat namespace wrapper.
 *
 * Some tests and older code refer to TransactionProcessor under
 * Ksfraser\FaBankImport\Services. The concrete implementation lives at
 * Ksfraser\FaBankImport\TransactionProcessor.
 */
class TransactionProcessor extends \Ksfraser\FaBankImport\TransactionProcessor
{
}
