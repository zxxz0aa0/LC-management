<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TimeStatisticsExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new PeakHoursSheet($this->data['peak_hours']),
            new WeekdayDistributionSheet($this->data['weekday_distribution']),
            new MonthlyTrendsSheet($this->data['monthly_trends']),
            new AdvanceBookingSheet($this->data['advance_booking']),
        ];
    }
}
