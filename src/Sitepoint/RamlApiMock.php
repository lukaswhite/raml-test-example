<?php namespace Sitepoint;

class RamlApiMock {

	/**
	 * Constructor
	 * 
	 * @param string $ramlFilepath Path to the RAML file to use
	 */
	public function __construct($ramlFilepath)
	{
		// Create the RAML parser and parse the RAML file
		$parser = new \Raml\Parser();
		$api = $parser->parse($ramlFilepath);

		// Extract the routes
		$routes = $api->getResourcesAsUri()->getRoutes();
		$this->routes = $routes;

		// Iterate through the available routes and add them to the Router
		$this->dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($routes) {
			foreach ($routes as $route) {
				$r->addRoute($route['method'], $route['path'], $route['path']);
			}
		});

	}

	/**
	 * Dispatch a route
	 * 
	 * @param  string $method  The HTTP verb (GET, POST etc)
	 * @param  string $url     The URL
	 * @param  array  $data    An array of data (Note, not currently used)
	 * @param  array  $headers An array of headers (Note, not currently used)
	 * @return Response
	 */
	public function dispatch($method, $url, $data = array(), $headers = array())
	{
		// Parse the URL
		$parsedUrl = parse_url($url);
		$path = $parsedUrl['path'];

		// Attempt to obtain a matching route
		$routeInfo = $this->dispatcher->dispatch($method, $path);

		// Analyse the route
		switch ($routeInfo[0]) {
			case \FastRoute\Dispatcher::NOT_FOUND:
				// Return a 404
				return new Response(404);				
				break;

			case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				// Method not allows (405)
				$allowedMethods = $routeInfo[1];				
				// Create the response...
				$response = new Response(405);
				// ...and set the Allow header
				$response->headers['Allow'] = implode(', ', $allowedMethods);
				return $response;
				break;

			case \FastRoute\Dispatcher::FOUND:
				$handler = $routeInfo[1];
				$vars = $routeInfo[2];
				$signature = sprintf('%s %s', $method, $handler);
				$route = $this->routes[$signature];

				// Get any query parameters
				if (isset($parsedUrl['query'])) {					
					parse_str($parsedUrl['query'], $queryParams);							
				} else {
					$queryParams = [];
				}

				// Check the query parameters
				$errors = $this->checkQueryParameters($route, $queryParams);
				if (count($errors)) {
					$response = new Response(400);
					$response->setBody(json_encode(['errors' => $errors]));
					return $response;
				}				

				// If we get this far, is a successful response
				return $this->handleRoute($route, $vars);
		
				break;
		}

	}

	/**
	 * Checks any query parameters
	 * @param  array 	$route  The current route definition, taken from RAML
	 * @param  array 	$params The query parameters
	 * @return boolean
	 */
	public function checkQueryParameters($route, $params)
	{
		// Get this route's available query parameters
		$queryParameters = $route['response']->getQueryParameters();

		// Create an array to hold the errors
		$errors = [];

		if (count($queryParameters)) {

			foreach($queryParameters as $name => $param) {				

				// If the display name is set then great, we'll use that - otherwise we'll use
				// the name
				$displayName = (strlen($param->getDisplayName())) ? $param->getDisplayName() : $name;

				// If the parameter is required but not supplied, add an error
				if ($param->isRequired() && !isset($params[$name])) {
					//$errors[$name] = sprintf('%s is required', $displayName);
				}

				// Now check the format
				if (isset($params[$name])) {

					var_dump($param);

					switch ($param->getType()) {
						case 'string':
							if (!is_string($params[$name])) {
								$errors[$name] = sprintf('%s must be a string');
							}
							break;
						case 'number':
							if (!is_numeric($params[$name])) {
								$errors[$name] = sprintf('%s must be a number');
							}
							break;
						case 'integer':
							if (!is_int($params[$name])) {
								$errors[$name] = sprintf('%s must be an integer');
							}
							break;
						case 'boolean':
							if (!is_bool($params[$name])) {
								$errors[$name] = sprintf('%s must be a boolean');
							}
							break;
						// date and file are omitted for brevity
					}

				}

			}
		}

		// Finally, return the errors
		return $errors;
	}

	/**
	 * Return a response for the given route
	 * 
	 * @param  array 	$route  The current route definition, taken from RAML
	 * @param  array 	$vars   An optional array of URI parameters
	 * @return Response
	 */
	public function handleRoute($route, $vars)
	{
		// Create a reponse
		$response = new Response(200);	

		// Return an example response, from the RAML
		$response->setBody($route['response']->getResponse(200)->getExampleByType('application/json'));

		// And return the result
		return $response;

	}
	
}
