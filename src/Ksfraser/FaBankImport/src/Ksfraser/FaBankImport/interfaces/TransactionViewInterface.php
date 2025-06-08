<?php

namespace Ksfraser\FaBankImport\Interfaces;

interface TransactionViewInterface
{
    public function render(): string;
    public function renderActions(): string;
    public function addButton(string $type, array $params): void;
}
