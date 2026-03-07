<?php
namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\DTO\UploadFormDTO;
use Ksfraser\FaBankImport\Services\DefaultParserResolver;
use Ksfraser\FaBankImport\Services\ParserRegistry;
use Ksfraser\FA\Auth\UserSession;
use Ksfraser\Superglobals\PostParameterProvider;
use Ksfraser\FaBankImport\Request\ParserSelector;

class UploadFormHandler {
    /**
     * @param array $request
     * @return UploadFormDTO
     */
    public function handle($request): UploadFormDTO {
        $registry = new ParserRegistry();
        $parsers = $registry->getParsersArray();

        // Determine user and request context
        $username = UserSession::getCurrentUsername();
        $parameterProvider = new PostParameterProvider();
        $parserSelector = new ParserSelector($parameterProvider, $registry);
        $requestedParser = $parserSelector->getSelectedParser();

        $resolver = new DefaultParserResolver();
        $selectedParser = $resolver->resolve($parsers, $username, $requestedParser);

        return new UploadFormDTO($parsers, $selectedParser);
    }
}
