<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Service;

use Ksfraser\FaBankImport\Service\ImportFlow;
use PHPUnit\Framework\TestCase;

/**
 * The order of the steps of an import run.
 *
 * @see \Ksfraser\FaBankImport\Service\ImportFlow
 */
final class ImportFlowTest extends TestCase
{
    /**
     * The flow states its steps once, in the order they have to run.
     *
     * @return void
     */
    public function testItStatesTheStepsOfTheRun(): void
    {
        $flow = new ImportFlow();

        $this->assertSame(
            [
                ImportFlow::STEP_DUPLICATE_REVIEW,
                ImportFlow::STEP_ACCOUNT_RESOLUTION,
                ImportFlow::STEP_IMPORT,
            ],
            $flow->steps()
        );
    }

    /**
     * The steps run in the documented order.
     *
     * @return void
     */
    public function testTheStepThatFollowsTheDuplicateReviewIsTheAccountResolution(): void
    {
        $flow = new ImportFlow();

        $this->assertSame(
            ImportFlow::STEP_ACCOUNT_RESOLUTION,
            $flow->nextStep(ImportFlow::STEP_DUPLICATE_REVIEW)
        );
        $this->assertSame(
            ImportFlow::STEP_IMPORT,
            $flow->nextStep(ImportFlow::STEP_ACCOUNT_RESOLUTION)
        );
    }

    /**
     * After the last step the flow is finished.
     *
     * @return void
     */
    public function testTheFlowFollowsTheImportStepWithNothing(): void
    {
        $flow = new ImportFlow();

        $this->assertNull($flow->nextStep(ImportFlow::STEP_IMPORT));
    }

    /**
     * A step that is not part of the flow has no follower.
     *
     * @return void
     */
    public function testAnUnknownStepHasNoFollower(): void
    {
        $flow = new ImportFlow();

        $this->assertNull($flow->nextStep('not_a_step'));
    }

    /**
     * The account resolution never runs after a step that is not in the flow.
     *
     * @return void
     */
    public function testTheAccountResolutionDoesNotRunAfterAnUnknownStep(): void
    {
        $flow = new ImportFlow();

        $this->assertFalse($flow->runsAccountResolutionAfter('not_a_step'));
    }
}
