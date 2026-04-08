<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :generic_fa_interface_model [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Defines generic_fa_interface_model behavior for this module.
 */
// Minimal test/dev shim for the external ksf_modules_common dependency.
// Provides the base model class that legacy module models extend.

declare(strict_types=1);

if (!class_exists('generic_fa_interface_model')) {
    class generic_fa_interface_model
    {
        /** @var string|null */
        protected $table_name;

        public function __construct(?string $table_name = null)
        {
            $this->table_name = $table_name;
        }

        public function get_table_name(): ?string
        {
            return $this->table_name;
        }

        // The real implementation provides CRUD helpers; for unit tests we keep
        // lightweight placeholders to avoid fatals when legacy code calls them.
        public function ensure_table(): void
        {
            // no-op
        }

        public function insert(array $data): int
        {
            return 0;
        }

        public function update(int $id, array $data): bool
        {
            return true;
        }

        public function delete(int $id): bool
        {
            return true;
        }

        public function get(int $id): ?array
        {
            return null;
        }

        public function all(): array
        {
            return [];
        }
    }
}
