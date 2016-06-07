<?php

namespace AppBundle\Oauth;

use AppBundle\Oauth;

abstract class OauthAbstract {
  public $provider = null;
  public $providerKey = null;
  public $user = null;
  public $userpic = null;
  public $sex = null;
  private static $instances = array();

  /**
   * @param string $method
   * @return OauthAbstract
   */
  public static function getInstance($method) {
    if (!isset(self::$instances[$method])) {
      switch ($method) {
        case 'Vk':
          self::$instances[$method] = new OauthVk();
          break;
        case 'Google':
          self::$instances[$method] = new OauthGoogle();
          break;
      }
      //$class = 'Oauth'.$method;
      //self::$instances[$method] = new $class();
    }
    return self::$instances[$method];
  }
  private function __clone() {}
  private function __construct() {}

  /**
   * @param string $code
   * @param string $domain
   * @return OauthAbstract
   */
  abstract function fetchUserData($code, $domain);
  function fetchByToken($access_token, $uid) {}
  function parseData($data) {}
}