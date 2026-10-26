<?php

namespace App\Console\Commands;

use App\Services\SeoHealthCheck;
use Illuminate\Console\Command;

class SeoAudit extends Command
{
    protected $signature = 'seo:audit';

    protected $description = 'Check public pages, the sitemap, and robots.txt for SEO regressions';

    public function handle(SeoHealthCheck $check): int
    {
        $findings = $check->findings();
        if ($findings === []) {
            $this->info('No SEO findings. This does not mean the pages are indexed.');

            return self::SUCCESS;
        }

        $errors = 0;
        foreach ($findings as $finding) {
            $line = strtoupper($finding['severity']).' '.$finding['url'].' — '.$finding['description'].' '.$finding['fix'];
            if ($finding['severity'] === 'error') {
                $this->error($line);
                $errors++;
            } else {
                $this->warn($line);
            }
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
