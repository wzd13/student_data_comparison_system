<?php
namespace App;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Exporter
{
    public static function exportCsv(array $rows, string $filename)
    {
        $fh = fopen($filename, 'w');
        if (empty($rows)) return false;
        fputcsv($fh, array_keys($rows[0]));
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        fclose($fh);
        return true;
    }

    public static function exportXlsx(array $rows, string $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if (!empty($rows)) {
            $sheet->fromArray(array_keys($rows[0]), null, 'A1');
            $sheet->fromArray($rows, null, 'A2');
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);
        return true;
    }
}
