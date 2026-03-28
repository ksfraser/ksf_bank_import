<?php

namespace Ksfraser\FaBankImport\Import\Services;

/**
 * Service for applying transaction state to the bank import controller.
 * 
 * Centralizes the scattered $bi_controller->set() calls and ensures
 * consistent state management throughout transaction processing.
 */
class TransactionStateManager
{
    /**
     * @var object Bank import controller instance
     */
    private object $controller;

    /**
     * Create a new state manager for the given controller.
     *
     * @param object $controller Bank import controller instance
     */
    public function __construct(object $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Apply full transaction state to controller.
     *
     * @param int $transactionId
     * @param string $partnerId
     * @param array $transaction Transaction data
     * @param array $bankAccount Bank account data
     * @param float $charge Calculated charge amount
     * @return void
     */
    public function applyTransactionState(
        int $transactionId,
        string $partnerId,
        array $transaction,
        array $bankAccount,
        float $charge = 0.0
    ): void {
        $this->controller->set('tid', $transactionId);
        $this->controller->set('partnerId', $partnerId);
        $this->controller->set('trz', $transaction);
        $this->controller->set('our_account', $bankAccount);
        $this->controller->set('charge', $charge);
    }

    /**
     * Set transaction ID in controller state.
     *
     * @param int $transactionId
     * @return $this
     */
    public function setTransaction(int $transactionId): self
    {
        $this->controller->set('tid', $transactionId);
        return $this;
    }

    /**
     * Set partner ID and type in controller state.
     *
     * @param string $partnerId
     * @param string|null $partnerType
     * @return $this
     */
    public function setPartner(string $partnerId, ?string $partnerType = null): self
    {
        $this->controller->set('partnerId', $partnerId);
        if ($partnerType !== null) {
            $this->controller->set('partnerType', $partnerType);
        }
        return $this;
    }

    /**
     * Set transaction data in controller state.
     *
     * @param array $transaction
     * @return $this
     */
    public function setTransactionData(array $transaction): self
    {
        $this->controller->set('trz', $transaction);
        return $this;
    }

    /**
     * Set bank account in controller state.
     *
     * @param array $bankAccount
     * @return $this
     */
    public function setBankAccount(array $bankAccount): self
    {
        $this->controller->set('our_account', $bankAccount);
        return $this;
    }

    /**
     * Set charge amount in controller state.
     *
     * @param float $charge
     * @return $this
     */
    public function setCharge(float $charge): self
    {
        $this->controller->set('charge', $charge);
        return $this;
    }

    /**
     * Clear transaction-specific state.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->controller->set('tid', null);
        $this->controller->set('partnerId', null);
        $this->controller->set('trz', []);
        $this->controller->set('our_account', []);
        $this->controller->set('charge', 0.0);
        $this->controller->set('partnerType', null);
    }

    /**
     * Get current state as array.
     *
     * @return array
     */
    public function getState(): array
    {
        return [
            'tid' => $this->controller->get('tid') ?? null,
            'partnerId' => $this->controller->get('partnerId') ?? null,
            'trz' => $this->controller->get('trz') ?? [],
            'our_account' => $this->controller->get('our_account') ?? [],
            'charge' => $this->controller->get('charge') ?? 0.0,
            'partnerType' => $this->controller->get('partnerType') ?? null,
        ];
    }
}
