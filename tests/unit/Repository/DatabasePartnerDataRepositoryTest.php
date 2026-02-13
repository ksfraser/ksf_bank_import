<?php

namespace Tests\Unit\Repository;

use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;
use Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

class DatabasePartnerDataRepositoryTest extends TestCase
{
    public function testFindByPartnerUsesInjectedAdapterAndHydratesPartnerData(): void
    {
        $adapter = new class([
            new \TestDbResult([
                [
                    'partner_id' => 1,
                    'partner_type' => 2,
                    'partner_detail_id' => 0,
                    'data' => 'ACME',
                    'occurrence_count' => 3,
                ],
                [
                    'partner_id' => 1,
                    'partner_type' => 2,
                    'partner_detail_id' => 0,
                    'data' => 'BETA',
                    'occurrence_count' => 1,
                ],
            ]),
        ]) implements DbAdapterInterface {
            /** @var array<int, string> */
            public $queries = [];

            /** @var array<int, mixed> */
            private $results;

            public function __construct(array $results)
            {
                $this->results = $results;
            }

            public function query(string $sql, string $errorMsg = '')
            {
                $this->queries[] = $sql;
                return array_shift($this->results) ?? new \TestDbResult([]);
            }

            public function fetch($result)
            {
                if ($result instanceof \TestDbResult) {
                    return $result->fetch();
                }
                return false;
            }
        };

        $repo = new DatabasePartnerDataRepository($adapter);
        $items = $repo->findByPartner(1, 2);

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(PartnerData::class, $items);
        $this->assertStringContainsString(TB_PREF . 'bi_partners_data', $adapter->queries[0]);
    }
}
