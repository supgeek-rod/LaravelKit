<?php

namespace App\Tenements\ePartV2\Commands;

use App\Tenements\ePartV2\Models\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SitemapMaker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ePartV2:sitemap-maker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成 parts 的站点地图';

    protected $chunkIndex = 0;
    protected $chunkSize = 10000;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('Command Start ...');
        $this->newLine();

        Part::query()->select('id')->chunkById($this->chunkSize, function ($parts) {
            $this->chunkIndex += 1;

            $this->makeSitemapAndSave($parts);

            $this->line("#{$this->chunkIndex} done.");
        });

        $this->newLine()->line('All done.');
    }

    public function makeSitemapAndSave($parts)
    {
        $filePath = "sitemaps/{$this->chunkIndex}.xml";
        $date = date('Y-m-d');

        Storage::delete($filePath);
        Storage::put($filePath, '<?xml version="1.0" encoding="UTF-8"?>');
        Storage::append($filePath, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

        $partUrls = null;
        foreach ($parts as $part) {
            $partUrls .= <<<EOD
                <url>
                    <loc>https://www.bpachip.com/products/{$part->id}.html</loc>
                    <changefreq>always</changefreq>
                    <priority>1.0</priority>
                    <lastmod>{$date}</lastmod>
                </url>
            EOD . PHP_EOL;
        }

        Storage::append($filePath, $partUrls);
        Storage::append($filePath, '</urlset>');
    }
}
