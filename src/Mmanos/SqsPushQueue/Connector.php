<?php namespace Mmanos\SqsPushQueue;

use Illuminate\Http\Request;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Aws\Sqs\SqsClient;
use Illuminate\Queue\SqsQueue;

class Connector implements ConnectorInterface {

	/**
	 * The current request instance.
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * Create a new Iron connector instance.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Queue\QueueInterface
	 */
	public function connect(array $config)
	{
		$sqs = SqsClient::factory($config);

		return new Queue($sqs, $this->request, $config['queue']);
	}

}
