<?php

return [
    'imap' => [
        'host' => env('MAILBOX_IMAP_HOST', 'camulus.o2switch.net'),
        'port' => (int) env('MAILBOX_IMAP_PORT', 993),
        'encryption' => env('MAILBOX_IMAP_ENCRYPTION', 'ssl'),
    ],

    'smtp' => [
        'host' => env('MAILBOX_SMTP_HOST', 'camulus.o2switch.net'),
        'port' => (int) env('MAILBOX_SMTP_PORT', 465),
        'encryption' => env('MAILBOX_SMTP_ENCRYPTION', 'ssl'),
    ],

    'per_page' => (int) env('MAILBOX_PER_PAGE', 15),
];
