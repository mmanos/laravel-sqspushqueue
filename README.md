# AWS SQS push queue driver for Laravel 4

This package provides a laravel queue driver with support for AWS SQS push queues. This technique is used by the AWS Elastic Beanstalk worker tier environments.

## Installation Via Composer

Add this to you composer.json file, in the require object:

```javascript
"mmanos/laravel-sqspushqueue": "dev-master"
```

After that, run composer install to install the package.

Add the service provider to `app/config/app.php`, within the `providers` array.

```php
'providers' => array(
	// ...
	'Mmanos\SqsPushQueue\SqsPushQueueServiceProvider',
)
```

## Configuration

Update the existing `queue.php` config file and change the sqs driver to be `sqspush`. This driver will use the existing sqs config properites (`key`, `secret`, etc...). The main queue driver should still be sqs.
