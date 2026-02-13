<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Actions;

final class UnsetTransactionAction
{
    /** @param array<string,mixed> $post */
    public function supports(array $post): bool
    {
        return isset($post['UnsetTrans']);
    }

    /**
     * @param array<string,mixed> $post
     * @param object $controller
     */
    public function execute(array $post, $controller): bool
    {
        if (!$this->supports($post) || !is_object($controller) || !method_exists($controller, 'unsetTrans')) {
            return false;
        }

        $controller->unsetTrans();
        return true;
    }
}
