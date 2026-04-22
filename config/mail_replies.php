<?php

return [
    'enabled' => env('MAIL_REPLIES_ENABLED', false),

    // Exemple: {imap.hostinger.com:993/imap/ssl}INBOX
    'mailbox' => env('MAIL_REPLIES_IMAP_MAILBOX'),
    'username' => env('MAIL_REPLIES_IMAP_USERNAME'),
    'password' => env('MAIL_REPLIES_IMAP_PASSWORD'),

    // UNSEEN pour ne traiter que les nouveaux emails
    'search' => env('MAIL_REPLIES_IMAP_SEARCH', 'UNSEEN'),
    'limit' => (int) env('MAIL_REPLIES_IMAP_LIMIT', 30),

    // Adresse de reponse centralisee (optionnelle mais recommandee)
    'reply_to_address' => env('MAIL_REPLIES_REPLY_TO_ADDRESS'),
    'reply_to_name' => env('MAIL_REPLIES_REPLY_TO_NAME', 'E-PNMLS Reponses'),
];
