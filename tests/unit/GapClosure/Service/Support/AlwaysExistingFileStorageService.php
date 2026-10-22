<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Service\Support;

use Ksfraser\FaBankImport\Service\FileStorageService;

/**
 * A file storage service that believes every file exists.
 *
 * The service reports a failed read, a failed size lookup and a failed
 * modification time lookup as exceptions. Those failures cannot be
 * provoked with a real file, because the service only reaches them once
 * the file has been found: the double answers the "does it exist" question
 * affirmatively and the call that follows then fails.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Service\Support
 * @license    MIT
 */
final class AlwaysExistingFileStorageService extends FileStorageService
{
    /**
     * @inheritDoc
     */
    public function exists(string $filePath): bool
    {
        return true;
    }
}
