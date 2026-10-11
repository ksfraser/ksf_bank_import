<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Application\PhpSessionPendingSessionStore;
use PHPUnit\Framework\TestCase;

/**
 * TDD tests for PhpSessionPendingSessionStore.
 *
 * Each test runs in a separate process so that session_start() calls do not
 * interfere with each other or the rest of the suite.
 *
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Application\PhpSessionPendingSessionStore
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PhpSessionPendingSessionStoreTest extends TestCase
{
    // -----------------------------------------------------------------------
    // SR-REQ-001 — PDF upload workflow requires pending session state storage.
    // -----------------------------------------------------------------------

    /**
     * @testdox store() persists data; load() retrieves it from $_SESSION
     */
    public function testStoreAndLoad(): void
    {
        $store = new PhpSessionPendingSessionStore();
        $data  = ['ocr_id' => 42, 'statement_ocr' => 'foo'];

        $store->store($data);

        $this->assertSame($data, $store->load());
    }

    /**
     * @testdox load() returns null when nothing has been stored yet
     */
    public function testLoadReturnsNullWhenEmpty(): void
    {
        $store = new PhpSessionPendingSessionStore();

        $this->assertNull($store->load());
    }

    /**
     * @testdox clear() removes previously stored data; load() returns null afterwards
     */
    public function testClearRemovesStoredData(): void
    {
        $store = new PhpSessionPendingSessionStore();
        $store->store(['key' => 'value']);
        $store->clear();

        $this->assertNull($store->load());
    }

    /**
     * @testdox Two stores with different keys are isolated from each other
     */
    public function testCustomKeyIsolatesFromDefaultKey(): void
    {
        $default = new PhpSessionPendingSessionStore();
        $custom  = new PhpSessionPendingSessionStore('sr_other_key');

        $default->store(['which' => 'default']);
        $custom->store(['which' => 'custom']);

        $this->assertSame(['which' => 'default'], $default->load());
        $this->assertSame(['which' => 'custom'],  $custom->load());
    }

    /**
     * @testdox store() overwrites previously stored data
     */
    public function testStoreOverwritesPreviousData(): void
    {
        $store = new PhpSessionPendingSessionStore();
        $store->store(['v' => 1]);
        $store->store(['v' => 2]);

        $this->assertSame(['v' => 2], $store->load());
    }
}
