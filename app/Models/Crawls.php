<?php

namespace App\Models;


class Crawls extends JsonModel
{
    protected string $table = 'crawl_entries';

    protected array $schema = [
        "id",
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
