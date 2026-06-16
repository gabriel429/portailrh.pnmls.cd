<?php

namespace Tests\Unit;

use App\Models\Holiday;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class HolidayWorkingDaysTest extends TestCase
{
    public function test_weekends_are_not_counted_in_holiday_duration(): void
    {
        $days = Holiday::calculateWorkingDays(
            Carbon::parse('2026-06-19'),
            Carbon::parse('2026-06-22')
        );

        $this->assertSame(2, $days);
    }

    public function test_weekend_only_period_has_no_working_day(): void
    {
        $days = Holiday::calculateWorkingDays(
            Carbon::parse('2026-06-20'),
            Carbon::parse('2026-06-21')
        );

        $this->assertSame(0, $days);
    }
}
