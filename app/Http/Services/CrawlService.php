<?php

namespace App\Http\Services;

use App\Models\Crawls;
use App\Models\CrawlSchedules;
use DOMDocument;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class CrawlService
{
    private string|null $data;
    private DomDocument|null $dom;
    private string $domain;
    private string $protocol;

    /**
     * Check if the given URL is valid
     *
     * @param string $url
     * @return bool
     */
    final public function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if the given URL can be crawled given the /robots.txt file
     *
     */
    final public function canCrawl(string $url): bool
    {
        return $this->getRobotsPreventionRule($url) === null;
    }

    /**
     * Get Robots Prevention rule
     *
     * @param string $url
     * @return string|null
     */
    final public function getRobotsPreventionRule(string $url): string|null
    {

        $urlParsed = parse_url($url);
        $path = $urlParsed['path'] ?? '/';
        $robotsUrl = $urlParsed['scheme'] . '://' . $urlParsed['host'] . '/robots.txt';
        $this->crawl($robotsUrl);
        if (empty($this->data)) {
            return null;
        }

        // Group the disallowed paths by user agent
        $lines = explode("\n", $this->data);
        $userAgents = [];
        $currentUserAgent = '';
        foreach ($lines as $line) {
            if (str_starts_with($line, 'User-agent:')) {
                $currentUserAgent = trim(str_replace('User-agent:', '', $line));
                $userAgents[$currentUserAgent] = [];
            } else if (str_starts_with($line, 'Disallow:')) {
                $userAgents[$currentUserAgent][] = trim(str_replace('Disallow:', '', $line));
            }
        }

        // We only care about the * and Googlebot user agents
        $agents = ['*', 'Googlebot'];
        foreach ($agents as $agent) {
            if (isset($userAgents[$agent])) {
                $disallowed = $userAgents[$agent];
                if(count($disallowed) === 1 && $disallowed[0] === '/') {
                    return $agent.': '.$disallowed[0];
                }
                foreach ($disallowed as $disallowedPath) {
                    if (str_starts_with($path, $disallowedPath)) {
                        return $agent.': '.$disallowedPath;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get data from the given URL
     *
     * @param string $url
     * @return array<string, string>
     */
    final public function getData(string $url): array
    {
        $this->crawl($url);
        if($this->data === null || $this->dom === null) {
            return [
                'status' => 'failed'
            ];
        }
        return $this->getUrlData($url);
    }

    /**
     * Crawl the given URL, and load the HTML into the DOM
     *
     * @param string $url
     * @return void
     */
    private function crawl(string $url): void
    {
        try {
            $this->data = file_get_contents($url);

            $parts = explode('.', $url);
            $extension = end($parts);
            if (!in_array($extension, ['txt', 'jpg', 'png', 'gif', 'jpeg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                $this->dom = new \DomDocument('1.0', 'UTF-8');
                @$this->dom->loadHTML($this->data);
            }
        } catch (Throwable $e) {
            $this->data = null;
            $this->dom = null;
        }
    }

    /**
     * Get the data from the given URL
     *
     * @param string $url
     * @return array<string, string>
     */
    private function getUrlData(string $url): array
    {
        $urlParsed = parse_url($url);

        $this->protocol = $urlParsed['scheme'];
        $this->domain = rtrim($urlParsed['host'], '/');
        $path = explode('/', $urlParsed['path'] ?? '');

        // remove empty path elements
        $path = array_filter($path, static function ($path) {
            return $path !== '';
        });

        $rawUrl = $this->protocol . '://' . $this->domain . (!empty($path) ? '/' . implode('/', $path) : '');

        $key = md5($rawUrl);

        // check if we already have the data for this URL
        $existingEntry = (new Crawls())->where('key', $key)->first();

        if ($existingEntry !== null) {
            return $existingEntry->toArray();
        }

        // extract if any url parameters
        $parameters = [];
        if (isset($urlParsed['query'])) {
            $query = $urlParsed['query'];
            parse_str($query, $parameters);
        }

        $type = 'internal';
        if (isset($urlParsed['host']) && $urlParsed['host'] !== $this->domain) {
            $type = 'external';
        }

        $newEntry = [
            'key' => $key,
            'domain' => $this->domain,
            'name' => trim($this->getName()),
            'path' => json_encode(array_values($path)),
            'status' => $this->data ? 'success' : 'failed',
            'title' => trim($this->getTitle()),
            'type' => $type,
            'description' => trim($this->getDescription()),
            'favicon' => trim($this->getFavicon()),
            'image' => trim($this->getImage()),
            'keywords' => $this->getKeywords()
        ];

        $newCrawl = new Crawls();
        foreach ($newEntry as $key => $value){
            $newCrawl->$key = $value;
        }
        $newCrawl->save();

        $newEntry['page_size'] = strlen($this->data) . ' bytes';

        return $newEntry;
    }

    /**
     * Get the name of the page
     *
     * @return string
     */
    private function getName(): string
    {
        $outputName = '';
        $siteName = $this->dom->getElementsByTagName('meta');
        foreach ($siteName as $meta) {
            if ($meta->getAttribute('property') === 'og:site_name') {
                $outputName = $meta->getAttribute('content');
                break;
            }
        }

        if (empty($outputName)) {
            $outputName = $this->domain;
        }

        return $outputName;
    }


    /**
     * Get the title of the page
     * If there is no title, then we need to use the domain
     * and truncate it to 200 characters
     *
     * @return string
     */
    private function getTitle(): string
    {
        $title = $this->dom->getElementsByTagName('title');
        // if there is no title, then we need to use the domain
        if ($title->length === 0) {
            return $this->domain;
        }
        return substr($title->item(0)->nodeValue, 0, 200);
    }

    /**
     * Get the description of the page
     * If there is no description, then we need to use the body
     * and truncate it to 200 characters
     *
     * @return string
     */
    private function getDescription(): string
    {
        $description = $this->dom->getElementsByTagName('meta');
        $descriptionText = '';
        foreach ($description as $meta) {
            if ($meta->getAttribute('name') === 'description') {
                $descriptionText = $meta->getAttribute('content');
                break;
            }
        }
        if (empty($descriptionText)) {
            $body = $this->dom->getElementsByTagName('body');
            $descriptionText = $body->item(0)->nodeValue;
        }
        return substr($descriptionText, 0, 200);
    }

    /**
     * Get the favicon of the page
     *
     * @return string
     */
    private function getFavicon(): string
    {
        $favicon = $this->dom->getElementsByTagName('link');
        $image = '';
        foreach ($favicon as $link) {
            if ($link->getAttribute('rel') === 'icon') {
                $image = $link->getAttribute('href');
            }
        }
        if ($image !== '' && !str_contains($image, $this->protocol)) {
            $image = $this->protocol . '://' . $this->domain . '/' . ltrim($image, '/');
        }

        // check if the image is a valid image
        if ($image !== '') {
            try {
                $imageData = getimagesize($image);
                if ($imageData === false) {
                    $image = '';
                }
            } catch (Throwable $e) {
                $image = '';
            }
        }

        return $image;
    }

    /**
     * Get the og:image of the page
     * If there is no image, then we use the first image on the page
     *
     * @return string
     */
    private function getImage(): string
    {
        $image = $this->dom->getElementsByTagName('meta');
        $imageUrl = '';
        foreach ($image as $meta) {
            if ($meta->getAttribute('property') === 'og:image') {
                $imageUrl = $meta->getAttribute('content');
            }
        }

        if (empty($imageUrl)) {
            $images = $this->dom->getElementsByTagName('img');
            if ($images->length > 0) {
                $firstImage = $images->item(0);
                if ($firstImage) {
                    $imageUrl = $firstImage->getAttribute('src');
                }
            }
        }

        if ($imageUrl !== '' && !str_contains($imageUrl, $this->protocol)) {
            $imageUrl = $this->protocol . '://' . $this->domain . '/' . ltrim($imageUrl, '/');
        }

        // check if the image is a valid image
        if ($imageUrl !== '') {
            try {
                $imageData = getimagesize($imageUrl);
                if ($imageData === false) {
                    $imageUrl = '';
                }
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), '404')) {
                    $imageUrl = '';
                }
            }
        }

        return $imageUrl;
    }

    /**
     * Get the keywords of the page,
     * if there are no keywords, then we need to find them in the body
     *
     * @return string
     */
    private function getKeywords(): string
    {
        $outputKeywords = '';
        $keywords = $this->dom->getElementsByTagName('meta');
        foreach ($keywords as $meta) {
            if ($meta->getAttribute('name') === 'keywords') {
                $outputKeywords = $meta->getAttribute('content');
                break;
            }
        }
        if(empty($outputKeywords)) {
            $body = $this->dom->getElementsByTagName('body');
            $bodyText = $body->item(0)->nodeValue;

            $words = explode(' ', $bodyText);
            $wordCount = array_count_values($words);

            arsort($wordCount);

            $keywords = array_keys(array_slice($wordCount, 0, 10));
            $outputKeywords = implode(', ', $keywords);
        }

        // if length is greater than 200, then we need to truncate, but split it on a comma
        if(strlen($outputKeywords) > 200) {
            $keywords = explode(',', $outputKeywords);
            $outputKeywords = '';
            foreach($keywords as $keyword) {
                if(strlen($outputKeywords . $keyword) > 200) {
                    break;
                }
                $outputKeywords .= $keyword . ', ';
            }
            $outputKeywords = rtrim($outputKeywords, ', ');
        }

        $outputKeywords = trim($outputKeywords,', ');
        return trim($outputKeywords,', ');
    }

    /**
     * Get the child URLs of the given URL
     *
     * @param string $url
     * @return array<string>
     */
    final public function getChildUrls(string $url): array
    {
        if($this->data === null || $this->dom === null) {
            return [];
        }

        $this->crawl($url);

        $links = $this->dom->getElementsByTagName('a');
        $childUrls = [];
        $startDomainString = $this->protocol . '://' . $this->domain;
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (str_starts_with($href, '/') || str_starts_with($href, $startDomainString)) {
                if (str_starts_with($href, '/')) {
                    $href = $startDomainString . '/' . ltrim($href, '/');
                }
                $childUrls[] = $href;
            }
        }

        return $childUrls;
    }

    /**
     * Schedule the given URL for crawling
     *
     * @param string $url
     * @param string $sourceKey
     * @return void
     */
    final public function scheduleCrawl(string $url, string $sourceKey): void
    {
        $urlParsed = parse_url($url);

        $protocol = $urlParsed['scheme'];
        $domain = rtrim($urlParsed['host'], '/');
        $path = explode('/', $urlParsed['path'] ?? '');

        $rawUrl = $protocol . '://' . $domain . (!empty($path) ? '/' . implode('/', $path) : '');

        $key = md5($rawUrl);

        $existingEntry = (new Crawls())->where('key', $key)->first();
        if ($existingEntry) {
            return;
        }

        $existingSchedule = (new CrawlSchedules())->where('url', $url)->first();
        if ($existingSchedule) {
            return;
        }

        (new CrawlSchedules())->create([
            'key' => $sourceKey,
            'url' => $url,
            'status' => 'scheduled'
        ]);
    }

    /**
     * Format the results for the given key
     *
     * @param string $key
     * @return array
     */
    final public function getResults(string $key): array
    {
        $results = (new Crawls())->where('key', $key)->orderBy('created_at', 'DESC')->limit(10)->all();
        $output = [];
        foreach ($results as $result) {
            $output[] = $this->formatResultToApi($result);
        }
        return $output;
    }

    /**
     * Format the result for the API.
     * Output the data in a more user-friendly format
     *
     * @param array $result
     * @return array
     */
    final public function formatResultToApi(array $result): array
    {
        if($result['status'] === 'failed') {
            return [
                'status' => 'failed',
                'message' => 'Failed to retrieve data'
            ];
        }
        return [
            'key' => $result['key'],
            'url' => $result['domain'] . '/' . implode('/', json_decode($result['path'], true)),
            'domain' => $result['domain'],
            'path' => json_decode($result['path'], true),
            'name' => $result['name'],
            'title' => $result['title'],
            'description' => $result['description'],
            'favicon' => $result['favicon'],
            'image' => $result['image'],
            'keywords' => $result['keywords']
        ];
    }


    /**
     * Get the count of scheduled URLs
     *
     * @return int
     */
    final public function getScheduledCount(): int
    {
        return (new CrawlSchedules())->where('status', 'scheduled')->count();
    }

    /**
     * Get the count of running URLs
     *
     * @return int
     */
    final public function getRunningCount(): int
    {
        return (new CrawlSchedules())->where('status', 'running')->count();
    }

    /**
     * Stop the crawl for the given key
     *
     * @param string $key
     * @return void
     */
    final public function stopCrawl(string $key): void
    {
        $keyResult = (new Crawls())->where('key', $key)->first();
        if (!$keyResult) {
            return;
        }

        $domain = $keyResult['domain'];

        (new Crawls())->delete($domain,'domain');
        (new CrawlSchedules())->delete($key, 'key');
    }

    /**
     * Run the crawl schedule
     *
     * @return void
     */
    final public function runCrawlSchedule(): void
    {
        Artisan::call('app:run-crawl-schedule');
    }

    /**
     * Delete old entries from the database
     *
     * @return void
     */
    final public function deleteOldEntries(): void
    {
        $oldEntries = (new Crawls())->where('created_at', '<', now()->subMinutes(15))->get();

        foreach ($oldEntries as $entry) {
            $entry->delete();
        }
    }
}
