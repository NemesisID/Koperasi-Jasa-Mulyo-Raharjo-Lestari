<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

/** Export baris laporan apa pun (array of arrays) ke XLSX. */
class RowsExport implements FromArray
{
    public function __construct(
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }
}
