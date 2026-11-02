<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: FR-009 / IR-001 / ARCH-008*/

namespace Ksfraser\FaBankImport\Views;

use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlTable;
use Ksfraser\HTML\Elements\HtmlTableRow;
use Ksfraser\HTML\Elements\HtmlTd;
use Ksfraser\HTML\Elements\HtmlTh;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlElementInterface;
use Ksfraser\HTML\HtmlAttribute;

/**
 * @BABOK CLASS ROLLUP: AllocatableInvoicesTable — 3 methods; FR-009 (3); IR-001 (3)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/AllocatableInvoicesTable.php; coverage: docs/TestCases/ (file rollup)
 * AllocatableInvoicesTable - SRP class to render allocatable invoices as a self-contained table
 * 
 * @package Views
 * @since 20251019
 */
class AllocatableInvoicesTable implements HtmlElementInterface
{
    /**
     * @var HtmlFragment
     */
    private $fragment;

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
     * Create allocatable invoices table
     * 
     * @param array $allocDetails Array of allocation rows (each row is an assoc array)
     * @param array|null $columns Optional column map defining keys, labels and formatters
     */
    public function __construct(array $allocDetails = [], ?array $columns = null)
    {
        $this->fragment = new HtmlFragment();

        if (empty($allocDetails)) {
            return;
        }

        if ($columns === null) {
            $columns = [
                'type_no' => '#',
                'trans_date' => _('Date'),
                'invoice_amount' => _('Invoice Amount'),
                'payments' => _('Other Payments'),
                'unallocated' => _('Outstanding Balance'),
            ];
        }

        $normalized = [];
        foreach ($columns as $key => $spec) {
            if (is_string($spec)) {
                $normalized[$key] = ['label' => $spec];
            } elseif (is_array($spec)) {
                $normalized[$key] = $spec;
            } else {
                $normalized[$key] = ['label' => (string)$spec];
            }
        }

        $headerCells = new HtmlFragment();
        foreach ($normalized as $key => $spec) {
            $label = $spec['label'] ?? '';
            $th = new HtmlTh(new HtmlString($label));
            if (!empty($spec['thAttrs']) && is_array($spec['thAttrs'])) {
                foreach ($spec['thAttrs'] as $attrName => $attrValue) {
                    $th->addAttribute(new HtmlAttribute($attrName, $attrValue));
                }
            }
            $headerCells->addChild($th);
        }

        $headerRow = new HtmlTableRow($headerCells);

        $bodyFragment = new HtmlFragment();
        foreach ($allocDetails as $row) {
            $cells = new HtmlFragment();
            foreach ($normalized as $key => $spec) {
                $value = $row[$key] ?? null;
                if ($value === null) {
                    if ($key === 'trans_date' && isset($row['date_'])) {
                        $value = $row['date_'];
                    } elseif ($key === 'invoice_amount' && isset($row['amount'])) {
                        $value = $row['amount'];
                    }
                }

                if (!empty($spec['formatter']) && is_callable($spec['formatter'])) {
                    $out = call_user_func($spec['formatter'], $value, $row);
                } else {
                    if (is_numeric($value)) {
                        $out = number_format((float)$value, 2);
                    } else {
                        $out = (string)($value ?? '');
                    }
                }

                $td = new HtmlTd(new HtmlString($out));
                if (!empty($spec['attrs']) && is_array($spec['attrs'])) {
                    foreach ($spec['attrs'] as $attrName => $attrValue) {
                        $td->addAttribute(new HtmlAttribute($attrName, $attrValue));
                    }
                }

                $cells->addChild($td);
            }

            $bodyFragment->addChild(new HtmlTableRow($cells));
        }

        $tableContent = new HtmlFragment();
        $tableContent->addChild($headerRow);
        $tableContent->addChild($bodyFragment);

        $table = new HtmlTable($tableContent);
        $tableClass = defined('TABLESTYLE') ? TABLESTYLE : 'tablestyle';
        $table->addAttribute(new HtmlAttribute('class', $tableClass));
        $table->addAttribute(new HtmlAttribute('width', '80%'));

        $this->fragment->addChild($table);
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getHtml
     * Get HTML as string
     * 
     * @return string The HTML
     */
    public function getHtml(): string
    {
        return $this->fragment->getHtml();
    }

    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: toHtml
     * Output HTML directly to screen
     * 
     * @return void
     */
    public function toHtml(): void
    {
        $this->fragment->toHtml();
    }
}
