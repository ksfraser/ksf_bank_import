<?php

namespace KsfBankImport\Views;

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
 * AllocatableInvoicesTable - SRP class to render allocatable invoices as a self-contained table
 */
class AllocatableInvoicesTable implements HtmlElementInterface
{
    private $fragment;

    /**
     * @param array $allocDetails Array of allocation rows (each row is an assoc array)
     * @param array|null $columns Optional column map defining keys, labels and formatters.
     *
     * Columns map format (assoc array) — keys correspond to data row keys and values
     * are either a string (header label) or an array with optional entries:
     * - `label` (string): header text
     * - `formatter` (callable): fn($value, $row): string — returns formatted cell value
     * - `attrs` (array): associative array of cell attributes (e.g. ['align'=>'right'])
     * - `thAttrs` (array): attributes for the header cell
     *
     * Example:
     * ```php
     * $columns = [
     *   'type_no' => '#',
     *   'trans_date' => ['label'=>_('Date')],
     *   'invoice_amount' => [
     *       'label'=>_('Invoice Amount'),
     *       'formatter'=>fn($v)=>number_format((float)$v,2),
     *       'attrs'=>['align'=>'right','class'=>'currency']
     *   ],
     * ];
     * ```
     *
     * Notes:
     * - Numeric values will be formatted with two decimals for display when no formatter
     *   is provided.
     */
    public function __construct(array $allocDetails = [], ?array $columns = null)
    {
        $this->fragment = new HtmlFragment();

        if (empty($allocDetails)) {
            return;
        }

        // Normalize columns: if not provided, use sensible defaults
        if ($columns === null) {
            $columns = [
                'type_no' => '#',
                'trans_date' => _('Date'),
                'invoice_amount' => _('Invoice Amount'),
                'payments' => _('Other Payments'),
                'unallocated' => _('Outstanding Balance'),
            ];
        }

        // Normalize each column entry to full array form
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

        // Build header row
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

        // Build body rows
        $bodyFragment = new HtmlFragment();
        foreach ($allocDetails as $row) {
            $cells = new HtmlFragment();
            foreach ($normalized as $key => $spec) {
                // Determine raw value (allow aliasing like 'amount' vs 'invoice_amount')
                $value = $row[$key] ?? null;
                if ($value === null) {
                    // try simple fallback lookups used previously
                    if ($key === 'trans_date' && isset($row['date_'])) {
                        $value = $row['date_'];
                    } elseif ($key === 'invoice_amount' && isset($row['amount'])) {
                        $value = $row['amount'];
                    }
                }

                // Apply formatter if provided
                if (!empty($spec['formatter']) && is_callable($spec['formatter'])) {
                    $out = call_user_func($spec['formatter'], $value, $row);
                } else {
                    // Default formatting: numeric values -> 2 decimals, else cast to string
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

        // Compose table: header + body
        $tableContent = new HtmlFragment();
        $tableContent->addChild($headerRow);
        $tableContent->addChild($bodyFragment);

        $table = new HtmlTable($tableContent);
        $table->addAttribute(new \Ksfraser\HTML\HtmlAttribute('class', TABLESTYLE));
        $table->addAttribute(new \Ksfraser\HTML\HtmlAttribute('width', '80%'));

        $this->fragment->addChild($table);
    }

    public function getHtml(): string
    {
        return $this->fragment->getHtml();
    }

    public function toHtml(): void
    {
        $this->fragment->toHtml();
    }
}
