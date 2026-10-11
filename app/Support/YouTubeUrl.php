<?php

namespace App\Support;

final class YouTubeUrl
{
    public static function videoId(?string $url): ?string
    {
        if ($url === null || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $parts = parse_url($url);
        if ($parts === false || ! in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $id = null;
        if ($host === 'youtu.be' && preg_match('~^/([a-zA-Z0-9_-]{11})/?$~', $path, $match)) {
            $id = $match[1];
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^/(?:embed|shorts)/([a-zA-Z0-9_-]{11})/?$~', $path, $match)) {
                $id = $match[1];
            }
        }

        return is_string($id) && preg_match('/^[a-zA-Z0-9_-]{11}$/D', $id) === 1 ? $id : null;
    }
}
