<?php

namespace AppBundle\Oauth;

class OauthGoogle extends OauthAbstract {
  const appId = '860345762051-fgkpvutgp2omhv0ebv2uo4e0t60u3a20.apps.googleusercontent.com';
  const secret = 'kRUmteaZP9yxqhmTz1K98uqE';
  public function fetchUserData($code, $domain) {
    $redirectUrlBase = 'http://'.$domain.'/oauth/google';
    $userDataUrlBase = 'https://www.googleapis.com/plus/v1/people/me?access_token=';
    $authTokenUrlBase = 'https://accounts.google.com/o/oauth2/token';
    $params = array(
      'code' => $code,
      'client_id' => self::appId,
      'client_secret' => self::secret,
      'redirect_uri' => $redirectUrlBase,
      'grant_type' => 'authorization_code'
    );
    $authTokenUrlBase = curl_init($authTokenUrlBase);
    curl_setopt($authTokenUrlBase, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($authTokenUrlBase, CURLOPT_POST, true);
    curl_setopt($authTokenUrlBase, CURLOPT_POSTFIELDS, http_build_query($params));
    $authTokenResponse = curl_exec($authTokenUrlBase);
    $authTokenParams = json_decode($authTokenResponse, true);
    curl_close($authTokenUrlBase);
    if (empty($authTokenParams['access_token'])) {
      throw new \Exception('Empty access_token');
    }
    $userDataUrl = $userDataUrlBase.$authTokenParams['access_token'];
    $userDataCurl = curl_init($userDataUrl);
    curl_setopt($userDataCurl, CURLOPT_RETURNTRANSFER, true);
    $userDataResponse = curl_exec($userDataCurl);
    curl_close($userDataCurl);
    $raw = json_decode($userDataResponse, true);
    $this->provider = 'google';
    $this->providerKey = $raw['id'];
    if (isset($raw['tagline'])) {
      $this->user = $raw['tagline'];
    } elseif (isset($raw['displayName'])) {
      $this->user = $raw['displayName'];
    }
    if (isset($raw['image']['url'])) {
      $tmp = parse_url($raw['image']['url']);
      $this->userpic = $tmp['scheme'].'://'.$tmp['host'].$tmp['path'];
    }
    $this->sex = ($raw['gender'] == 'male');
  }
}