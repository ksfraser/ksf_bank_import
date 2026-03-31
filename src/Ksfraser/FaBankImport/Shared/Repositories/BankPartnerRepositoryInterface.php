<?php
namespace Ksfraser\FaBankImport\Shared\Repositories;

use Ksfraser\FaBankImport\Shared\Entities\BankPartner;

/**
 * BankPartnerRepositoryInterface - Abstract interface for partner (counterparty) persistence
 * 
 * @package Ksfraser\FaBankImport\Shared\Repositories
 * @stable - Part of Shared Kernel API
 */
interface BankPartnerRepositoryInterface
{
    /**
     * Find partner by ID
     * 
     * @throws EntityNotFoundException
     */
    public function findById(int $id): BankPartner;

    /**
     * Find partners by FA partner ID
     * 
     * @return BankPartner[]
     */
    public function findByFAPartnerId(int $faPartnerId): array;

    /**
     * Find by bank code
     * 
     * @throws EntityNotFoundException
     */
    public function findByBankCode(string $bankCode): BankPartner;

    /**
     * Find by FA partner ID and type
     * 
     * @return BankPartner[]
     */
    public function findByTypeAndFAPartnerId(string $partnerType, int $faPartnerId): array;

    /**
     * Save partner (insert or update)
     */
    public function save(BankPartner $partner): void;

    /**
     * Delete partner
     */
    public function delete(int $id): void;

    /**
     * Check if partner exists
     */
    public function exists(int $faPartnerId, string $bankCode): bool;
}
