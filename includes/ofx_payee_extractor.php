<?php

declare(strict_types=1);

/**
 * Read the PAYEE aggregate out of raw OFX/QFX content.
 *
 * The vendored asgrim parser exposes only the transaction-level fields, so
 * payee name/address is read from the raw stream and joined back by FITID.
 * Kept standalone (no FA includes) so it is unit-testable.
 *
 * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
 */
class ofx_payee_extractor
{
    /**
     * A fixed child set, which is how SGML (where payee elements have no
     * closing tags) is terminated: the first tag outside the set ends it.
     *
     * @return array<string,string> Payee tag => transaction field.
     */
    protected function fieldMap()
    {
        return array(
            'NAME' => 'merchant',
            'ADDR1' => 'address1',
            'ADDR2' => 'address2',
            'ADDR3' => 'address3',
            'CITY' => 'city',
            'STATE' => 'province',
            'POSTALCODE' => 'postalcode',
            'COUNTRY' => 'country',
            'PHONE' => 'phone',
            'EMAIL' => 'email',
        );
    }

    /**
     * Payee fields for every transaction that has one, keyed by FITID.
     *
     * @param string $content Raw OFX/QFX contents.
     * @return array<string,array<string,string>>
     * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
     */
    public function extractPayees($content)
    {
        $payees = array();
        $pattern = '/<STMTTRN>(.*?)(?=<\\/STMTTRN>|<STMTTRN>|<\\/BANKTRANLIST>|<\\/CCSTMTRS>|<\\/STMTRS>)/is';
        if (!preg_match_all($pattern, (string) $content, $blocks)) {
            return $payees;
        }

        foreach ($blocks[1] as $block) {
            $fitid = $this->tagValue($block, 'FITID');
            if ($fitid === null) {
                continue;
            }
            $payee = $this->extractPayeeBlock($block);
            if ($payee) {
                $payees[$fitid] = $payee;
            }
        }

        return $payees;
    }

    /**
     * Value of the first occurrence of a tag inside a fragment.
     *
     * @param string $fragment STMTTRN fragment.
     * @param string $tag      Tag name.
     * @return string|null Trimmed value, or null when absent/empty.
     * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
     */
    public function tagValue($fragment, $tag)
    {
        if (preg_match('/<' . preg_quote($tag, '/') . '>([^<\\r\\n]*)/i', (string) $fragment, $match)) {
            $value = trim($match[1]);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Read the PAYEE aggregate from one transaction block.
     *
     * @param string $block STMTTRN fragment.
     * @return array<string,string> Payee fields.
     * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
     */
    public function extractPayeeBlock($block)
    {
        $position = stripos((string) $block, '<PAYEE>');
        if ($position === false) {
            return array();
        }

        $names = $this->fieldMap();
        $fields = array();
        $tail = substr($block, $position + strlen('<PAYEE>'));
        if (!preg_match_all('/<([A-Z0-9_.]+)>([^<\\r\\n]*)/i', $tail, $matches, PREG_SET_ORDER)) {
            return $fields;
        }

        foreach ($matches as $match) {
            $tag = strtoupper($match[1]);
            if (!isset($names[$tag])) {
                break;
            }
            $value = trim($match[2]);
            if ($value !== '') {
                $fields[$names[$tag]] = $value;
            }
        }

        return $fields;
    }

    /**
     * Account ids declared by CCACCTFROM aggregates.
     *
     * @param string $content Raw OFX/QFX contents.
     * @return array<string,string> Credit-card account ids, keyed by id.
     * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
     */
    public function extractCreditCardAccountIds($content)
    {
        if (stripos((string) $content, 'CREDITCARDMSGSRSV1') === false) {
            return array();
        }

        $ids = array();
        if (preg_match_all('/<CCACCTFROM>.*?<ACCTID>([^<\\r\\n]*)/is', (string) $content, $matches)) {
            foreach ($matches[1] as $id) {
                $id = trim($id);
                if ($id !== '') {
                    $ids[$id] = $id;
                }
            }
        }

        return $ids;
    }

    /**
     * Copy a payee's fields onto a transaction, when the bank supplied them.
     *
     * Only non-empty fields are written, so a partial PAYEE does not erase a
     * transaction-level value.
     *
     * @param object               $transaction          Transaction object to enrich.
     * @param array<string,string> $payee                Fields from extractPayees().
     * @param string               $accountNumber        Statement account id.
     * @param array<string,string> $creditCardAccounts   Keyed credit-card account ids.
     * @return void
     * @BABOK FR-IMP-005, FR-001 | BR-IMP-001 | UAT-IMP-001
     */
    public function applyTo($transaction, array $payee, $accountNumber, array $creditCardAccounts)
    {
        foreach (array('city', 'province', 'country', 'postalcode') as $field) {
            if (!empty($payee[$field])) {
                $transaction->$field = $payee[$field];
            }
        }

        $address = array();
        foreach (array('address1', 'address2', 'address3') as $line) {
            if (!empty($payee[$line])) {
                $address[] = $payee[$line];
            }
        }
        if ($address) {
            $transaction->address = implode(', ', $address);
        }

        if (isset($creditCardAccounts[$accountNumber])) {
            $transaction->cardnumber = $accountNumber;
        }
    }
}
