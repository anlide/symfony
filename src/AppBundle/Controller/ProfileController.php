<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;

class ProfileController extends Controller
{
  /**
   * @Route("/profile", name="profile")
   * @Method({"GET"})
   */
  public function indexAction(Request $request)
  {
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($userId === null) throw new \Exception('Не авторизован');
    return $this->render('profile.html.twig', array('user' => $user));
  }
  /**
   * RESTful update
   * @Route("/profile", name="profile_update")
   * @Method({"PUT"})
   */
  public function updateAction(Request $request) {
    $return = array();
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    if ($userId === null) return $this->json(false);
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($user === null) return $this->json(false);
    $json = json_decode($request->getContent(), true);
    $email = $json['email'];
    if ($user->email != $email) {
      if ($email != '') {
        // Возможно такой email уже есть в системе, тогда надо пользователя спросить - слить данные с того аккаунта в этот или нет?
        // Но перед слиянием собственно подтвердить email
        // Чтобы не нарушать целостность БД - нельзя сохранять в текущего пользователя email (ну точнее можно, но это бонусных проблем вагон будет)
        // Сохраним в сессии
        $session->set('email', $email);
        /**
         * @var User $userOther
         */
        $userOther = $this->getDoctrine()
          ->getRepository('AppBundle:User')
          ->findOneBy(array('email' => $email));
        $return['email_exists'] = $userOther !== null;
        // Опять таки - сохраняем код подтверждения в сессии, а не в БД
        $confirmCode = $user->generateRandomString();
        $session->set('confirmCode', $confirmCode);
        $return['debug'] = $confirmCode;
        $message = \Swift_Message::newInstance()
          ->setSubject('Restore symfony.llk-guild.ru') // TODO: поставить сюда текст покрасивее
          ->setFrom('support@symfony.llk-guild.ru')
          ->setTo($email)
          ->setBody(
            $this->renderView(
              'Emails/restore.html.twig',
              array('code' => $confirmCode)
            ),
            'text/html'
          )
        ;
        $this->get('mailer')->send($message);
      } else {
        $user->email = $email;
        $user->confirmCode = null;
      }
    }
    $user->name = $json['name'];
    $this->getDoctrine()->getManager()->flush();
    $return['name'] = true;
    return $this->json($return);
  }
  /**
   * RESTful update confirm
   * @Route("/profile-confirm", name="profile_update_confirm")
   * @Method({"PUT"})
   */
  public function updateConfirmAction(Request $request) {
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    if ($userId === null) return $this->json(false);
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($user === null) return $this->json(false);
    $json = json_decode($request->getContent(), true);
    if ($session->get('confirmCode') == $json['code']) {
      // Когда всё подтверждено - надо собственно выполнить соединение
      // Попробуем подставить поле name
      // Попробуем подставить данные соц. сетей
      // Пробуем подставить аватара
      // Берём самую вкусную роль
      // Сообщения отмеченные, что автор - соединяемый - его перепишем на новый аккаунт
      // NOTE: опасное место, для всех новых фич, где используется пользователь - надо допиливать сюда код (и не забывать это)
      $email = $session->get('email');
      /**
       * @var User $userOther
       */
      $userOther = $this->getDoctrine()
        ->getRepository('AppBundle:User')
        ->findOneBy(array('email' => $email));
      if ($userOther === null) return $this->json(false); // Нежданчик
      $user->mergeAccout($userOther, $this->getDoctrine());
      $user->email = $email;
      $this->getDoctrine()->getManager()->flush();
      return $this->json(array('check' => true));
    } else {
      return $this->json(array('check' => false));
    }
  }
  /**
   * RESTful profile request moderator
   * @Route("/profile-request-moderator", name="profile_request_moderator")
   * @Method({"PUT"})
   */
  public function profileRequestModeratorAction(Request $request) {
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    if ($userId === null) return $this->json(false);
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($user === null) return $this->json(false);
    $return = array();
    if ($user->role == 'moderator') {
      $user->role = 'user';
    } else {
      $user->role = 'moderator';
    }
    $this->getDoctrine()->getManager()->flush();
    $return['got'] = ($user->role == 'moderator');
    return $this->json($return);
  }
  /**
   * RESTful profile set password
   * @Route("/profile-set-password", name="profile_set_password")
   * @Method({"PUT"})
   */
  public function profileSetPasswordAction(Request $request) {
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    if ($userId === null) return $this->json(false);
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($user === null) return $this->json(false);
    $return = array();
    // Если нужен пароль - проверяем пароль
    $json = json_decode($request->getContent(), true);
    $return['done'] = true;
    if ($user->password !== null) {
      if (!$user->checkPassword($json['password'])) {
        $return['done'] = false;
      }
    }
    if ($return['done']) {
      $user->password = md5($json['password_new']);
    }
    $this->getDoctrine()->getManager()->flush();
    return $this->json($return);
  }
  /**
   * RESTful profile social get url for redirect on cliend side
   * @Route("/profile-social-get-url={social}", name="profile_social_get_url")
   * @Method({"GET"})
   */
  public function profileSocialGetUrlAction(Request $request, $social) {
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    if ($userId === null) return $this->json(false);
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($user === null) return $this->json(false);
    $return = array();
    // Если нужен пароль - проверяем пароль
    switch ($social) {
      case 'vk':
        $return = 'https://oauth.vk.com/authorize?client_id=5493762&display=page&redirect_uri='.$request->getSchemeAndHttpHost().'/oauth/vk&scope=&response_type=code&v=5.52';
        break;
      case 'google':
        $return = 'https://accounts.google.com/o/oauth2/auth?client_id=860345762051-fgkpvutgp2omhv0ebv2uo4e0t60u3a20.apps.googleusercontent.com&response_type=code&scope=openid&redirect_uri='.$request->getSchemeAndHttpHost().'/oauth/google';
        break;
      default:
        $return = false;
        break;
    }
    return $this->json($return);
  }
}
