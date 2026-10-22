<?php

declare(strict_types=1);

/**
 * Scenario: exercises \Ksfraser\Application\Controllers\AbstractController and
 * \Ksfraser\Application\Controllers\AdminController.
 *
 * Both controllers render their output through the response handler, which
 * ends with exit(), so they can only be driven from a separate process.
 */

namespace Ksfraser\Application\Controllers {

    use Ksfraser\Application\Controllers\AbstractController;

    /**
     * Concrete controller that exposes the protected helpers of the base
     * class.
     */
    class TestController extends AbstractController
    {        /**
         * @param string $view View name.
         * @param array  $data View data.
         * @return void
         */
        public function callRender(string $view, array $data = []): void
        {
            $this->render($view, $data);
        }

        /**
         * @param array $data Payload.
         * @return void
         */
        public function callJson(array $data): void
        {
            $this->json($data);
        }

        /**
         * @param string $url Target url.
         * @return void
         */
        public function callRedirect(string $url): void
        {
            $this->redirect($url);
        }

        /**
         * @return string
         */
        public function index(): string
        {
            return 'index-action';
        }
    }

    /**
     * Admin controller whose json() helper does not terminate the request, so
     * that the code behind the call can be reached.
     */
    class TestableAdminController extends AdminController
    {
        /** @var array */
        public $jsonPayloads = [];

        /**
         * @param array $data Payload.
         * @return void
         */
        protected function json(array $data): void
        {
            $this->jsonPayloads[] = $data;
        }
    }
}

namespace {

    use Ksfraser\Application\Config\Config;
    use Ksfraser\Application\Controllers\AdminController;
    use Ksfraser\Application\Controllers\TestableAdminController;
    use Ksfraser\Application\Controllers\TestController;
    use Ksfraser\Application\Http\RequestHandler;

    /**
     * Exposes the response handler of a controller so that the runner can
     * report the headers and the status code it produced.
     *
     * @param object $controller Controller under test.
     * @return void
     */
    $exposeResponse = function ($controller) {
        $reflection = new \ReflectionObject($controller);
        if (!$reflection->hasProperty('response')) {
            return;
        }
        $property = $reflection->getProperty('response');
        $property->setAccessible(true);
        $GLOBALS['__isolated_handler'] = $property->getValue($controller);
    };

    $mode = isset($args[0]) ? (string) $args[0] : 'abstract_json';
    $logPath = isset($args[1]) ? (string) $args[1] : sys_get_temp_dir();

    $config = Config::getInstance();
    $config->set('logging.path', $logPath);

    RequestHandler::$query = [
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'end_date' => date('Y-m-d'),
        'days' => '7',
        'threshold' => '2.0',
    ];

    switch ($mode) {
        case 'abstract_render':
            $controller = new TestController();
            $exposeResponse($controller);
            $controller->callRender('admin/dashboard', ['title' => 'Dashboard']);
            break;

        case 'abstract_json':
            $controller = new TestController();
            $exposeResponse($controller);
            $controller->callJson(['status' => 'ok']);
            break;

        case 'abstract_redirect':
            $controller = new TestController();
            $exposeResponse($controller);
            $controller->callRedirect('/target.php');
            break;

        case 'admin_dashboard':
            $controller = new AdminController();
            $exposeResponse($controller);
            $controller->dashboard();
            break;

        case 'admin_performance_report_json':
            $controller = new AdminController();
            $exposeResponse($controller);
            RequestHandler::$query['format'] = 'json';
            $controller->performanceReport();
            break;

        case 'admin_performance_report_html':
            $controller = new AdminController();
            $exposeResponse($controller);
            $controller->performanceReport();
            break;

        case 'admin_performance_report_json_stubbed':
            $controller = new TestableAdminController();
            RequestHandler::$query['format'] = 'json';
            $controller->performanceReport();
            $GLOBALS['__isolated_state'] = ['json_payloads' => $controller->jsonPayloads];
            break;

        case 'admin_anomaly_report_json_stubbed':
            $controller = new TestableAdminController();
            RequestHandler::$query['format'] = 'json';
            $controller->anomalyReport();
            $GLOBALS['__isolated_state'] = ['json_payloads' => $controller->jsonPayloads];
            break;

        case 'admin_export':
            $controller = new AdminController();
            $exposeResponse($controller);
            $controller->exportMetrics();
            break;

        case 'admin_anomaly_report_json':
            $controller = new AdminController();
            $exposeResponse($controller);
            RequestHandler::$query['format'] = 'json';
            $controller->anomalyReport();
            break;

        case 'admin_anomaly_report_html':
            $controller = new AdminController();
            $exposeResponse($controller);
            $controller->anomalyReport();
            break;

        default:
            echo "UNKNOWN_MODE";
            break;
    }
}
