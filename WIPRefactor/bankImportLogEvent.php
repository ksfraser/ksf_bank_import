<?php
/**
 * @param array<string,mixed> $context
 */
function bank_import_log_event(?ImportRunLogger $logger, string $eventName, array $context = []): void
{
	if ($logger === null) {
		return;
	}
	try {
		$logger->event($eventName, $context);
	} catch (\Throwable $e) {
		// Never block import flow on logging.
	}
}
