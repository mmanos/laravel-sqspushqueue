<?php namespace Mmanos\SqsPushQueue;

use Illuminate\Queue\Jobs\Job as AbstractJob;
use Aws\Sqs\SqsClient;
use Illuminate\Container\Container;
use Exception;

class Job extends AbstractJob {

	/**
	 * The Amazon SQS client instance.
	 *
	 * @var \Aws\Sqs\SqsClient
	 */
	protected $sqs;

	/**
	 * The Amazon SQS job instance.
	 *
	 * @var array
	 */
	protected $job;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @param  \Aws\Sqs\SqsClient  $sqs
	 * @param  array   $job
	 * @return void
	 */
	public function __construct(Container $container,
                                SqsClient $sqs,
                                array $job)
	{
		$this->sqs = $sqs;
		$this->job = $job;
		$this->container = $container;
	}

	/**
	 * Fire the job.
	 *
	 * @return void
	 */
	public function fire()
	{
		$body = json_decode($this->getRawBody(), true);
		$this->resolveAndFire($body);
	}

	/**
	 * Get the raw body string for the job.
	 *
	 * @return string
	 */
	public function getRawBody()
	{
		return $this->job['Body'];
	}

	/**
	 * Release the job back into the queue.
	 *
	 * @param  int   $delay
	 * @return void
	 */
	public function release($delay = 0)
	{
		throw new Exception('Release');
	}

	/**
	 * Get the number of times the job has been attempted.
	 *
	 * @return int
	 */
	public function attempts()
	{
		return (int) $this->job['Attributes']['ApproximateReceiveCount'];
	}

	/**
	 * Get the job identifier.
	 *
	 * @return string
	 */
	public function getJobId()
	{
		return $this->job['MessageId'];
	}

	/**
	 * Get the IoC container instance.
	 *
	 * @return \Illuminate\Container\Container
	 */
	public function getContainer()
	{
		return $this->container;
	}

	/**
	 * Get the underlying SQS client instance.
	 *
	 * @return \Aws\Sqs\SqsClient
	 */
	public function getSqs()
	{
		return $this->sqs;
	}

	/**
	 * Get the underlying raw SQS job.
	 *
	 * @return array
	 */
	public function getSqsJob()
	{
		return $this->job;
	}

}
