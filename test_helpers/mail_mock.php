<?php

namespace {
    if (!isset($GLOBALS['mock_mail_log']) || !is_array($GLOBALS['mock_mail_log'])) {
        $GLOBALS['mock_mail_log'] = [];
    }
}

namespace Ksfraser\FaBankImport\Services {
    function mail($to, $subject, $message, $headers = ''): bool
    {
        $GLOBALS['mock_mail_log'][] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
        ];
        return true;
    }
}

namespace Ksfraser\Application\Services {
    function mail($to, $subject, $message, $headers = ''): bool
    {
        $GLOBALS['mock_mail_log'][] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
        ];
        return true;
    }
}