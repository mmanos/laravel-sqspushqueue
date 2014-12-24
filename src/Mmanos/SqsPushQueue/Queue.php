<?php namespace Mmanos\SqsPushQueue;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Queue\Queue as AbstractQueue;
use Illuminate\Queue\QueueInterface;
use Aws\Sqs\SqsClient;
use Exception;

class Queue extends AbstractQueue implements QueueInterface {

	/**
	 * The Amazon SQS instance.
	 *
	 * @var \Aws\Sqs\SqsClient
	 */
	protected $sqs;

	/**
	 * The current request instance.
	 *
	 * @var \Illuminate\Http\Request
	 */
	protected $request;

	/**
	 * The name of the default tube.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * Create a new Amazon SQS queue instance.
	 *
	 * @param  \Aws\Sqs\SqsClient  $sqs
	 * @param  \Illuminate\Http\Request  $request
	 * @param  string  $default
	 * @return void
	 */
	public function __construct(SqsClient $sqs, Request $request, $default)
	{
		$this->sqs = $sqs;
		$this->request = $request;
		$this->default = $default;
	}

	/**
	 * Push a new job onto the queue.
	 *
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return mixed
	 */
	public function push($job, $data = '', $queue = null)
	{
		return $this->pushRaw($this->createPayload($job, $data), $queue);
	}

	/**
	 * Push a raw payload onto the queue.
	 *
	 * @param  string  $payload
	 * @param  string  $queue
	 * @param  array   $options
	 * @return mixed
	 */
	public function pushRaw($payload, $queue = null, array $options = array())
	{
		$response = $this->sqs->sendMessage(array('QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload));

		return $response->get('MessageId');
	}

	/**
	 * Push a new job onto the queue after a delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @param  string  $job
	 * @param  mixed   $data
	 * @param  string  $queue
	 * @return mixed
	 */
	public function later($delay, $job, $data = '', $queue = null)
	{
		$payload = $this->createPayload($job, $data);

		$delay = $this->getSeconds($delay);

		return $this->sqs->sendMessage(array(

			'QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload, 'DelaySeconds' => $delay,

		))->get('MessageId');
	}

	/**
	 * Pop the next job off of the queue.
	 *
	 * @param  string  $queue
	 * @return \Illuminate\Queue\Jobs\Job|null
	 */
	public function pop($queue = null)
	{
		$queue = $this->getQueue($queue);

		$response = $this->sqs->receiveMessage(
			array('QueueUrl' => $queue, 'AttributeNames' => array('ApproximateReceiveCount'))
		);

		if (count($response['Messages']) > 0)
		{
			return new Job($this->container, $this->sqs, $response['Messages'][0]);
		}
	}

	/**
	 * Marshal a push queue request and fire the job.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function marshal()
	{
		try {
			$this->createPushedSqsJob($this->marshalPushedJob())->fire();
		} catch (Exception $e) {
			if ('Release' == $e->getMessage()) {
				// Return a non-200 message and EB will release back onto the queue.
				return new Response('Release job', 412);
			}
			
			throw $e;
		}

		return new Response('OK');
	}

	/**
	 * Marshal out the pushed job and payload.
	 *
	 * @return object
	 */
	protected function marshalPushedJob()
	{
		$r = $this->request;

		return array(
			'MessageId'  => $r->header('X-aws-sqsd-msgid'),
			'Body'       => $r->getContent(),
			'Attributes' => array('ApproximateReceiveCount' => $r->header('X-aws-sqsd-receive-count')),
		);
	}

	/**
	 * Create a new SqsPushJob for a pushed job.
	 *
	 * @param  object  $job
	 * @return \Illuminate\Queue\Jobs\Job
	 */
	protected function createPushedSqsJob($job)
	{
		return new Job($this->container, $this->sqs, $job);
	}

	/**
	 * Get the queue or return the default.
	 *
	 * @param  string|null  $queue
	 * @return string
	 */
	public function getQueue($queue)
	{
		return $queue ?: $this->default;
	}

	/**
	 * Get the underlying SQS instance.
	 *
	 * @return \Aws\Sqs\SqsClient
	 */
	public function getSqs()
	{
		return $this->sqs;
	}

	/**
	 * Get the request instance.
	 *
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Set the request instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;
	}

}
