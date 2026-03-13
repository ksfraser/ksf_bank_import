<?php
namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\DTO\UploadFormDTO;

class UploadFormHandler {
    public function handle($request): UploadFormDTO {
        // Simulate getParsers() from legacy code
        $parsers = [];
        if (function_exists('getParsers')) {
            $_parsers = getParsers();
            foreach ($_parsers as $pid => $pdata) {
                $parsers[$pid] = $pdata['name'];
            }
        } else if (isset($request['parsers'])) {
            // For testability, allow injection
            $parsers = $request['parsers'];
        }

        // Determine selected parser
        $selectedParser = isset($request['parser']) && isset($parsers[$request['parser']])
            ? $request['parser']
            : (array_key_exists('QFX', $parsers) ? 'QFX' : (count($parsers) ? array_key_first($parsers) : null));

        return new UploadFormDTO($parsers, $selectedParser);
    }
}
