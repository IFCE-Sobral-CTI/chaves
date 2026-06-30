<?php

namespace App\Support;

use Illuminate\Support\Stream;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * Export an array of arrays to a CSV StreamedResponse.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $headers  Map of column key => label
     * @param  string  $filename
     */
    public static function download(array $rows, array $headers, string $filename = 'relatorio.csv'): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($rows, $headers) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, array_values($headers));

            // Data rows
            foreach ($rows as $row) {
                $csvRow = [];
                foreach (array_keys($headers) as $key) {
                    $csvRow[] = $row[$key] ?? '';
                }
                fputcsv($handle, $csvRow);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }
}
