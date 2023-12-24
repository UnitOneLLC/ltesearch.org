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

        // Set the appropriate headers
        header('Content-Type: text/plain');
        header('Content-Disposition: inline; filename=' . $fileName);

        // Output the file contents
        echo $fileContents;
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
