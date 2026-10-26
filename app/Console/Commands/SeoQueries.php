<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeoQueries extends Command
{
    protected $signature = 'seo:queries {csv}';

    protected $description = 'Map a Search Console CSV to existing pages without inventing search volume';

    public function handle(): int
    {
        $path = $this->argument('csv');
        if (! is_file($path)) {
            $this->error('CSV file not found.');

            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $this->error('CSV file could not be read.');

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            $this->error('CSV has no header row.');

            return self::FAILURE;
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $columns = [];
        foreach ($header as $index => $name) {
            $columns[strtolower(trim((string) $name))] = $index;
        }
        if (! array_key_exists('query', $columns)) {
            fclose($handle);
            $this->error('CSV needs a query column.');

            return self::FAILURE;
        }

        $lines = [
            '# Search Console query report',
            '',
            'These numbers are copied from the CSV. They are observed exports, not estimates, and they are not rankings the app measured itself.',
            '',
        ];
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $query = trim((string) ($row[$columns['query']] ?? ''));
            if ($query === '') {
                continue;
            }
            $count++;
            $page = $this->cell($row, $columns, 'page');
            $clicks = $this->cell($row, $columns, 'clicks');
            $impressions = $this->cell($row, $columns, 'impressions');
            $ctr = $this->cell($row, $columns, 'ctr');
            $position = $this->cell($row, $columns, 'position');
            $target = $this->target($query);
            $note = $target ? 'Map to '.$target.'.' : 'Do not create a page yet.';
            $lines[] = '- '.$query.' | page: '.($page !== '' ? $page : 'n/a').' | clicks: '.($clicks !== '' ? $clicks : 'n/a').' | impressions: '.($impressions !== '' ? $impressions : 'n/a').' | ctr: '.($ctr !== '' ? $ctr : 'n/a').' | position: '.($position !== '' ? $position : 'n/a').' | '.$note;
        }
        fclose($handle);

        if ($count === 0) {
            $this->error('CSV has no query rows.');

            return self::FAILURE;
        }

        $lines[] = '';
        $lines[] = 'Rows: '.$count;
        File::ensureDirectoryExists(storage_path('app/seo'));
        File::put(storage_path('app/seo/query-report.md'), implode("\n", $lines)."\n");
        $this->info('Wrote storage/app/seo/query-report.md');

        return self::SUCCESS;
    }

    private function cell(array $row, array $columns, string $name): string
    {
        if (! array_key_exists($name, $columns)) {
            return '';
        }

        return trim((string) ($row[$columns[$name]] ?? ''));
    }

    private function target(string $query): ?string
    {
        $text = strtolower($query);
        foreach ([
            'omegle' => '/omegle-alternative',
            'talk to stranger' => '/talk-to-strangers',
            'stranger' => '/talk-to-strangers',
            'random video' => '/random-video-chat',
            'video chat' => '/random-video-chat',
            'camera' => '/help',
            'microphone' => '/help',
            'privacy' => '/privacy',
            'safety' => '/safety',
            'feature' => '/features',
            'guideline' => '/guidelines',
            'about' => '/about',
            'contact' => '/contact',
        ] as $needle => $url) {
            if (str_contains($text, $needle)) {
                return $url;
            }
        }

        return null;
    }
}
