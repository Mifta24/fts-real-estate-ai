<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Visitor Data Retention
    |--------------------------------------------------------------------------
    |
    | How long personal data is kept before `php artisan privacy:prune` (run
    | daily by the scheduler) removes it. Set a value to 0 to keep that kind of
    | data forever.
    |
    | conversation_days: days after the last message that a chat, its handover
    |   request and its messages are deleted. Conversations with an open
    |   handover are kept until an agent resolves them.
    | viewing_days: days after the viewing date that the visitor's name,
    |   contact details and notes are erased. The booking itself (reference,
    |   listing, date, status) stays for the agency's records.
    |
    */

    'conversation_days' => (int) env('RETENTION_CONVERSATION_DAYS', 90),

    'viewing_days' => (int) env('RETENTION_VIEWING_DAYS', 365),

];
