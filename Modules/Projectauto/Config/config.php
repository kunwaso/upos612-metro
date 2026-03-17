<?php

return [
    'name' => 'Projectauto',
    'module_version' => '1.0.0',

    'task' => [
        'default_expiry_hours' => 72,
        'escalation_action' => env('PROJECTAUTO_ESCALATION_ACTION', 'none'),
        'escalation_chunk' => 100,
    ],

    'rules' => [
        'max_per_event' => 100,
    ],
];
