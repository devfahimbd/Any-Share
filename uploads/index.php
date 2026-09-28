<?php
/**
 * Any Share - Uploads Directory Protection Fallback
 */
http_response_code(403);
header('HTTP/1.1 403 Forbidden');
header('Location: ../');
exit('403 Forbidden - Direct directory browsing is strictly prohibited.');
