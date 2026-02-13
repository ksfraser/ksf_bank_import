<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Actions;

final class AddCustomerAction
{
    /** @param array<string,mixed> $post */
    public function supports(array $post): bool
    {
        return isset($post['AddCustomer']);
    }

    /**
     * @param array<string,mixed> $post
     * @param object $controller
     */
    public function execute(array $post, $controller): bool
    {
        if (!$this->supports($post) || !is_object($controller) || !method_exists($controller, 'addCustomer')) {
            return false;
        }

        $controller->addCustomer();
        return true;
    }
}
