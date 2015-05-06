<?php

class IPS {
	/**
	 * @var fXmlRpc\Client
	 */
	protected $api;
	protected $url;
	protected $module;
	protected $key;


	/**
	 * @param $url
	 * @param $apiModule
	 * @param $apiKey
	 */
	public function __construct($url, $apiModule, $apiKey) {
		$this->url = "$url/interface/board/index.php";
		$this->module = $apiModule;
		$this->key = $apiKey;

		$this->api = new fXmlRpc\Client( $this->url );
	}


	/**
	 * XML-RPC API Magic Method. Calls a method to this class
	 * like an API method.
	 *
	 *
	 * For example:
	 *
	 * <code>
	 *    $ips->register($args);
	 * </code>
	 *
	 * ...will call the 'register' method of the XML-RPC API.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return array
	 */
	public function __call( $name, $arguments = [] ) {
		// Add the API module and key to the call parameters
		$args = array_merge(
			array(
				'api_module' => $this->module,
				'api_key' => $this->key,
			),
			$arguments[0]
		);

		return $this->api->call( $name, [$args] );
	}
}