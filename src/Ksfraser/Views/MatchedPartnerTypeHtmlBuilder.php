<?php

declare(strict_types=1);

namespace Ksfraser\Views;

use Ksfraser\HTML\HtmlFragment;
use Ksfraser\HTML\Composites\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlHidden;
use Ksfraser\HTML\Elements\HtmlInput;
use Ksfraser\HTML\Elements\HtmlOption;
use Ksfraser\HTML\Elements\HtmlSelect;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\HtmlAttribute;

/**
 * Builds MATCHED partner-type UI as HTML objects (no direct output side effects).
 */
final class MatchedPartnerTypeHtmlBuilder
{
    /**
     * @param int $id
     * @return HtmlFragment
     */
    public function build(int $id): HtmlFragment
    {
        require_once(__DIR__ . '/../FrontAccounting/TransactionTypes/TransactionTypesRegistry.php');

        $fragment = new HtmlFragment();

        $hidden = new HtmlHidden("partnerId_$id", 'manual');
        $fragment->addChild($hidden);

        $registry = \Ksfraser\FrontAccounting\TransactionTypes\TransactionTypesRegistry::getInstance();
        $transactionTypes = $registry->getLabelsArray(['moneyMoved' => true]);

        $select = new HtmlSelect('Existing_Type');
        $select->setClass('combo');
        $select->addOption(new HtmlOption('0', _('Select Transaction Type')));
        foreach ($transactionTypes as $code => $label) {
            $select->addOption(new HtmlOption($code, $label));
        }

        $typeLabelRow = new HtmlLabelRow(new HtmlString(_('Existing Entry Type:')), $select);
        $fragment->addChild($typeLabelRow);

        $entryInput = new HtmlInput('text');
        $entryInput->setName('Existing_Entry');
        $entryInput->setValue('0');
        $entryInput->addAttribute(new HtmlAttribute('size', '6'));
        $entryInput->setPlaceholder(_('Existing Entry:'));

        $entryLabelRow = new HtmlLabelRow(new HtmlString(_('Existing Entry:')), $entryInput);
        $fragment->addChild($entryLabelRow);

        return $fragment;
    }
}
