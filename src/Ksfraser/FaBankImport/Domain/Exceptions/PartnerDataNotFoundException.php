<?php

namespace Ksfraser\FaBankImport\Domain\Exceptions;

use Exception;

/**
 * @BABOK RTM: BR-002 / FR-008 / FR-018; file: Domain/Exceptions/PartnerDataNotFoundException.php; coverage: docs/TestCases/ (file rollup)
 * Exception thrown when partner data is not found
 *
 * @package Ksfraser\FaBankImport\Domain\Exceptions
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class PartnerDataNotFoundException extends Exception
{
    /**
 * @BABOK BR-002 / FR-008 / FR-018 — method: forPartner
     * Create exception for partner data not found by ID
     *
     * @param int $partnerId
     * @param int $partnerType
     * @param int $partnerDetailId
     *
     * @return self
     */
    public static function forPartner(
        int $partnerId,
        int $partnerType,
        int $partnerDetailId
    ): self {
        return new self(
            sprintf(
                'Partner data not found for partner_id=%d, partner_type=%d, partner_detail_id=%d',
                $partnerId,
                $partnerType,
                $partnerDetailId
            )
        );
    }

    /**
 * @BABOK BR-002 / FR-008 / FR-018 — method: forKeyword
     * Create exception for no matches found by keyword
     *
     * @param string $keyword
     * @param int|null $partnerType
     *
     * @return self
     */
    public static function forKeyword(string $keyword, ?int $partnerType = null): self
    {
        $message = sprintf('No partner data found for keyword: "%s"', $keyword);
        
        if ($partnerType !== null) {
            $message .= sprintf(' (partner_type=%d)', $partnerType);
        }
        
        return new self($message);
    }

    /**
 * @BABOK BR-002 / FR-008 / FR-018 — method: forKeywords
     * Create exception for no matches found by keywords array
     *
     * @param array<string> $keywords
     * @param int|null $partnerType
     *
     * @return self
     */
    public static function forKeywords(array $keywords, ?int $partnerType = null): self
    {
        $keywordList = implode(', ', array_map(fn($k) => "\"{$k}\"", $keywords));
        $message = sprintf('No partner data found for keywords: %s', $keywordList);
        
        if ($partnerType !== null) {
            $message .= sprintf(' (partner_type=%d)', $partnerType);
        }
        
        return new self($message);
    }
}
