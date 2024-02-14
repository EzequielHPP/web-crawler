<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScanRequest;
use App\Http\Services\CrawlService;
use App\Models\Crawls;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * Perform a scan on the given URL
     * This is the starting point
     *
     * @param ScanRequest $request
     * @return JsonResponse
     */
    final public function scan(ScanRequest $request): JsonResponse
    {
        try {
            $url = $request->input('url');
            $service = new CrawlService();
            $service->deleteOldEntries();

            if (!$service->isValidUrl($url)) {
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid URL'
                ];
                goto end;
            }

            if ($service->canCrawl($url) === false) {
                $response = [
                    'status' => 'error',
                    'message' => 'This URL cannot be crawled. Prevented by robots.txt - ' . $service->getRobotsPreventionRule($url)
                ];
                goto end;
            }

            $urlData = $service->getData($url);
            $children = $service->getChildUrls($url);

            if ($children) {
                // schedule the children for crawling
                foreach ($children as $child) {
                    $service->scheduleCrawl($child, $urlData['key']);
                }
            }

            $response = $service->formatResultToApi($urlData);
        } catch (\Throwable $e) {
            $response = [
                'status' => 'error',
                'message' => 'An internal error occurred. Please try again later.'
            ];
        }

        end:
        return response()->json($response);
    }

    /**
     * Get the status of the scan
     * This will return the number of scheduled and running scans
     *
     * @return JsonResponse
     */
    final public function scanStatus(): JsonResponse
    {
        try {
            $service = new CrawlService();
            $scheduled = $service->getScheduledCount();
            $running = $service->getRunningCount();
            $response = [
                'status' => 'success',
                'scheduled' => $scheduled,
                'running' => $running
            ];
        } catch (\Throwable $e) {
            $response = [
                'status' => 'error',
                'message' => 'An internal error occurred. Please try again later.'
            ];
        }
        return response()->json($response);
    }

    /**
     * Get the results of the scan
     * This will return the results of the last 20 scans
     *
     * @param string $key
     * @return JsonResponse
     */
    final public function getResults(string $key): JsonResponse
    {
        try {
            $keyResult = (new Crawls())->where('key', $key)->first();
            if (!$keyResult) {
                return response()->json(['status' => 'error', 'message' => 'Key not found']);
            }

            $domain = $keyResult['domain'];

            $results = (new Crawls())->where('domain', $domain)->all();
            // order collection by created_at
            $results = collect($results)->sortByDesc('created_at')->values()->all();
            // limit to 20
            $results = array_slice($results, 0, 20);

            $status = 'success';

            $service = new CrawlService();
            $running = $service->getRunningCount();
            $queued = $service->getScheduledCount();

            if ($running === 0 && $queued === 0) {
                $status = 'done';
            }

            foreach ($results as $tmpKey => $result) {
                $results[$tmpKey] = $service->formatResultToApi($result);
            }

            $service->runCrawlSchedule();

            if ($status === 'done') {
                $this->stopCrawl($key);
            }
            $response = ['status' => $status, 'data' => $results];
        } catch (\Throwable $e) {
            $response = [
                'status' => 'error',
                'message' => 'An internal error occurred. Please try again later.'
            ];
        }

        return response()->json($response);
    }

    /**
     * Stop the crawl
     * This will force stop the crawl for the given key
     *
     * @param string $key
     * @return JsonResponse
     */
    final public function stopCrawl(string $key): JsonResponse
    {
        try {

            $keyResult = (new Crawls())->where('key', $key)->first();
            if (!$keyResult) {
                return response()->json(['status' => 'error', 'message' => 'Key not found']);
            }

            $service = new CrawlService();
            $service->stopCrawl($key);
            $response = ['status' => 'success', 'message' => 'Crawl stopped'];
        } catch (\Throwable $e) {
            $response = [
                'status' => 'error',
                'message' => 'An internal error occurred. Please try again later.'
            ];
        }

        return response()->json($response);
    }
}
