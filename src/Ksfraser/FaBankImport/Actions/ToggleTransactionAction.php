<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ToggleTransactionAction [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for ToggleTransactionAction.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Actions;

final class ToggleTransactionAction
{
    /** @param array<string,mixed> $post */
    public function supports(array $post): bool
    {
        return isset($post['ToggleTransaction']);
    }

    /**
     * @param array<string,mixed> $post
     * @param object $controller
     */
    public function execute(array $post, $controller): bool
    {
        if (!$this->supports($post) || !is_object($controller) || !method_exists($controller, 'toggleDebitCredit')) {
            return false;
        }

        $controller->toggleDebitCredit();

        if (function_exists('display_notification')) {
            display_notification(__LINE__ . "::" . print_r($post, true));
        }

        return true;
    }
}
