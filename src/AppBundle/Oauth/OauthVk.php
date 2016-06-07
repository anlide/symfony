<?php

namespace AppBundle\Oauth;

class OauthVk extends OauthAbstract {
  const appId = '5493762';
  const secret = 'M7iarmhUIoSrcntLusYU';
  public function fetchUserData($code, $domain) {
    $redirectUrlBase = 'http://'.$domain.'/oauth/vk';
    $authTokenUrlBase = 'https://oauth.vk.com/access_token?client_id='.self::appId.'&client_secret='.self::secret.'&redirect_uri='.$redirectUrlBase.'&code=';
    $authTokenUrl = $authTokenUrlBase.$code;
    $resp = file_get_contents($authTokenUrl);
    var_dump($domain, $resp);
    $data = json_decode($resp, true);
    if (!isset($data['access_token'])) {
      throw new \Exception('access_token empty');
    }
    $this->fetchByToken($data['access_token'], $data['user_id']);
  }
  public function fetchByToken($accessToken, $uid) {
    $params = array(
      'uids'         => $uid,
      'fields'       => 'uid,first_name,last_name,sex,photo_big,country,city',
      'access_token' => $accessToken,
      'v' => '5.8'
    );
    $userInfo = json_decode(file_get_contents('https://api.vk.com/method/users.get?'.urldecode(http_build_query($params))), true);
    $this->parseData($userInfo);
  }
  public function parseData($userInfo) {
    $this->provider = 'vk';
    if (isset($userInfo['response'][0]['id'])) {
      $this->providerKey = $userInfo['response'][0]['id'];
    } elseif (isset($userInfo['response'][0]['uid'])) {
      $this->providerKey = $userInfo['response'][0]['uid'];
    } else {
      throw new \Exception('No id or uid from vk!');
    }
    if (isset($userInfo['response'][0]['first_name'])) {
      $this->user = $userInfo['response'][0]['first_name'];
      if (isset($userInfo['response'][0]['last_name'])) {
        $this->user .= ' '.$userInfo['response'][0]['last_name'];
      }
    }
    if (isset($userInfo['response'][0]['photo_big'])) {
      $this->userpic = $userInfo['response'][0]['photo_big'];
      if (substr($this->userpic, 0, 5) != 'https') $this->userpic = null; // TODO: Закачивать картинку себе и использовать её с локального сайта
    }
    $this->sex = ($userInfo['response'][0]['sex'] == 2);
  }
}