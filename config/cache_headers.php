<?php
/**
 * Cache headers utility for improving performance
 * Use this for endpoints that return static or semi-static data
 */

/**
 * Set cache headers for HTTP responses
 * @param int $maxAge Maximum age in seconds (default: 60 seconds)
 * @param bool $public Whether the response can be cached by public caches (default: true)
 */
function setCacheHeaders($maxAge = 60, $public = true) {
    $cacheControl = ($public ? 'public' : 'private') . ', max-age=' . $maxAge;
    header('Cache-Control: ' . $cacheControl);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
}

/**
 * Set no-cache headers for dynamic content
 */
function setNoCacheHeaders() {
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}
