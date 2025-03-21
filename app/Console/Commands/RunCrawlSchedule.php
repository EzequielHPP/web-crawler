<?php

namespace App\Console\Commands;

use App\Http\Services\CrawlService;
use App\Models\CrawlSchedules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunCrawlSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-crawl-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the first URL from the crawl_schedule table and run the crawl command on it.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = (new CrawlSchedules())->where('status', 'scheduled')->first();
        if ($url) {
            $this->info('Running the crawl command on ' . $url['url']);
            (new CrawlSchedules())->find($url['id'])->update(['status' => 'running']);

            $service = new CrawlService();
            $service->getData($url['url']);
            $children = $service->getChildUrls($url['url']);

            if ($children) {
                // schedule the children for crawling
                foreach ($children as $child) {
                    $service->scheduleCrawl($child, $url['key']);
                }
            }
            (new CrawlSchedules())->delete($url['id']);
        } else {
            $this->info('No URLs to crawl');
        }
    }
}
