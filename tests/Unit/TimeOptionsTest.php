<?php

namespace Tests\Unit;

use App\Support\TimeOptions;
use PHPUnit\Framework\TestCase;

class TimeOptionsTest extends TestCase
{
    public function test_it_builds_a_full_day_of_five_minute_time_choices(): void
    {
        $options = TimeOptions::everyFiveMinutes();

        $this->assertCount(24, $options);
        $this->assertCount(288, array_merge(...array_values($options)));
        $this->assertSame('07:30', $options['07.00–07.59']['07:30']);
        $this->assertSame('23:55', $options['23.00–23.59']['23:55']);
    }
}
