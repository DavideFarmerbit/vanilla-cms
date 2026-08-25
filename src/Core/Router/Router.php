<?php

namespace VanillaCms\Core\Router;

final class Router
{
    /**
     * Uses Dispaatchers to handle the url request.
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

    public static function notFound(): void
    {
        http_response_code(404);
        echo '404 Not Found';
    }

    /**
     * Parses the url into an array of segments.
     * @return string[]
     */
    private static function segments(): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $path = trim($path, '/');

        return $path === '' ? [] : explode('/', $path);
    }

    /** 
     * Matches a pattern against a set of segments.
     * @param string $pattern: A pattern like '/users/{id}/posts/{postId}/*' where '*' matches any number of segments, 
     *                         and {} defines a parameter (used by RouterDispatcher::handle()).
     * @param string[] $segments: The segments to match against.
     * @return string[]|null: The url parameters or null if no match.
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
}
