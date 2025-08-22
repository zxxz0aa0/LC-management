<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RowCountImport implements ToCollection, WithHeadingRow
{
    private $rowCount = 0;

    public function collection(Collection $rows)
    {
        $this->rowCount = $rows->count();
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }
}