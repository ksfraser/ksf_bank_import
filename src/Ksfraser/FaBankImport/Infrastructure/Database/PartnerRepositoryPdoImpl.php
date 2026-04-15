<?php

namespace Ksfraser\FaBankImport\Infrastructure\Database;

use PDO;
use PDOStatement;
use Ksfraser\FaBankImport\Contracts\PartnerRepository;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * Partner Repository - PDO Implementation
 * 
 * Persists and retrieves partner entities using PDO with parameterized queries.
 * All queries use placeholders to prevent SQL injection.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerRepositoryPdoImpl implements PartnerRepository
{
    private const TABLE = 'bi_partners_data';
    
    public function __construct(private readonly PDO $pdo)
    {
    }
    
    /**
     * Get partner by ID
     */
    public function getById(int $id): ?PartnerEntity
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM " . self::TABLE . " WHERE id = ?"
        );
        $stmt->execute([$id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->rowToEntity($row) : null;
    }
    
    /**
     * Get partner by name and type
     */
    public function getByName(string $name, PartnerType $type): ?PartnerEntity
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM " . self::TABLE . " WHERE name = ? AND partner_type = ?"
        );
        $stmt->execute([$name, $type->value]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->rowToEntity($row) : null;
    }
    
    /**
     * Search by pattern (LIKE)
     */
    public function searchByPattern(string $pattern, ?PartnerType $type = null): array
    {
        $sql = "SELECT * FROM " . self::TABLE . " WHERE name LIKE ?";
        $params = ["%{$pattern}%"];
        
        if ($type !== null) {
            $sql .= " AND partner_type = ?";
            $params[] = $type->value;
        }
        
        $sql .= " ORDER BY occurrence_count DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return array_map([$this, 'rowToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Get all partners of a type
     */
    public function getByType(PartnerType $type): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM " . self::TABLE . " WHERE partner_type = ? ORDER BY name"
        );
        $stmt->execute([$type->value]);
        
        return array_map([$this, 'rowToEntity'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    
    /**
     * Create new partner
     */
    public function create(PartnerEntity $partner): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO " . self::TABLE . 
            " (name, partner_type, occurrence_count, last_matched_ts) " .
            "VALUES (?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $partner->name(),
            $partner->type()->value,
            $partner->occurrenceCount(),
            $partner->lastMatchedTs() ? $partner->lastMatchedTs()->format('Y-m-d H:i:s') : null
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }
    
    /**
     * Update existing partner
     */
    public function update(PartnerEntity $partner): void
    {
        if ($partner->id() === 0) {
            throw new \InvalidArgumentException(
                'Cannot update partner with id = 0. Use create() for new partners.'
            );
        }
        
        $stmt = $this->pdo->prepare(
            "UPDATE " . self::TABLE . 
            " SET name = ?, partner_type = ?, occurrence_count = ?, last_matched_ts = ? " .
            "WHERE id = ?"
        );
        
        $stmt->execute([
            $partner->name(),
            $partner->type()->value,
            $partner->occurrenceCount(),
            $partner->lastMatchedTs() ? $partner->lastMatchedTs()->format('Y-m-d H:i:s') : null,
            $partner->id()
        ]);
    }
    
    /**
     * Delete partner
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM " . self::TABLE . " WHERE id = ?"
        );
        $stmt->execute([$id]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Check existence
     */
    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM " . self::TABLE . " WHERE id = ?"
        );
        $stmt->execute([$id]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Convert database row to entity
     */
    private function rowToEntity(array $row): PartnerEntity
    {
        return new PartnerEntity(
            id: (int)$row['id'],
            name: $row['name'],
            type: PartnerType::from($row['partner_type']),
            occurrenceCount: (int)($row['occurrence_count'] ?? 0),
            lastMatchedTs: $row['last_matched_ts'] 
                ? new \DateTime($row['last_matched_ts'])
                : null
        );
    }
}
