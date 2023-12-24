<?php
// Check if the request method is GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Define the file name
    $fileName = 'error_log';

    // Check if the "clear" parameter is set and its value is 1 or true
    if (isset($_GET['clear']) && ($_GET['clear'] == 1 || strtolower($_GET['clear']) == 'true')) {
        // Clear the contents of the file
        file_put_contents($fileName, '');
        echo 'File cleared successfully.';
        exit;
    }

    // Check if the file exists
    if (file_exists($fileName)) {
        // Read the contents of the file
        $fileContents = file_get_contents($fileName);

        // Set the appropriate headers for HTML
        header('Content-Type: text/html');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        // Output the HTML content with file contents inside <pre> tag
        echo '<!DOCTYPE html>
              <html lang="en">
              <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <title>Error Log Contents</title>
              </head>
              <body>
                  <pre>' . htmlspecialchars($fileContents) . '</pre>
              </body>
              </html>';
    } else {
        // If the file does not exist, return a 404 response
        header('HTTP/1.1 404 Not Found');
        echo 'File not found';
    }
} else {
    // If the request method is not GET, return a 405 response
    header('HTTP/1.1 405 Method Not Allowed');
    echo 'Method Not Allowed';
}
?>
