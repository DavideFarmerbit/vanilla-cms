<?php

namespace VanillaCms\Core\Router;

final class Router
{
    private const RETURN_TO_PARAM = 'return_to';

    /**
     * The current request's url, path and query string included.
     * @return string
     */
    public static function currentUrl(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    /**
     * Uses Dispatchers to handle the current url request.
     * @param RouterDispatcher[] $routes
     */
    public static function dispatch(array $routes): void
    {
        $segments = self::segments();

        foreach ($routes as $route) {
            // Match the route pattern against the url segments, and extract eventual parameters.
            $params = self::match($route->pattern(), $segments);
            
            // If no match, continue to the next route.
            if ($params === null) {
                continue;
            }

            // If a match is found, call the route handler with the extracted parameters and terminate the function.
            $route->handle($params);
            return;
        }

        // Fallback if no route matches.
        self::notFound();
    }

    /**
     * Renders the 404 Not Found page.
     */
    public static function notFound(): void
    {
        http_response_code(404);
        echo '404 Not Found';
    }

    /**
     * Redirects to $url, terminating the current script.
     * @param string $url
     */
    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Redirects to $url, remembering the current request so a later call to return()/returnUrl() can send the
     * visitor back to it (e.g. bouncing to a login page and returning to the originally requested page afterwards).
     * @param string $url url to redirect to.
     * @param string|null $returnTo url to return to; defaults to the current request url.
     */
    public static function redirectWithReturn(string $url, ?string $returnTo = null): never
    {
        $returnTo ??= self::currentUrl();
        $separator = str_contains($url, '?') ? '&' : '?';

        self::redirect($url . $separator . http_build_query([self::RETURN_TO_PARAM => $returnTo]));
    }

    /**
     * Redirects back to wherever redirectWithReturn() was originally called from, or to $fallback.
     * @param string $fallback url to redirect to if there is no valid return target.
     */
    public static function return(string $fallback = '/'): never
    {
        self::redirect(self::returnUrl($fallback));
    }

    /**
     * Resolves the target set by redirectWithReturn(), falling back to $fallback if none is present or it fails
     * validation. This only guarantees the target is a safe, same-origin url to redirect to: it says nothing about
     * whether the visitor is authorized to see it, callers must still enforce their own checks on that page.
     * @param string $fallback url to use if there is no valid return target.
     * @return string
     */
    public static function returnUrl(string $fallback = '/'): string
    {
        $target = $_GET[self::RETURN_TO_PARAM] ?? null;

        return is_string($target) && self::isSafeReturnTarget($target) ? $target : $fallback;
    }

    /**
     * Parses the url into an array of segments.
     * @return string[]
     */
    private static function segments(): array
    {
        $path = parse_url(self::currentUrl(), PHP_URL_PATH) ?? '/';
        $path = trim($path, '/');

        return $path === '' ? [] : explode('/', $path);
    }

    /** 
     * Matches a pattern against a set of segments.
     * @param string $pattern: A pattern like '/users/{id}/posts/{postId}/*' where '*' matches any number of segments, 
     *                         and {} defines a parameter (used by RouterDispatcher::handle()).
     * @param string[] $segments: The segments to match against.
     * @return array<string|string[]>|null: The url parameters or null if no match.
     */
    private static function match(string $pattern, array $segments): ?array
    {
        $patternSegments = $pattern === '' ? [] : explode('/', $pattern);
        $params = [];

        foreach ($patternSegments as $i => $token) {
            // If the token is '*', match any number of segments, and add all of them to the params array.
            if ($token === '*') {
                $params[] = array_slice($segments, $i);
                return $params;
            }

            // If the pattern expects more segments than the url has, return null.
            if (!array_key_exists($i, $segments)) {
                return null;
            }

            // If the token is a parameter, add the corresponding segment to the params array.
            if ($token !== '' && $token[0] === '{') {
                $params[] = $segments[$i];
                continue;
            }

            // If the token is a litteral, compare it to the corresponding segment.
            if ($segments[$i] !== $token) {
                return null;
            }
        }

        // If the pattern is shorter then the url, return null.
        // We check it here because the above loop might contain '*' tokens.
        return count($segments) === count($patternSegments) ? $params : null;
    }

    /**
     * Guards against open redirects: only accepts same-origin, relative paths, rejecting anything that could make
     * a browser navigate to a different host (protocol-relative urls, embedded schemes, backslash tricks, etc).
     * @param string $target
     * @return bool
     */
    private static function isSafeReturnTarget(string $target): bool
    {
        if ($target === '' || $target[0] !== '/') {
            return false;
        }

        // Blocks '//evil.com' and '/\evil.com', both of which browsers can resolve as protocol-relative urls.
        if (isset($target[1]) && ($target[1] === '/' || $target[1] === '\\')) {
            return false;
        }

        if (str_contains($target, '\\') || preg_match('/[\x00-\x1F\x7F]/', $target) === 1) {
            return false;
        }

        // Defense in depth: even after the checks above, make sure no scheme/host sneaked in.
        $parts = parse_url($target);

        return $parts !== false && !isset($parts['scheme']) && !isset($parts['host']);
    }
}
