<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Oauth\OauthAbstract;
use AppBundle\Oauth\OauthVk;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OauthController
 * @package AppBundle\Controller
 */
class OauthController extends Controller
{
  /**
   * @Route("/oauth/{method}", name="oauth")
   */
  public function oauthAction(Request $request, $method)
  {
    // NOTE: что-то не так с моим nginx сервером (и QUERY_STRING приходит пустой, хотя конфиг явно правильный)
    // Нет времени изучать каксделать правильно через "$code = $request->query->get('code');"
    // Поэтому сделано немного порагульному получение $code
    if ($method == 'register') return $this->registerAction($request);
    if ($method == 'register-finish') return $this->registerFinishAction($request);
    if (!in_array($method, array('vk', 'google'))) {
      return $this->json('invalid method: '.$method);
    }
    preg_match('~^/oauth/([^\?]+)\?code\=(.*)$~', $request->getRequestUri(), $m);
    $code = $m[2];
    try {
      $oauth = OauthAbstract::getInstance(strtoupper(substr($method, 0, 1)).substr($method, 1));
      $oauth->fetchUserData($code, $request->getHost());
    } catch (\Exception $e) {
      // По какой-то причине данные не может получить
      if (!isset($oauth)) $oauth = null;
      return $this->json(array($e->getMessage(), $oauth, $request->getRequestUri(), $code, $method));
    }
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array($method => $oauth->providerKey));
    $session = $request->getSession();
    $session->start();
    $userIdSession = $session->get('user');
    // И тут начинается ветвление 4 case залогинен/нет, есть пользователь/нет
    if ($user === null) {
      if ($userIdSession === null) {
        // Пользователя нет и сессии нет - значит надо предложить закончить регистрацию
        $session->set('oauth', $oauth);
        return $this->redirect($request->getSchemeAndHttpHost().'/oauth/register');
      } else {
        // Пользователя нет но сессия есть - значит надо дополнить аккаунт и вернутся в профиль
        /**
         * @var User $userSession
         */
        $userSession = $this->getDoctrine()
          ->getRepository('AppBundle:User')
          ->findOneBy(array('id' => $userIdSession));
        if ($userSession === null) return $this->json('Пользователя удалили в процессе регистрации'); // ололо ситуация
        $userSession->$method = $oauth->providerKey;
        $this->getDoctrine()->getManager()->flush();
        return $this->redirect($request->getSchemeAndHttpHost().'/profile');
      }
    } else {
      if ($userIdSession === null) {
        // Пользователь есть и сессии нет - просто авторизуем пользователя и делаем редирект на главную страницу
        $session->set('user', $user->getId());
        return $this->redirect($request->getSchemeAndHttpHost());
      } else {
        // Пользователь есть и сессия есть - значит надо склеивать аккаунты и вернутся в профиль
        /**
         * @var User $userSession
         */
        $userSession = $this->getDoctrine()
          ->getRepository('AppBundle:User')
          ->findOneBy(array('id' => $userIdSession));
        if ($userSession === null) return $this->json('Пользователя удалили в процессе регистрации'); // ололо ситуация
        $userSession->mergeAccout($user, $this->getDoctrine());
        $this->getDoctrine()->getManager()->flush();
        return $this->redirect($request->getSchemeAndHttpHost().'/profile');
      }
    }
  }
  /**
   * @Route("/oauth/register", name="oauth_register")
   */
  public function registerAction(Request $request)
  {
    $session = $request->getSession();
    $session->start();
    /**
     * @var OauthAbstract $oauth
     */
    $oauth = $session->get('oauth');
    if ($oauth === null) return $this->redirect($request->getSchemeAndHttpHost());
    if (empty($oauth->user)) return $this->redirect($request->getSchemeAndHttpHost());
    return $this->render('oauth.register.html.twig', array('oauth' => $oauth));
  }
  /**
   * @Route("/oauth/register-finish", name="oauth_register_finish")
   */
  public function registerFinishAction(Request $request)
  {
    $session = $request->getSession();
    $session->start();
    /**
     * @var OauthAbstract $oauth
     */
    $oauth = $session->get('oauth');
    $user = new User();
    $user->email = null;
    $user->password = null;
    $user->confirmCode = null;
    $user->name = $oauth->user;
    switch ($oauth->provider) {
      case 'vk':
        $user->vk = $oauth->providerKey;
        break;
      case 'google':
        $user->google = $oauth->providerKey;
        break;
    }
    $em = $this->getDoctrine()->getManager();
    $em->persist($user);
    $em->flush();
    $session->remove('oauth');
    $session->set('user', $user->getId());
    return $this->json(true);
  }
}
