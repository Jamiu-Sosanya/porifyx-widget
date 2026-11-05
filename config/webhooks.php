<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lead webhook noise reduction
    |--------------------------------------------------------------------------
    |
    | When a widget does not use "notify on every lead refresh", webhooks for
    | event "lead.qualified" are sent only when: the lead row was just created,
    | the score changes by at least this many points, or temperature changes.
    |
    */

    'lead_score_jump_threshold' => (int) env('WEBHOOK_LEAD_SCORE_JUMP', 10),

];
