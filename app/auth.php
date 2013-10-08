<?php

if(!function_exists('apache_request_headers')) {
  function apache_request_headers() {
    static $arh = array();

    if (!$arh) {
      $rx_http = '/\AHTTP_/';
      foreach ($_SERVER as $key => $val) {
        if (preg_match($rx_http, $key)) {
          $arh_key = preg_replace($rx_http, '', $key);
          $rx_matches = array();

          $rx_matches = explode('_', $arh_key);
          if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
            foreach ($rx_matches as $ak_key => $ak_val) {
              $rx_matches[$ak_key] = ucfirst(strtolower($ak_val));
            }

            $arh_key = implode('-', $rx_matches);
          }

          $arh[$arh_key] = $val;
        }
      }
    }
    return $arh;
  }
}

function secure_compare($a, $b) {
  if (strlen($a) !== strlen($b)) {
    return false;
  }

  $result = 0;

  for ($i = 0; $i < strlen($a); $i++) {
    $result |= ord($a[$i]) ^ ord($b[$i]);
  }

  return $result == 0;
}

function isAuthorized($repoSlug, $headers = null) {
  if ($headers === null) {
    $headers = apache_request_headers();
  }

  if (!isset($headers['Authorization'])) {
    throw new Exception("Missing Authorization header");
  }

  $authorization = hash('sha256', $repoSlug . TRAVIS_TOKEN);

  if (!secure_compare($headers['Authorization'], $authorization)) {
    throw new Exception("Invalid Authorization header");
  }

  return true;
}
