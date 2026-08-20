<?php
namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow = 0;

    public function setRows($start, $end)
    {
        $this->startRow = $start;
        $this->endRow = $end;
    }

    public function readCell($column, $row, $worksheetName = '')
    {
        if ($row >= $this->startRow && $row <= $this->endRow) {
            return true;
        }
        return false;
    }
}

class ExcelReader
{
    public static function readPreview(string $path, int $rows = 20): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);
        $out = [];
        $count = 0;
        foreach ($data as $r) {
            $out[] = array_values($r);
            $count++;
            if ($count >= $rows) break;
        }
        return $out;
    }

    public static function detectHeaders(string $path, int $rowIndex = 1): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $row = $sheet->rangeToArray('A' . $rowIndex . ':' . $sheet->getHighestColumn() . $rowIndex, null, true, true, true);
        return isset($row[1]) ? array_values($row[1]) : [];
    }

    public static function iterateRows(string $path, callable $callback, int $chunkSize = 1000)
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $chunkFilter = new ChunkReadFilter();
        $reader->setReadFilter($chunkFilter);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $start = 1;
        while ($start <= $highestRow) {
            $end = min($start + $chunkSize - 1, $highestRow);
            $chunkFilter->setRows($start, $end);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            foreach ($rows as $rowNum => $row) {
                $callback(array_values($row), $rowNum);
            }
            $start = $end + 1;
        }
    }
}
