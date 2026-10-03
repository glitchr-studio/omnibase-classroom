<?php

namespace Base\Classroom\Service;

use Base\Classroom\Entity\Resource;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * The first rows of a spreadsheet resource, as a table on its page: a
 * colleague sees what the Kangourou file holds before downloading it. xlsx,
 * xls, xlsm and ods through PhpSpreadsheet, csv read directly; the result
 * cached until the resource changes.
 *
 * @phpstan-type Sheet array{name: string, rows: list<list<string>>, more: bool}
 */
class SpreadsheetPreview
{
    public function __construct(
        private readonly CacheInterface $cache,
        #[Autowire('%classroom.preview_rows%')] private readonly int $rows = 30,
    ) {
    }

    /** @return list<Sheet> one entry per sheet (one for a csv), empty when nothing can be read */
    public function sheets(Resource $resource): array
    {
        if (!$resource->isSpreadsheet() || !$resource->getId()) {
            return [];
        }
        $key = sprintf('classroom.preview.%d.%s', $resource->getId(), $resource->getUpdatedAt()?->format('U') ?? '0');

        return $this->cache->get($key, function (ItemInterface $item) use ($resource): array {
            $item->expiresAfter(86400);
            $file = $resource->getFile();
            if (!$file || !is_file($file->getPathname())) {
                return [];
            }
            try {
                return 'csv' === $resource->getExtension() ? [$this->csv($file->getPathname())] : $this->workbook($file->getPathname());
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /** @return Sheet */
    private function csv(string $path): array
    {
        $handle = new \SplFileObject($path);
        $handle->setCsvControl(',', '"', '\\');
        $handle->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD | \SplFileObject::DROP_NEW_LINE);
        // The separator French spreadsheets save with, when the first line says so.
        $first = $handle->fgets();
        $handle->rewind();
        if (substr_count($first, ';') > substr_count($first, ',')) {
            $handle->setCsvControl(';', '"', '\\');
        }
        $rows = [];
        $more = false;
        foreach ($handle as $row) {
            if (!\is_array($row) || [null] === $row) {
                continue;
            }
            if (\count($rows) >= $this->rows) {
                $more = true;
                break;
            }
            $rows[] = array_map(fn ($cell) => trim((string) $cell), $row);
        }

        return ['name' => 'csv', 'rows' => $rows, 'more' => $more];
    }

    /** @return list<Sheet> */
    private function workbook(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheets = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $highestRow = $sheet->getHighestDataRow();
            $rows = [];
            $max = min($highestRow, $this->rows);
            for ($r = 1; $r <= $max; ++$r) {
                $row = $sheet->rangeToArray('A'.$r.':'.$sheet->getHighestDataColumn().$r, '', true, true, false)[0] ?? [];
                $rows[] = array_map(fn ($cell) => trim((string) $cell), $row);
            }
            // Trailing empty columns and rows trimmed: a sheet's "highest" cell is often formatting.
            $rows = array_values(array_filter($rows, fn ($row) => '' !== implode('', $row)));
            if ($rows) {
                $sheets[] = ['name' => $sheet->getTitle(), 'rows' => $rows, 'more' => $highestRow > $this->rows];
            }
        }
        $spreadsheet->disconnectWorksheets();

        return $sheets;
    }
}
