<?php
/**
 * Core primitives coverage tests (batch 2).
 *
 * Drives infrastructure classes that had 0% coverage: the DI container,
 * event objects, dispatcher, command bus, middleware, HTTP request
 * handling and domain exceptions. All pure logic -- no FA or database
 * dependency.
 *
 * @BABOK RTM: FR-015 / BR-003 / FR-014 / FR-050 / IR-001; coverage: docs/TestCases/Batch/
 */

namespace Ksfraser\FaBankImport\Tests\Coverage;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Container\SimpleContainer;
use Ksfraser\FaBankImport\Services\EventDispatcher;
use Ksfraser\FaBankImport\Services\SimpleCommandBus;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;
use Ksfraser\FaBankImport\Events\VendorAddedEvent;
use Ksfraser\FaBankImport\Events\VendorNotAddedEvent;
use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Middleware\AdminMiddleware;
use Ksfraser\FaBankImport\Middleware\AuthMiddleware;
use Ksfraser\FaBankImport\Middleware\TransactionValidationMiddleware;
use Ksfraser\FaBankImport\Middleware\MiddlewarePipeline;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Exceptions\UnauthorizedException;
use Ksfraser\FaBankImport\Exceptions\TransactionValidationException;

class AutoWireFixture
{
    public $dependency;

    public function __construct(\stdClass $dependency = null)
    {
        $this->dependency = $dependency ?? new \stdClass();
    }
}

class AutoWirePrimitiveFixture
{
    public function __construct(int $count = 5)
    {
        $this->count = $count;
    }
}

class ErrorProneFixture
{
    public function __construct($missingNoDefault)
    {
    }
}

class CorePrimitivesTest extends TestCase
{
    // ------------------------------------------------------------------
    // SimpleContainer
    // ------------------------------------------------------------------
    public function test_container_binds_and_makes_instance_singleton(): void
    {
        $c = new SimpleContainer();
        $c->bind(\stdClass::class, new \stdClass());

        $first = $c->make(\stdClass::class);
        $second = $c->make(\stdClass::class);

        $this->assertSame($first, $second, 'object binding defaults to singleton');
        $this->assertTrue($c->has(\stdClass::class));
        $this->assertContains(\stdClass::class, $c->getBindings());
    }

    public function test_container_makes_distinct_non_singleton_instances(): void
    {
        $c = new SimpleContainer();
        $c->bind(\stdClass::class, \stdClass::class, false);

        $this->assertNotSame($c->make(\stdClass::class), $c->make(\stdClass::class));
    }

    public function test_container_invokes_factory_bindings(): void
    {
        $c = new SimpleContainer();
        $c->bind('event', function (SimpleContainer $container) {
            return new TransactionProcessedEvent(1, 'C');
        });

        $this->assertInstanceOf(TransactionProcessedEvent::class, $c->make('event'));
    }

    public function test_container_activates_the_same_factory_once_for_singletons(): void
    {
        $calls = 0;
        $c = new SimpleContainer();
        $c->bind('lazy', function () use (&$calls) {
            $calls++;

            return new AutoWirePrimitiveFixture();
        });

        $c->make('lazy');
        $c->make('lazy');

        $this->assertSame(1, $calls);
        $this->assertInstanceOf(AutoWirePrimitiveFixture::class, $c->make('lazy'));
    }

    public function test_container_uses_registered_instances_first(): void
    {
        $c = new SimpleContainer();
        $registered = new \stdClass();
        $c->instance(AutoWireFixture::class, $registered);

        $this->assertSame($registered, $c->make(AutoWireFixture::class));
    }

    public function test_container_auto_wires_class_dependencies(): void
    {
        $c = new SimpleContainer();
        $c->bind(\stdClass::class, new \stdClass());

        $made = $c->make(AutoWireFixture::class);

        $this->assertInstanceOf(AutoWireFixture::class, $made);
        $this->assertInstanceOf(\stdClass::class, $made->dependency);
    }

    public function test_container_unbound_class_resolves_by_name(): void
    {
        $c = new SimpleContainer();

        $made = $c->make(AutoWireFixture::class);
        $this->assertInstanceOf(AutoWireFixture::class, $made);
    }

    public function test_container_uninstantiable_abstract_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not instantiable');

