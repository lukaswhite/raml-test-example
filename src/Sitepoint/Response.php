<?php namespace Sitepoint;

class Response {

	/**
	 * The HTTP status code
	 * @var integer
	 */
	public $status;

	/**
	 * The body of the response
	 * @var string
	 */
	public $body;

	/**
	 * An array of response headers
	 * @var array
	 */
	public $headers;

	/**
	 * Constructor
	 * 
	 * @param integer $status The HTTP status code
	 */
	public function __construct($status = 200)
	{
		$this->status = $status;
		
		$this->headers = [
			'Content-Type' => 'application/json'
		];
	}

	/**
	 * Sets the response body
	 * 
	 * @param string $body
	 */
	public function setBody($body)
	{
		$this->body = $body;
		$this->headers['Content-Length'] = strlen($body);
	}

}