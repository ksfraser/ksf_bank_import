<?php

$GLOBALS['mock_mail_log'] = [];

function mail($to, $subject, $message, $headers)
{
    $GLOBALS['mock_mail_log'][] = [
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'headers' => $headers
    ];
    return true;
}