        (new SimpleContainer())->make(\Ksfraser\FaBankImport\Middleware\MiddlewareInterface::class);
    }

    public function test_container_unknown_class_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        (new SimpleContainer())->make('Ksfraser\\FaBankImport\\Does\\Not\\Exist');
    }

    public function test_container_unknown_constructor_dependency_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve');

        (new SimpleContainer())->make(ErrorProneFixture::class);
    }

    public function test_container_flush_clears_bindings(): void
    {
        $c = new SimpleContainer();
        $c->bind(\stdClass::class, new \stdClass());
        $this->assertTrue($c->has(\stdClass::class));

        $c->flush();

        $this->assertSame([], $c->getBindings());
        $this->assertFalse($c->has(\stdClass::class));
    }

    // ------------------------------------------------------------------
    // Events
    // ------------------------------------------------------------------
    public function test_transaction_processed_event_accessors(): void
    {
        $event = new TransactionProcessedEvent(42, 'D');

        $this->assertSame(42, $event->getTransactionId());
        $this->assertSame('D', $event->getType());
        $this->assertInstanceOf(\DateTime::class, $event->getTimestamp());
    }

    public function test_vendor_added_and_not_added_event_accessors(): void
    {
        $added = @new VendorAddedEvent(7);
        $this->assertSame(7, $added->getVendorId());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $added->getTimestamp());

        $notAdded = @new VendorNotAddedEvent(9);
        $this->assertSame(9, $notAdded->getVendorId());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $notAdded->getTimestamp());
    }

    public function test_processed_transaction_command_accessors(): void
    {
        $command = new ProcessTransactionCommand(11, 'C', 5);

        $this->assertSame(11, $command->getTransactionId());
        $this->assertSame('C', $command->getType());
        $this->assertSame(5, $command->getUserId());
        $this->assertNull((new ProcessTransactionCommand(12, 'D'))->getUserId());
    }

    // ------------------------------------------------------------------
    // EventDispatcher
    // ------------------------------------------------------------------
    public function test_dispatcher_invokes_listeners_for_event_class(): void
    {
        $d = new EventDispatcher();
        $received = [];

        $d->addListener(TransactionProcessedEvent::class, function ($event) use (&$received) {
            $received[] = 'first:' . $event->getType();
        });
        $d->addListener(TransactionProcessedEvent::class, function ($event) use (&$received) {
            $received[] = 'second';
        });

        $d->dispatch(new TransactionProcessedEvent(1, 'B'));

        $this->assertSame(['first:B', 'second'], $received);
    }

    public function test_dispatcher_ignores_unlistened_events(): void
    {
        $d = new EventDispatcher();

        $this->assertNull($d->dispatch(new \stdClass()));
    }

    // ------------------------------------------------------------------
    // SimpleCommandBus
    // ------------------------------------------------------------------
    public function test_command_bus_dispatches_to_registered_handler(): void
    {
        $bus = new SimpleCommandBus();
        $bus->register(ProcessTransactionCommand::class, new class {
            public function handle($command)
            {
                return 'handled:' . $command->getTransactionId();
            }
        });

        $this->assertSame('handled:33', $bus->dispatch(new ProcessTransactionCommand(33, 'C')));
    }

    public function test_command_bus_requires_a_handler_for_the_command(): void
    {
        $bus = new SimpleCommandBus();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No handler registered');

        $bus->dispatch(new ProcessTransactionCommand(34, 'C'));
    }

    // ------------------------------------------------------------------
    // Middleware
    // ------------------------------------------------------------------
    public function test_pipeline_runs_middlewares_in_reverse_registration_order(): void
    {
        $pipeline = new MiddlewarePipeline();
        $trace = [];

        $pipeline->pipe(new class('outer', $trace) implements \Ksfraser\FaBankImport\Middleware\MiddlewareInterface {
            private $label;
            private $trace;

            public function __construct(string $label, array &$trace)
            {
                $this->label = $label;
                $this->trace = &$trace;
            }

            public function process($request, callable $next)
            {
                $this->trace[] = 'in:' . $this->label;

                return $next($request) . '|out:' . $this->label;
            }
        });
        $pipeline->pipe(new class('inner', $trace) implements \Ksfraser\FaBankImport\Middleware\MiddlewareInterface {
            private $label;
            private $trace;

            public function __construct(string $label, array &$trace)
            {
                $this->label = $label;
                $this->trace = &$trace;
            }

            public function process($request, callable $next)
            {
                $this->trace[] = 'in:' . $this->label;

                return $next($request);
            }
        });

        $result = $pipeline->process(new RequestHandler(), fn ($request) => 'CORE');

        $this->assertSame('CORE|out:outer', $result);
        $this->assertSame(['in:outer', 'in:inner'], $trace);
    }

    public function test_pipeline_short_circuits_without_handler(): void
    {
        $pipeline = new MiddlewarePipeline();
        $pipeline->pipe(new class implements \Ksfraser\FaBankImport\Middleware\MiddlewareInterface {
            public function process($request, callable $next)
            {
                return 'STOPPED';
            }
        });

        $reached = false;
        $result = $pipeline->process(new RequestHandler(), function () use (&$reached) {
            $reached = true;

            return 'CORE';
        });

        $this->assertSame('STOPPED', $result);
        $this->assertFalse($reached);
    }

    public function test_admin_middleware_rejects_non_admin_sessions(): void
    {
        $_SESSION['user_id'] = 5;
        unset($_SESSION['is_admin']);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('administrator privileges');

        (new AdminMiddleware())->process(new RequestHandler(), fn ($request) => 'CORE');
    }

    public function test_admin_middleware_passes_admin_sessions(): void
    {
        unset($_SESSION['user_id'], $_SESSION['is_admin']);
        $_SESSION['user_id'] = 5;
        $_SESSION['is_admin'] = true;

        $result = (new AdminMiddleware())->process(new RequestHandler(), fn ($request) => 'ALLOWED');
        $this->assertSame('ALLOWED', $result);
    }

    public function test_auth_middleware_passes_when_logged_in(): void
    {
        $_SESSION['user_id'] = 8;

        $result = (new AuthMiddleware())->process(new RequestHandler(), fn ($request) => 'PASSED');
        $this->assertSame('PASSED', $result);
    }

    private function postRequestHandler(array $transaction): RequestHandler
    {
        $savedMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $savedPost = $_POST;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['transaction' => $transaction];

        try {
            return new RequestHandler();
        } finally {
            if ($savedMethod === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $savedMethod;
            }
            $_POST = $savedPost;
        }
    }

    public function test_validation_middleware_accepts_valid_transaction_types(): void
    {
        $middleware = new TransactionValidationMiddleware();

        $result = $middleware->process($this->postRequestHandler(['type' => 'C']), fn ($request) => 'OK');

        $this->assertSame('OK', $result);
    }

    public function test_validation_middleware_rejects_invalid_transaction_types(): void
    {
        $middleware = new TransactionValidationMiddleware();

        try {
            $middleware->process($this->postRequestHandler(['type' => 'Z']), fn ($request) => 'OK');
            $this->fail('Invalid transaction type should raise TransactionValidationException');
        } catch (TransactionValidationException $e) {
            $this->assertEqualsCanonicalizing(['Invalid transaction type'], $e->getValidationErrors());
        }
    }

    // ------------------------------------------------------------------
    // RequestHandler
    // ------------------------------------------------------------------
    public function test_request_handler_processes_with_params_and_defaults(): void
    {
        $handler = new RequestHandler(['param_x' => 9]);

        $output = $handler->handle();

        $this->assertTrue($output['success']);
        $this->assertSame('Request processed successfully', $output['message']);
    }

    public function test_request_handler_merges_params(): void
    {
        $_GET['query_key'] = 'q1';
        $handler = new RequestHandler(['param_x' => 9]);

        $this->assertSame(9, $handler->getParams()['param_x']);
        $this->assertSame('q1', $handler->getParams()['query_key']);

        $this->assertFalse($handler->isPost());
    }

    public function test_request_handler_picks_transaction_from_params(): void
    {
        $handler = new RequestHandler(['transaction' => ['type' => 'D']]);

        $this->assertSame('D', $handler->getTransactionCommand()['type']);
    }

    // ------------------------------------------------------------------
    // Domain exceptions
    // ------------------------------------------------------------------
    public function test_unauthorized_exception_defaults(): void
    {
        $e = new UnauthorizedException();

        $this->assertSame(403, $e->getCode());
        $this->assertSame('Unauthorized access', $e->getMessage());
    }

    public function test_transaction_validation_exception_builds_message_from_errors(): void
    {
        $e = new TransactionValidationException(['amount missing', 'type unknown']);

        $this->assertSame(
            'Transaction validation failed: amount missing, type unknown',
            $e->getMessage()
        );
        $this->assertSame(['amount missing', 'type unknown'], $e->getValidationErrors());
    }
}
