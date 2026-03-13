<?php

namespace Ksfraser\FaBankImport\Request;

/**
 * Interface for parameter providers (POST, GET, etc.)
 */
interface ParameterProvider
{
    /**
     * Get a parameter value.
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed Parameter value or default
     */
    public function get(string $key, $default = null);
}
