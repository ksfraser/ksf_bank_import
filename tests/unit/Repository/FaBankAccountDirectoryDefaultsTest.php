<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Repository {
    /**
     * Namespace-local FA doubles for FaBankAccountDirectory's default
     * closures. A namespaced function shadows the global lookup for the
     * unqualified production calls without touching the global function
     * table (handler_fa_stubs pattern).
     */
    final class FaNativeDouble
    {
        /** @var mixed What get_bank_accounts returns next. */
        public static $listResult = null;

        /** @var mixed What hook_invoke replies with (null = silent module). */
        public static $hookResult = null;

        public static function reset(): void
        {
            self::$listResult = null;
            self::$hookResult = null;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\get_bank_accounts')) {
        function get_bank_accounts($show_inactive = false)
        {
            return FaNativeDouble::$listResult;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\hook_invoke')) {
        function hook_invoke($ext, $method, &$data, $opts = null)
        {
            if (FaNativeDouble::$hookResult !== null) {
                $data = FaNativeDouble::$hookResult;
            }
            return FaNativeDouble::$hookResult;
        }
    }
}

namespace Ksfraser\FaBankImport\Tests\Unit\Repository {
    use Ksfraser\FaBankImport\Repository\FaBankAccountDirectory;
    use Ksfraser\FaBankImport\Repository\FaNativeDouble;
    use PHPUnit\Framework\TestCase;

    /**
     * The fetchable "mysqli-like" result: its string form is the SQL famock
     * uses to look up the seeded result set.
     */
    final class FetchableResult
    {
        public function __toString(): string
        {
            return 'SELECT * FROM 0_bank_accounts directory defaults';
        }
    }

    final class FaBankAccountDirectoryDefaultsTest extends TestCase
    {
        /** @var callable|null */
        protected $scopedAutoloader;

        protected function setUp(): void
        {
            FaNativeDouble::reset();
        }

        protected function tearDown(): void
        {
            unset($GLOBALS['__fa_result_set']['SELECT * FROM 0_bank_accounts directory defaults']);
            if ($this->scopedAutoloader !== null) {
                spl_autoload_unregister($this->scopedAutoloader);
                $this->scopedAutoloader = null;
            }
        }

        public function testNativeMissingYieldsNothing(): void
        {
            FaNativeDouble::$listResult = null;
            $directory = new FaBankAccountDirectory();

            $this->assertSame([], $directory->listBankAccounts());
            $this->assertFalse($directory->accountNumberExists('123'));
        }

        public function testNativeFalseYieldsNothing(): void
        {
            FaNativeDouble::$listResult = false;
            $directory = new FaBankAccountDirectory();

            $this->assertSame([], $directory->listBankAccounts());
        }

        public function testNativeArrayShapeIsAligned(): void
        {
            FaNativeDouble::$listResult = [
                1 => ['id' => 2, 'bank_account_number' => '777', 'bank_name' => 'Array Bank'],
            ];
            $directory = new FaBankAccountDirectory();

            $this->assertSame(
                [['id' => 2, 'number' => '777', 'name' => 'Array Bank']],
                $directory->listBankAccounts()
            );
        }

        public function testNativeFetchableResultDrivesTheDbLoop(): void
        {
            $GLOBALS['__fa_result_set']['SELECT * FROM 0_bank_accounts directory defaults'] = [
                ['id' => 6, 'bank_account_number' => '888', 'bank_name' => 'Loop Bank'],
            ];
            FaNativeDouble::$listResult = new FetchableResult();
            $directory = new FaBankAccountDirectory();

            $this->assertSame(
                [['id' => 6, 'number' => '888', 'name' => 'Loop Bank']],
                $directory->listBankAccounts()
            );
        }

        public function testDefaultDtoFactoryDeclinesWhenResponderVendorAbsent(): void
        {
            if (class_exists('\ksfraser\FrontAccounting\NativeResponder\Dto\BankAccountDTO')) {
                $this->markTestSkipped('The responder vendor is loaded; the vendor-absent path is not reachable.');
            }

            FaNativeDouble::$hookResult = 'should-not-fire';
            FaNativeDouble::$listResult = [
                ['id' => 3, 'bank_account_number' => '123', 'bank_account_name' => 'N'],
            ];
            $directory = new FaBankAccountDirectory();

            // No DTO reachable -> the hook cannot fire; FA answered instead.
            $this->assertSame(
                [['id' => 3, 'number' => '123', 'name' => 'N']],
                $directory->listBankAccounts()
            );
        }

        public function testDefaultDtoFactoryBuildsTheHookRequestWhenVendorPresent(): void
        {
            $this->scopedAutoloader = static function (string $class): void {
                if ($class === 'ksfraser\FrontAccounting\NativeResponder\Dto\BankAccountDTO') {
                    eval('namespace ksfraser\FrontAccounting\NativeResponder\Dto; class BankAccountDTO { public $injected = null; public function __construct(array $values = []) { $this->injected = $values; } }');
                }
            };
            spl_autoload_register($this->scopedAutoloader);

            FaNativeDouble::$hookResult = null; // module silent -> envelope untouched
            FaNativeDouble::$listResult = [
                ['id' => 4, 'bank_account_number' => '55', 'bank_name' => 'Hook Bank'],
            ];
            $directory = new FaBankAccountDirectory();

            // The hook ran with a real DTO and stayed silent; the fallback answers.
            $this->assertSame(
                [['id' => 4, 'number' => '55', 'name' => 'Hook Bank']],
                $directory->listBankAccounts()
            );
        }
    }
}
