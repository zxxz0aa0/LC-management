<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class GeographyStatisticsExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new PickupLocationsSheet($this->data['pickup_locations']),
            new DropoffLocationsSheet($this->data['dropoff_locations']),
            new CrossCountySheet($this->data['cross_county']),
            new PopularRoutesSheet($this->data['popular_routes']),
        ];
    }
}
