<?php

class AccountTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Raml\Parser
     */
    private $parser;

    public function setUp()
    {
        parent::setUp();
        $parser = new \Raml\Parser();
        $this->api = $parser->parse(__DIR__.'/fixture/api.raml');

        $routes = $this->api->getResourcesAsUri()->getRoutes();

        $response = $routes['GET /account']['response']->getResponse(200);

        $this->schema = $response->getSchemaByType('application/json');

    }

    // ---


    /** @test */
    public function shouldBeExpectedFormat()
    {    
        $accessToken = 'some-secret-token';

        $client = new \Guzzle\Http\Client();
        
        $request = $client->get($this->api->getBaseUri() . '/account', [
            'query' => [
                'accessToken' => $accessToken,
            ]
        ]);
        
        $response = $client->send($request);
        
        // Check that we got a 200 status code
        $this->assertEquals( 200, $response->getStatusCode() );

        // Check that the response is JSON        
        $this->assertEquals( 'application/json', $response->getHeader('Content-Type')->__toString());

        // Check the JSON against the schema
        $this->assertTrue($this->schema->validate($response->getBody()));

    }

}
