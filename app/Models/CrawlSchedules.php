<?php

namespace App\Models;

class CrawlSchedules extends JsonModel
{
    protected string $table = 'crawl_schedule';

    protected array $schema = [
        "id",
        "key",
        "url",
        "status",
        "created_at",
        "updated_at"
    ];
}
