<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('platform:cleanup')->everyMinute();
Schedule::command('analytics:rollup')->dailyAt('00:20');
