<?php
define('APP_DIR', dirname(dirname(__FILE__)));

require APP_DIR . '/vendor/autoload.php';
require APP_DIR . '/app/config.php';
require APP_DIR . '/app/auth.php';
require APP_DIR . '/app/lib/GithubRepository.php';
require APP_DIR . '/app/lib/Model.php';
require APP_DIR . '/app/lib/Payload.php';
require APP_DIR . '/app/lib/Matrix.php';

$app->get('/', function () use ($app) {
  $app->render('index.twig.html', array(
    'payloads' => Payload::find(),
    'branches' => GithubRepository::branches(),
    'stable_branch' => STABLE_BRANCH,
    'github_user' => GITHUB_USER,
    'github_repo' => GITHUB_REPO,
    'page_title' => PAGE_TITLE,
    'travis_identifier' => TRAVIS_IDENTIFIER,
  ));
});

$app->get('/branches/', function () use ($app) {
  GithubRepository::branches(true);
  $app->flash('success', 'Branches refreshed');
  return $app->redirect('/');
});

$app->post('/travisci/', function () use ($app) {
  $data = json_decode($app->request->post('payload'), true);
  $payload = Payload::create($data);
  $matrices = Matrix::createMany($data['matrix']);

  try {
    isAuthorized($payload->getRepoSlug());
  } catch (Exception $e) {
    $app->response->status(401);
    $app->contentType('application/json');
    $app->response->body(json_encode(array(
      'status' => 401, 'message' => $e->getMessage()
    ), JSON_PRETTY_PRINT) . "\n");
    return $app->stop();
  }

  $saved = array();
  $saved[] = array($payload->save(), $payload->errors());
  if ($payload->data('_id')) {
    foreach ($matrices as $matrix) {
      $matrix->attach($payload);
      $saved[] = array($matrix->save(), $matrix->errors());
    }
  }

  $response = array('payload' => $payload->toArray(), 'matrices' => array(), 'saved' => $saved);
  foreach ($matrices as $matrix) {
    $response['matrices'][] = $matrix->toArray();
  }

  $app->response->status(200);
  $app->contentType('application/json');
  $app->response->body(json_encode(array('status' => 200, 'data' => $response), JSON_PRETTY_PRINT) . "\n");
  return $app->stop();
});

// Run app
$app->run();
