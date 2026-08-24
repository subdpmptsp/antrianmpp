<?php

return [
    'operator_idle_timeout_minutes' => (int) env('OPERATOR_IDLE_TIMEOUT', 60),
    'operator_absolute_session_minutes' => (int) env('OPERATOR_ABSOLUTE_SESSION', 720),
];
