<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Crawls extends Model
{
    protected $table = 'crawl_entries';

    protected $fillable=[
        "key",
        "name",
        "domain",
        "path",
        "status",
        "title",
        "type",
        "description",
        "favicon",
        "image",
        "keywords",
        "created_at",
        "updated_at"
    ];

}
