<?php

namespace Ksfraser\FaBankImport\Request;

use Ksfraser\Superglobals\PostParameterProvider as SuperglobalsPostParameterProvider;

/**
 * Adapter that wraps PostParameterProvider to implement the local ParameterProvider interface.
 */
class PostParameterProviderAdapter implements ParameterProvider
{
    private $provider;

    public function __construct(SuperglobalsPostParameterProvider $provider)
    {
        $this->provider = $provider;
    }

    public function get(string $key, $default = null)
    {
        return $this->provider->get($key) ?? $default;
    }
}
