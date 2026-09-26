<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('platform:cleanup')->everyMinute();
