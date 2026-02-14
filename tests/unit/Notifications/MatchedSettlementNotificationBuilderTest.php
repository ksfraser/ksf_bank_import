<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use Ksfraser\FA\Notifications\MatchedSettlementNotificationBuilder;
use PHPUnit\Framework\TestCase;

final class MatchedSettlementNotificationBuilderTest extends TestCase
{
    public function testBuildMessageUsesCanonicalFormat(): void
    {
        $message = MatchedSettlementNotificationBuilder::buildMessage(22, 501);

        $this->assertSame('Transaction was MATCH settled 22::501', $message);
    }

    public function testBuildReturnsMessageLinkDataAndContext(): void
    {
        $payload = MatchedSettlementNotificationBuilder::build(ST_CUSTPAYMENT, 99);

        $this->assertSame('Transaction was MATCH settled ' . ST_CUSTPAYMENT . '::99', $payload['message']);
        $this->assertSame(
            '../../gl/view/gl_trans_view.php?type_id=' . ST_CUSTPAYMENT . '&trans_no=99',
            $payload['linkData']['view_gl_link']
        );
        $this->assertSame('matched_settlement', $payload['context']['context']);
    }
}
