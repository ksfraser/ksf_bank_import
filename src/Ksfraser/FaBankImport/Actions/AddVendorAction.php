<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :AddVendorAction [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for AddVendorAction.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Actions;

final class AddVendorAction
{
    /** @param array<string,mixed> $post */
    public function supports(array $post): bool
    {
        return isset($post['AddVendor']);
    }

    /**
     * @param array<string,mixed> $post
     * @param object $controller
     */
    public function execute(array $post, $controller): bool
    {
        if (!$this->supports($post) || !is_object($controller) || !method_exists($controller, 'addVendor')) {
            return false;
        }

        $controller->addVendor();
        return true;
    }
}
