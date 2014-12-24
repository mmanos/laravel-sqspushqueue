<?php namespace Mmanos\SqsPushQueue;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;

class SqsPushQueueServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('mmanos/laravel-sqspushqueue');
		
		$app = $this->app;
		
		$app['queue']->extend('sqspush', function () use ($app) {
			return new Connector($app['request']);
		});
		
		$app->rebinding('request', function ($app, $request) {
			if ($app['queue']->connected('sqspush')) {
				$app['queue']->connection('sqspush')->setRequest($request);
			}
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
