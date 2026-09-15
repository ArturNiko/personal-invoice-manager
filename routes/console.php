<?php

use Illuminate\Support\Facades\Schedule;


Schedule::command('app:poll-nanonets-tasks')
    ->cron(sprintf('*/%d * * * *', (int) env('NANONETS_POLL_MINUTES', 1)))
    ->withoutOverlapping();
