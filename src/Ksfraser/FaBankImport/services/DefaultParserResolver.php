<?php
namespace Ksfraser\FaBankImport\Services;

use Ksfraser\FaBankImport\Config\ConfigService;

class DefaultParserResolver
{
    /**
     * Determine the default parser for a user, company, or fallback.
     * 
     * We use QFX as a fallback default if it is enabled. Since we are 
     *   providing a QFX parser as part of the code this should be safe enough.
     * If QFX is disabled, we return NULL.
     *
     * @param array $parsers [parser_id => name]
     * @param string|null $username
     * @param string|null $requestedParser
     * @return string|null
     */
    public function resolve(array $parsers, ?string $username = null, ?string $requestedParser = null): ?string
    {
        $config = ConfigService::getInstance();

        // 1. If user submitted a parser, store as their default and return it
        if ($requestedParser && isset($parsers[$requestedParser])) {
            if ($username) {
                $config->set('user.' . $username . '.default_parser', $requestedParser, $username, 'Set user default parser');
            }
            return $requestedParser;
        }

        // 2. Try to get user's last used parser
        if ($username) {
            $userDefault = $config->get('user.' . $username . '.default_parser', null);
            if ($userDefault && isset($parsers[$userDefault])) {
                return $userDefault;
            }
        }

        // 3. Try to get company default parser
        $companyDefault = $config->get('company.default_parser', null);
        if ($companyDefault && isset($parsers[$companyDefault])) {
            return $companyDefault;
        }

        // 4. Fallback: use first available parser, or null if none
        $fallback = count($parsers) ? array_key_first($parsers) : null;

        // Only set company default to QFX if QFX is available
        if (!$companyDefault && isset($parsers['QFX'])) {
            $config->set('company.default_parser', 'QFX', $username ?: 'system', 'Set company default parser');
        }

        return $fallback;
    }
}
