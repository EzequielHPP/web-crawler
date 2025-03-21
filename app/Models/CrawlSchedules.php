<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrawlSchedules extends Model
{
    protected $table = 'crawl_schedule';

    protected $fillable = [
        "key",
        "url",
        "status",
        "created_at",
        "updated_at"
    ];
}
