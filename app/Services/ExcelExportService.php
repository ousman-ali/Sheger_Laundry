<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Streams a minimal XLSX file from an array of rows (each row is array of scalar values).
     * This creates a valid Office Open XML workbook with a single sheet using XML streaming.
     * Note: This is a lean implementation without styles; suitable for small/medium datasets.
     *
     * @param string $filename Suggested filename (e.g., orders_YYYYMMDD.xlsx)
     * @param array<int,string> $headers Column headers
     * @param iterable<array<int,string|int|float|null>> $rows
     */
    public static function streamSimpleXlsx(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows) {
            $boundary = uniqid('xlsx', true);
            $files = [];

            // Helper to add file to zip parts
            $add = function (string $path, string $content) use (&$files) {
                $files[$path] = $content;
            };

            // Shared strings
            $sharedStrings = [];
            $share = function ($value) use (&$sharedStrings) {
                $v = (string)($value ?? '');
                if (!isset($sharedStrings[$v])) {
                    $sharedStrings[$v] = count($sharedStrings);
                }
                return $sharedStrings[$v];
            };

            // Build sheet data (rows)
            $sheetRowsXml = '';
            $rowIndex = 1;
            $writeRow = function (array $cols) use (&$sheetRowsXml, &$rowIndex, $share) {
                $sheetRowsXml .= '<row r="'.$rowIndex.'">';
                $colIndex = 0;
                foreach ($cols as $val) {
                    $r = self::colRef($colIndex).$rowIndex;
                    if (is_numeric($val)) {
                        $sheetRowsXml .= '<c r="'.$r.'"><v>'.(0+$val).'</v></c>';
                    } else {
                        $s = $share($val);
                        $sheetRowsXml .= '<c r="'.$r.'" t="s"><v>'.$s.'</v></c>';
                    }
                    $colIndex++;
                }
                $sheetRowsXml .= '</row>';
                $rowIndex++;
            };

            // Header
            $writeRow($headers);
            // Data
            foreach ($rows as $row) {
                $writeRow(array_map(fn($v) => $v === null ? '' : $v, $row));
            }

            // Build sharedStrings.xml
            $sst = '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($sharedStrings).'" uniqueCount="'.count($sharedStrings).'">';
            foreach ($sharedStrings as $text => $idx) {
                $sst .= '<si><t>'.self::xml($text).'</t></si>';
            }
            $sst .= '</sst>';

            // Core files
            $add('[Content_Types].xml', self::contentTypes());
            $add('_rels/.rels', self::rels());
            $add('xl/_rels/workbook.xml.rels', self::workbookRels());
            $add('xl/workbook.xml', self::workbook());
            $add('xl/styles.xml', self::styles());
            $add('xl/worksheets/sheet1.xml', self::sheet($sheetRowsXml));
            $add('xl/sharedStrings.xml', $sst);

            // Create ZIP (PKZip) manually
            self::zipStream($files);
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        return $response;
    }

    private static function xml(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function colRef(int $i): string
    {
        $s = '';
        $i2 = $i;
        do {
            $s = chr(65 + ($i2 % 26)) . $s;
            $i2 = intdiv($i2, 26) - 1;
        } while ($i2 >= 0);
        return $s;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            .'</Types>';
    }

    private static function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"></styleSheet>';
    }

    private static function sheet(string $rowsXml): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.$rowsXml.'</sheetData>'
            .'</worksheet>';
    }

    // Minimal zip streaming (store method, no compression)
    private static function zipStream(array $files): void
    {
        $offset = 0;
        $central = '';
        foreach ($files as $name => $content) {
            $data = $content;
            $crc = crc32($data);
            $len = strlen($data);
            // Local file header
            echo pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $len, $len, strlen($name), 0);
            echo $name;
            echo $data;
            // Central dir header
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 0, 20, 0, 0, 0, 0, $crc, $len, $len, strlen($name), 0, 0, 0, 0, 0, $offset);
            $central .= $name;
            $offset += 30 + strlen($name) + $len; // 30 bytes header + name + data
        }
        // EOCD
        echo $central;
        echo pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), $offset, 0);
    }
}
