<?php

namespace Ksfraser\FaBankImport\Repository;

use PDO;
use RuntimeException;

/**
 * @BABOK CLASS ROLLUP: PdoPartnerRepository — 11 methods; FR-008 (11)
 * @BABOK RTM: FR-008 / ARCH-001; file: Repository/PdoPartnerRepository.php; coverage: docs/TestCases/ (file rollup)
 * PDO-based implementation of PartnerRepository
 *
 * Provides CRUD operations for partner data using PDO prepared statements
 * and supports the bi_partners_data table schema.
 *
 * @package Ksfraser\FaBankImport\Infrastructure\Persistence
 */
final class PdoPartnerRepository implements PartnerRepository
{
    private readonly PDO $pdo;
    private readonly string $tableName;

    /** @BABOK FR-008 / ARCH-001 — method: __construct */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->tableName = 'bi_partners_data';
    }

    /** @BABOK FR-008 / ARCH-001 — method: create */
    public function create(array $data): int
    {
        $required = ['name', 'partner_type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new RuntimeException("Required field '{$field}' missing");
            }
        }

        $sql = "INSERT INTO {$this->tableName} 
                (name, partner_type, occurrence_count, last_matched_ts, created_at, updated_at)
                VALUES (:name, :partner_type, :occurrence_count, :last_matched_ts, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        $stmt = $this->pdo->prepare($sql);
        
        $occurrence_count = $data['occurrence_count'] ?? 0;
        $last_matched_ts = $data['last_matched_ts'] ?? null;

        $stmt->execute([
            ':name' => $data['name'],
            ':partner_type' => $data['partner_type'],
            ':occurrence_count' => $occurrence_count,
            ':last_matched_ts' => $last_matched_ts,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @BABOK FR-008 / ARCH-001 — method: read */
    public function read(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /** @BABOK FR-008 / ARCH-001 — method: update */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $allowedFields = ['name', 'partner_type', 'occurrence_count', 'last_matched_ts'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields, true)) {
                $updates[] = "{$field} = :{$field}";
                $params[":{$field}"] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        $updates[] = "updated_at = CURRENT_TIMESTAMP";

        $sql = "UPDATE {$this->tableName} SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /** @BABOK FR-008 / ARCH-001 — method: delete */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /** @BABOK FR-008 / ARCH-001 — method: findByName */
    public function findByName(string $name, ?string $partnerType = null): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE name LIKE :name";
        $params = [':name' => "%{$name}%"];

        if ($partnerType !== null) {
            $sql .= " AND partner_type = :partner_type";
            $params[':partner_type'] = $partnerType;
        }

        $sql .= " ORDER BY occurrence_count DESC, name ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @BABOK FR-008 / ARCH-001 — method: findByType */
    public function findByType(string $partnerType): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE partner_type = :partner_type 
                ORDER BY occurrence_count DESC, name ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':partner_type' => $partnerType]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @BABOK FR-008 / ARCH-001 — method: findAll */
    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @BABOK FR-008 / ARCH-001 — method: count */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->tableName}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['cnt'] ?? 0);
    }

    /** @BABOK FR-008 / ARCH-001 — method: incrementOccurrenceCount */
    public function incrementOccurrenceCount(int $id): bool
    {
        $sql = "UPDATE {$this->tableName} 
                SET occurrence_count = occurrence_count + 1,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /** @BABOK FR-008 / ARCH-001 — method: updateLastMatched */
    public function updateLastMatched(int $id, ?string $timestamp = null): bool
    {
        $timestamp = $timestamp ?? date('Y-m-d H:i:s');

        $sql = "UPDATE {$this->tableName} 
                SET last_matched_ts = :timestamp,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':timestamp' => $timestamp,
        ]);

        return $stmt->rowCount() > 0;
    }
}
