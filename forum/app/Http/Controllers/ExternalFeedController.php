<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


class ExternalFeedController extends Controller
{
    public function hackerNewsTop(Request $request)
    {
        $validated = $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'ttl'   => 'sometimes|integer|min:1|max:120',
        ]);

        $limit = (int)($validated['limit'] ?? 20);
        $ttl   = (int)($validated['ttl'] ?? 10);

        $cacheKey = "ext:hn:top:limit={$limit}";
        $items = Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($limit) {
            $ids = Http::timeout(10)->get('https://hacker-news.firebaseio.com/v0/topstories.json')->json();
            if (!is_array($ids) || empty($ids)) {
                abort(502, 'Upstream (Hacker News) unavailable');
            }
            $ids = array_slice($ids, 0, $limit);


            $responses = Http::timeout(10)->pool(function (Pool $pool) use ($ids) {
                return array_map(fn($id) => $pool->as((string)$id)->get("https://hacker-news.firebaseio.com/v0/item/{$id}.json"), $ids);
            });

            $out = [];
            foreach ($responses as $id => $resp) {
                if ($resp->ok()) {
                    $it = $resp->json();
                    if (is_array($it) && isset($it['id'])) {
                        $out[] = [
                            'id'         => $it['id'],
                            'type'       => $it['type'] ?? null,
                            'title'      => $it['title'] ?? null,
                            'url'        => $it['url']   ?? null,
                            'by'         => $it['by']    ?? null,
                            'score'      => $it['score'] ?? null,
                            'comments'   => $it['descendants'] ?? null,
                            'time'       => isset($it['time']) ? Carbon::createFromTimestamp($it['time'])->toISOString() : null,
                        ];
                    }
                }
            }
            return $out;
        });

        return response()->json([
            'source' => 'hacker_news',
            'count'  => count($items),
            'items'  => $items,
            'cached' => true,
        ]);
    }

    public function discourseLatest(Request $request)
    {
        $validated = $request->validate([
            'host' => 'sometimes|string',
            'page' => 'sometimes|integer|min:0',
            'ttl'  => 'sometimes|integer|min:1|max:60',
        ]);

        $rawHost = $validated['host'] ?? 'meta.discourse.org';
        $host = preg_replace('/[^a-z0-9\.\-]/i', '', $rawHost) ?: 'meta.discourse.org';

        $page = (int)($validated['page'] ?? 0);
        $ttl  = (int)($validated['ttl']  ?? 5);

        $cacheKey = "ext:discourse:latest:host={$host}:page={$page}";
        $payload = Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($host, $page) {
            $url = "https://{$host}/latest.json";
            $res = Http::timeout(10)->get($url, ['page' => $page]);

            if (!$res->ok()) {
                abort(502, 'Upstream (Discourse) unavailable');
            }

            $json = $res->json();
            $topics = data_get($json, 'topic_list.topics', []);
            if (!is_array($topics)) {
                $topics = [];
            }

            $items = [];
            foreach ($topics as $t) {
                $id    = $t['id']    ?? null;
                $slug  = $t['slug']  ?? null;
                $title = $t['title'] ?? null;

                $items[] = [
                    'id'           => $id,
                    'title'        => $title,
                    'slug'         => $slug,
                    'url'          => ($id && $slug) ? "https://{$host}/t/{$slug}/{$id}" : null,
                    'posts_count'  => $t['posts_count']  ?? null,
                    'views'        => $t['views']        ?? null,
                    'like_count'   => $t['like_count']   ?? null,
                    'category_id'  => $t['category_id']  ?? null,
                    'created_at'   => $t['created_at']   ?? null,
                    'last_posted_at' => $t['last_posted_at'] ?? null,
                ];
            }

            return [
                'host'   => $host,
                'page'   => $page,
                'count'  => count($items),
                'items'  => $items,
            ];
        });

        return response()->json($payload + ['source' => 'discourse', 'cached' => true]);
    }
}
