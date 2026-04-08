<?php

namespace Ksfraser\FaBankImport\Repository;

use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;
use Ksfraser\FaBankImport\Domain\Exceptions\PartnerDataNotFoundException;
use RuntimeException;

/**
 * Database implementation of PartnerDataRepository
 *
 * Uses prepared statements for security and integrates with FrontAccounting's
 * database abstraction layer.
 *
 * @package Ksfraser\FaBankImport\Repository
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class DatabasePartnerDataRepository implements PartnerDataRepositoryInterface
{
    /**
     * @var string Table name (with TB_PREF)
     */
    private string $tableName;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!defined('TB_PREF')) {
            throw new RuntimeException('TB_PREF constant not defined');
        }
        $this->tableName = TB_PREF . 'bi_partners_data';
    }

    /**
     * {@inheritdoc}
     */
    public function find(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $data
    ): ?PartnerData {
        $sql = "SELECT * FROM `{$this->tableName}` 
                WHERE partner_id = ? 
                AND partner_type = ? 
                AND partner_detail_id = ? 
                AND data = ?
                LIMIT 1";

        $result = db_query($sql, [$partnerId, $partnerType, $partnerDetailId, $data]);

        if ($row = db_fetch($result)) {
            return PartnerData::fromArray($row);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByPartner(int $partnerId, ?int $partnerType = null): array
    {
        $sql = "SELECT * FROM `{$this->tableName}` 
                WHERE partner_id = ?";
        $params = [$partnerId];

        if ($partnerType !== null) {
            $sql .= " AND partner_type = ?";
            $params[] = $partnerType;
        }

        $sql .= " ORDER BY occurrence_count DESC, data ASC";

        $result = db_query($sql, $params);

        $partnerDataList = [];
        while ($row = db_fetch($result)) {
            $partnerDataList[] = PartnerData::fromArray($row);
        }

        return $partnerDataList;
    }

    /**
     * {@inheritdoc}
     */
    public function findByKeyword(string $keyword, ?int $partnerType = null): array
    {
        $sql = "SELECT * FROM `{$this->tableName}` 
                WHERE data LIKE ?";
        $params = ['%' . $this->escapeLike($keyword) . '%'];

        if ($partnerType !== null) {
            $sql .= " AND partner_type = ?";
            $params[] = $partnerType;
        }

        $sql .= " ORDER BY occurrence_count DESC, data ASC";

        $result = db_query($sql, $params);

        $partnerDataList = [];
        while ($row = db_fetch($result)) {
            $partnerDataList[] = PartnerData::fromArray($row);
        }

        return $partnerDataList;
    }

    /**
     * {@inheritdoc}
     */
    public function searchByKeywords(
        array $keywords,
        ?int $partnerType = null,
        int $limit = 10
    ): array {
        if (empty($keywords)) {
            return [];
        }

        // Build LIKE conditions for each keyword
        $likeClauses = [];
        $params = [];
        foreach ($keywords as $keyword) {
            $likeClauses[] = "data LIKE ?";
            $params[] = '%' . $this->escapeLike($keyword) . '%';
        }

        $sql = "SELECT 
                    partner_id,
                    partner_type,
                    partner_detail_id,
                    data,
                    occurrence_count
                FROM `{$this->tableName}` 
                WHERE (" . implode(' OR ', $likeClauses) . ")";

        if ($partnerType !== null) {
            $sql .= " AND partner_type = ?";
            $params[] = $partnerType;
        }

        $result = db_query($sql, $params);

        // Group results by partner and calculate scores
        $partnerMatches = [];
        while ($row = db_fetch($result)) {
            $partnerKey = sprintf(
                '%d_%d_%d',
                $row['partner_id'],
                $row['partner_type'],
                $row['partner_detail_id']
            );

            if (!isset($partnerMatches[$partnerKey])) {
                $partnerMatches[$partnerKey] = [
                    'partner_id' => (int)$row['partner_id'],
                    'partner_type' => (int)$row['partner_type'],
                    'partner_detail_id' => (int)$row['partner_detail_id'],
                    'matched_keywords' => [],
                    'total_score' => 0,
                ];
            }

            // Check which keywords matched this data
            foreach ($keywords as $keyword) {
                if (stripos($row['data'], $keyword) !== false) {
                    if (!in_array($keyword, $partnerMatches[$partnerKey]['matched_keywords'])) {
                        $partnerMatches[$partnerKey]['matched_keywords'][] = $keyword;
                    }
                    $partnerMatches[$partnerKey]['total_score'] += (int)$row['occurrence_count'];
                }
            }
        }

        // Sort by number of matched keywords (desc), then by score (desc)
        usort($partnerMatches, function($a, $b) {
            $keywordCountCompare = count($b['matched_keywords']) - count($a['matched_keywords']);
            if ($keywordCountCompare !== 0) {
                return $keywordCountCompare;
            }
            return $b['total_score'] - $a['total_score'];
        });

        // Apply limit
        return array_slice($partnerMatches, 0, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function save(PartnerData $partnerData): bool
    {
        // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert
        $sql = "INSERT INTO `{$this->tableName}` 
                (partner_id, partner_type, partner_detail_id, data, occurrence_count)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE occurrence_count = ?";

        $result = db_query($sql, [
            $partnerData->getPartnerId(),
            $partnerData->getPartnerType(),
            $partnerData->getPartnerDetailId(),
            $partnerData->getData(),
            $partnerData->getOccurrenceCount(),
            $partnerData->getOccurrenceCount()
        ]);

        return $result !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $data
    ): bool {
        $sql = "DELETE FROM `{$this->tableName}` 
                WHERE partner_id = ? 
                AND partner_type = ? 
                AND partner_detail_id = ? 
                AND data = ?
                LIMIT 1";

        $result = db_query($sql, [$partnerId, $partnerType, $partnerDetailId, $data]);

        return $result !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByPartner(int $partnerId, ?int $partnerType = null): int
    {
        $sql = "DELETE FROM `{$this->tableName}` 
                WHERE partner_id = ?";
        $params = [$partnerId];

        if ($partnerType !== null) {
            $sql .= " AND partner_type = ?";
            $params[] = $partnerType;
        }

        $result = db_query($sql, $params);

        // Since we don't have db_num_affected_rows, return 1 for success, 0 for failure
        return $result !== false ? 1 : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementOccurrence(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $keyword,
        int $increment = 1
    ): bool {
        // Use INSERT ... ON DUPLICATE KEY UPDATE
        $sql = "INSERT INTO `{$this->tableName}` 
                (partner_id, partner_type, partner_detail_id, data, occurrence_count)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE occurrence_count = occurrence_count + ?";

        $result = db_query($sql, [
            $partnerId,
            $partnerType,
            $partnerDetailId,
            $keyword,
            $increment,
            $increment
        ]);

        return $result !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function count(?int $partnerType = null): int
    {
        $sql = "SELECT COUNT(*) as total FROM `{$this->tableName}`";
        $params = [];

        if ($partnerType !== null) {
            $sql .= " WHERE partner_type = ?";
            $params[] = $partnerType;
        }

        $result = db_query($sql, $params);
        $row = db_fetch($result);

        return (int)$row['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function exists(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId,
        string $data
    ): bool {
        return $this->find($partnerId, $partnerType, $partnerDetailId, $data) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTopKeywords(int $limit = 20, ?int $partnerType = null): array
    {
        $sql = "SELECT data, SUM(occurrence_count) as total_occurrences
                FROM `{$this->tableName}`";
        $params = [];

        if ($partnerType !== null) {
            $sql .= " WHERE partner_type = ?";
            $params[] = $partnerType;
        }

        $sql .= " GROUP BY data
                  ORDER BY total_occurrences DESC
                  LIMIT ?";
        $params[] = $limit;

        $result = db_query($sql, $params);

        $keywords = [];
        while ($row = db_fetch($result)) {
            $keywords[] = [
                'data' => $row['data'],
                'total_occurrences' => (int)$row['total_occurrences'],
            ];
        }

        return $keywords;
    }

    /**
     * Escape special characters in LIKE patterns
     *
     * Escapes %, _ and \ characters that have special meaning in LIKE clauses.
     *
     * @param string $value Value to escape
     *
     * @return string Escaped value
     */
    private function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }
}
