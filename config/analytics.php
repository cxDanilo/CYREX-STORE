<?php

return [
    // Cuántos días se guardan las filas de analítica antes de que el
    // propio middleware las borre de a poco (no hay cron real corriendo
    // en el servidor que pueda encargarse de esto con un comando aparte).
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 90),
];
