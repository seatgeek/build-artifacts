<?php

function defineWithEnv($key, $default = null) {
  $value = getenv($key);
  if ($value === false) {
    if (is_callable($default)) {
      $value = $default($key);
    } else {
      $value = $default;
    }
  }

  if ($value === null) {
    throw new RuntimeException(sprintf('Missing environment variable %s', $key));
  }

  define($key, $value);
}

function defaultFrom($key, $default = null) {
  if (getenv($key) === false) {
    return $default;
  }

  return getenv($key);
}

defineWithEnv('ELASTICSEARCH_URL', function($key) {
  return defaultFrom('SEARCHBOX_URL', 'http://localhost:9200') . '/';
});

defineWithEnv('REDIS_URL', function($key) {
  return defaultFrom('REDISTOGO_URL', 'tcp://127.0.0.1:6379');
});

defineWithEnv('AIRBRAKE_API_KEY');
defineWithEnv('AIRBRAKE_PROJECT_ID');

defineWithEnv('CACHE_EXPIRATION', 500);
defineWithEnv('DOWNLOAD_URL_TEMPLATE', "%s");
defineWithEnv('GITHUB_TOKEN');
defineWithEnv('GITHUB_USER');
defineWithEnv('GITHUB_REPO');
defineWithEnv('PAGE_TITLE', 'Travis Artifacts');
defineWithEnv('SHOW_BRANCHES', 1);
defineWithEnv('STABLE_BRANCH', 'master');
defineWithEnv('TIMEZONE', 'UTC');
defineWithEnv('TRAVIS_IDENTIFIER', 'number');
defineWithEnv('TRAVIS_TOKEN');

date_default_timezone_set(TIMEZONE);

// Create an array of configuration data to pass to the handler class
$config = [
    'handlers' => [
        // *Can* be the class name, not-namespaced
        // The namespace will be "interpolated" in such cases
        'AirbrakeHandler' => [
            'projectKey' => AIRBRAKE_API_KEY,
            'projectId' => AIRBRAKE_PROJECT_ID,
        ],
    ],
];

// Register the error handler
(new \Josegonzalez\ErrorHandlers\Handler($config))->register();

// Prepare app
$app = new \Slim\Slim(array(
  'templates.path' => '../templates',
));

// Create monolog logger and store logger in container as singleton
// (Singleton resources retrieve the same log resource definition each time)
$app->container->singleton('log', function ($c) {
  $log = new \Monolog\Logger('slim-skeleton');
  $log->pushHandler(new \Monolog\Handler\StreamHandler('../logs/app.log', \Psr\Log\LogLevel::DEBUG));
  $env = $c['environment'];
  $env['slim.log'] = $log;

  return $log;
});

// Prepare view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
  'charset' => 'utf-8',
  'cache' => realpath('../templates/cache'),
  'auto_reload' => true,
  'strict_variables' => false,
  'autoescape' => true
);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());
