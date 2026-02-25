<?php
namespace Ksfraser\FaBankImport;

require_once __DIR__ . '/ImportSteps.php';

/**
 * State machine for the bank import process.
 * Each step is a constant; transitions are handled by controller logic.
 */
class ImportStateMachine
{
    /**
     * Get the next step in the workflow.
     * @param string $currentStep
     * @param array $context (optional, for conditional transitions)
     * @return string
     */
    public static function getNextStep($currentStep, $context = [])
    {
        switch ($currentStep) {
            case ImportSteps::UPLOAD_FORM:
                return ImportSteps::PARSE_FILES;
            case ImportSteps::PARSE_FILES:
                if (!empty($context['has_duplicates'])) return ImportSteps::DUPLICATE_RESOLUTION;
                if (!empty($context['needs_account_resolution'])) return ImportSteps::ACCOUNT_RESOLUTION;
                if (!empty($context['needs_mapping_confirmation'])) return ImportSteps::MAPPING_CONFIRMATION;
                return ImportSteps::IMPORT;
            case ImportSteps::DUPLICATE_RESOLUTION:
                if (!empty($context['needs_account_resolution'])) return ImportSteps::ACCOUNT_RESOLUTION;
                if (!empty($context['needs_mapping_confirmation'])) return ImportSteps::MAPPING_CONFIRMATION;
                return ImportSteps::IMPORT;
            case ImportSteps::ACCOUNT_RESOLUTION:
                if (!empty($context['needs_mapping_confirmation'])) return ImportSteps::MAPPING_CONFIRMATION;
                return ImportSteps::IMPORT;
            case ImportSteps::MAPPING_CONFIRMATION:
                return ImportSteps::IMPORT;
            case ImportSteps::IMPORT:
                return ImportSteps::SUMMARY;
            case ImportSteps::SUMMARY:
                return ImportSteps::COMPLETE;
            default:
                return ImportSteps::UPLOAD_FORM;
        }
    }
}
