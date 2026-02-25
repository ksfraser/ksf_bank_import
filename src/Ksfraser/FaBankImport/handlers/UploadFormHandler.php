<?php
namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\DTO\UploadFormDTO;
use Ksfraser\FaBankImport\Services\DefaultParserResolver;
use Ksfraser\FaBankImport\Services\ParserRegistry;

class UploadFormHandler {
    /**
     * @param array $request
     * @return UploadFormDTO
     */
    public function handle($request): UploadFormDTO {
        $registry = new ParserRegistry();
        $parsers = $registry->getParsersArray();

        // Determine user context
        $username = isset($request['username']) ? $request['username'] : (isset($_SESSION['wa_current_user']->username) ? $_SESSION['wa_current_user']->username : null);
        $requestedParser = isset($request['parser']) ? $request['parser'] : null;

        $resolver = new DefaultParserResolver();
        $selectedParser = $resolver->resolve($parsers, $username, $requestedParser);

        return new UploadFormDTO($parsers, $selectedParser);
    }
}
