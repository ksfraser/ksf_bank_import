<?php
namespace Ksfraser\FaBankImport;

require_once __DIR__ . '/ImportStateMachine.php';
require_once __DIR__ . '/ImportSteps.php';

/**
 * Stub controller for orchestrating the bank import workflow.
 * Demonstrates state machine usage and step routing.
 */
class ImportController
{
    /**
     * Entry point for the import process.
     * @param array $request Simulated request/session data
     * @return void
     */
    public function handle(array &$request)
    {
        $currentStep = isset($request['import_step']) ? $request['import_step'] : ImportSteps::UPLOAD_FORM;
        $context = isset($request['import_context']) ? $request['import_context'] : [];

        switch ($currentStep) {
            case ImportSteps::UPLOAD_FORM:
                $handler = new \Ksfraser\FaBankImport\Handlers\UploadFormHandler();
                $dto = $handler->handle($request);
                $view = new \Ksfraser\FaBankImport\Views\UploadFormView($dto->parsers, $dto->selectedParser);
                $view->toHtml();
                break;
            case ImportSteps::PARSE_FILES:
                $handler = new \Ksfraser\FaBankImport\Handlers\ParseFilesHandler();
                $dto = $handler->handle($request);
                // Render parse files view (to be implemented)
                echo "<pre>Parsed files: ", print_r($dto, true), "</pre>";
                break;
            case ImportSteps::DUPLICATE_RESOLUTION:
                $handler = new \Ksfraser\FaBankImport\Handlers\DuplicateResolutionHandler();
                $dto = $handler->handle($request);
                // Render duplicate resolution view (to be implemented)
                echo "<pre>Duplicates: ", print_r($dto, true), "</pre>";
                break;
            case ImportSteps::ACCOUNT_RESOLUTION:
                $handler = new \Ksfraser\FaBankImport\Handlers\AccountResolutionHandler();
                $dto = $handler->handle($request);
                // Render account resolution view (to be implemented)
                echo "<pre>Account resolution: ", print_r($dto, true), "</pre>";
                break;
            case ImportSteps::MAPPING_CONFIRMATION:
                $handler = new \Ksfraser\FaBankImport\Handlers\MappingConfirmationHandler();
                $dto = $handler->handle($request);
                // Render mapping confirmation view (to be implemented)
                echo "<pre>Mapping confirmation: ", print_r($dto, true), "</pre>";
                break;
            case ImportSteps::IMPORT:
                $handler = new \Ksfraser\FaBankImport\Handlers\ImportHandler();
                $dto = $handler->handle($request);
                // Render import summary view (to be implemented)
                echo "<pre>Import summary: ", print_r($dto, true), "</pre>";
                break;
            case ImportSteps::SUMMARY:
                // Render summary view (to be implemented)
                echo "<h2>Import Complete</h2>";
                break;
            case ImportSteps::COMPLETE:
                echo "<h2>Process Finished</h2>";
                break;
            default:
                echo "<h2>Unknown Step</h2>";
        }

        // Determine next step
        $nextStep = ImportStateMachine::getNextStep($currentStep, $context);
        $request['import_step'] = $nextStep;
    }
}
