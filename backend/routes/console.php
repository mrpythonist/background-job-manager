<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('workers:prune-stale')->everyFiveMinutes();
