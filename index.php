<?php
require_once 'vendor/autoload.php';

use Sitepoint\RamlApiMock;

// The RAML library is currently showing a deprecated error, so ignore it
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

// Create the router
$router = new RamlApiMock('./test/fixture/api.raml');

// Handle the route
$response = $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

// Set the HTTP response code
http_response_code($response->status);

// Optionally set some response headers
if (count($response->headers)) {
	foreach ($response->headers as $name => $value) {
		header(sprintf('%s: %s', $name, $value));
	}
}

// Print out the body of the response
print $response->body;