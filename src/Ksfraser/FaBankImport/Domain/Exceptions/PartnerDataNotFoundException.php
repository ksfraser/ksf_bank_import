<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :PartnerDataNotFoundException [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for PartnerDataNotFoundException.
 */
namespace Ksfraser\FaBankImport\Domain\Exceptions;

use Exception;

/**
 * Exception thrown when partner data is not found
 *
 * @package Ksfraser\FaBankImport\Domain\Exceptions
 * @author  Kevin Fraser
 * @version 1.0.0
 */
class PartnerDataNotFoundException extends Exception
{
    /**
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
     * Create exception for no matches found by keywords array
     *
     * @param array<string> $keywords
     * @param int|null $partnerType
     *
     * @return self
     */
    public static function forKeywords(array $keywords, ?int $partnerType = null): self
    {
        $keywordList = implode(', ', array_map(function ($k): string {
            return '"' . $k . '"';
        }, $keywords));
        $message = sprintf('No partner data found for keywords: %s', $keywordList);
        
        if ($partnerType !== null) {
            $message .= sprintf(' (partner_type=%d)', $partnerType);
        }
        
        return new self($message);
    }
}
