<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :DbAdapterInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for DbAdapterInterface.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface as ModulesDaoDbAdapterInterface;

interface DbAdapterInterface extends ModulesDaoDbAdapterInterface
{
}
