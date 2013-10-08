<?php
class GithubRepository {

  static $connection = null;

  public static function connection() {
    if (!static::$connection) {
      $info = parse_url(REDIS_URL);

      $redis = new Redis;
      $redis->connect($info['host'], $info['port']);
      if (!empty($info['pass'])) {
        $redis->auth($info['pass']);
      }

      $connection = $redis;
    }

    return $connection;
  }

  public static function branches($force = false) {
    $branches = array();
    if (SHOW_BRANCHES) {
      $branches = static::connection()->get('branches');
      if (!$branches || $force) {
        $branches = array();
        $client = new Github\Client();
        $client->authenticate(GITHUB_TOKEN, null, Github\Client::AUTH_HTTP_TOKEN);
        $response = $client->api('repo')->branches(GITHUB_USER, GITHUB_REPO);
        foreach ($response as $branch) {
          $branch['real_name'] = $branch['name'];
          $branch['name'] = str_replace('/', '-', $branch['name']);
          $branches[] = $branch;
        }

        static::connection()->set('branches', json_encode($branches));
        static::connection()->expire('branches', (int)CACHE_EXPIRATION);
      } else {
        $branches = json_decode($branches, true);
      }
    }

    return $branches;
  }

}
