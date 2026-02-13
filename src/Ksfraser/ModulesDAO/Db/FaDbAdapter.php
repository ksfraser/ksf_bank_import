<?php

declare(strict_types=1);

namespace Ksfraser\ModulesDAO\Db;

final class FaDbAdapter implements DbAdapterInterface
{
    /** @inheritDoc */
    public function query(string $sql, string $errorMsg = '')
    {
        return db_query($sql, $errorMsg);
    }

    /** @inheritDoc */
    public function fetch($result)
    {
        return db_fetch($result);
    }
}
