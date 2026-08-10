<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strip trailing slash on /api/* before routing so clients that call
 * /api/v1/notifikasi/ do not get an HTML 301 that breaks JSON parsers.
 */
class NormalizeApiTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        $uri = (string) $request->server->get('REQUEST_URI', '');
        $path = parse_url($uri, PHP_URL_PATH) ?: '';

        if (! str_starts_with($path, '/api')) {
            return $next($request);
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $query = parse_url($uri, PHP_URL_QUERY);
            $normalized = rtrim($path, '/');
            $request->server->set(
                'REQUEST_URI',
                $normalized.($query ? '?'.$query : '')
            );

            if ($request->server->has('PATH_INFO')) {
                $request->server->set('PATH_INFO', rtrim((string) $request->server->get('PATH_INFO'), '/') ?: '/');
            }
        }

        return $next($request);
    }
}
