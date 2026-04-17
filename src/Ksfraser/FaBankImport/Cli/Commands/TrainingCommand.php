<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Cli\Commands;

use Ksfraser\FaBankImport\Contracts\Command;
use Ksfraser\FaBankImport\Contracts\TrainingService;
use Ksfraser\FaBankImport\Contracts\Logger;
use Ksfraser\FaBankImport\Infrastructure\Error\ErrorHandler;
use Ksfraser\FaBankImport\Exception\TrainingException;

/**
 * TrainingCommand - CLI command to execute partner matching training
 * 
 * Runs the TrainingService to collect training data from historical transactions
 * and update partner learning metrics (occurrence counts, timestamps).
 * 
 * Usage:
 *   php app.php train [--dry-run] [--quiet]
 * 
 * Options:
 *   --dry-run     Don't persist changes, just simulate
 *   --quiet       Suppress output
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
final class TrainingCommand implements Command
{
    public function __construct(
        private readonly TrainingService $trainingService,
        private readonly Logger $logger,
        private readonly ErrorHandler $errorHandler
    ) {}

    public function name(): string
    {
        return 'train';
    }

    public function description(): string
    {
        return 'Build training data from historical partner transactions';
    }

    public function execute(array $arguments = []): int
    {
        try {
            $dryRun = $this->parseDryRunFlag($arguments);
            $quiet = $this->parseQuietFlag($arguments);

            $result = $this->errorHandler->handle(
                fn() => $this->trainingService->buildTrainingData(dryRun: $dryRun),
                'partner training'
            );

            if (!$quiet) {
                $this->outputResults($result, $dryRun);
            }

            $this->logger->info(
                'Training completed successfully',
                [
                    'processed' => $result['processed'],
                    'learned' => $result['learned'],
                    'skipped' => $result['skipped'],
                    'dry_run' => $dryRun
                ]
            );

            return 0; // Success
        } catch (TrainingException $e) {
            $this->errorHandler->logException($e, 'in training command');
            $this->outputError('Training failed: ' . $e->getMessage());
            return 1; // Failure
        } catch (\Throwable $e) {
            $this->errorHandler->logException($e, 'unexpected error in training command');
            $this->outputError('Unexpected error: ' . $e->getMessage());
            return 2; // Fatal error
        }
    }

    public function help(): string
    {
        return <<<'HELP'
Build training data from historical partner transactions.

USAGE:
  php app.php train [OPTIONS]

OPTIONS:
  --dry-run      Simulate without persisting changes (default: false)
  --quiet        Suppress output (default: false)
  --help         Show this help message

DESCRIPTION:
  Processes all historical partners across all types (suppliers, customers,
  bank transfers, quick entries) and searches for matching transactions.
  Updates learning metrics (occurrence count, last matched timestamp) for
  partners that have matches.

  With --dry-run flag, the operation is simulated without database changes.
  This is useful for testing or previewing what the training would do.

EXAMPLES:
  # Run training with database updates
  php app.php train

  # Simulate training without persisting
  php app.php train --dry-run

  # Run silently
  php app.php train --quiet

  # Combine options
  php app.php train --dry-run --quiet
HELP;
    }

    private function parseDryRunFlag(array $arguments): bool
    {
        return isset($arguments['--dry-run']) || isset($arguments['dry-run']);
    }

    private function parseQuietFlag(array $arguments): bool
    {
        return isset($arguments['--quiet']) || isset($arguments['quiet']);
    }

    private function outputResults(array $stats, bool $dryRun): void
    {
        $modeText = $dryRun ? ' (dry-run)' : '';
        
        echo "\n";
        echo "Training Results$modeText:\n";
        echo "  Processed:  {$stats['processed']}\n";
        echo "  Learned:    {$stats['learned']}\n";
        echo "  Skipped:    {$stats['skipped']}\n";
        echo "\n";

        if ($dryRun) {
            echo "NOTE: Changes were not persisted (dry-run mode)\n\n";
        }
    }

    private function outputError(string $message): void
    {
        echo "\n";
        echo "ERROR: $message\n";
        echo "\n";
    }
}
