<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthController extends Controller
{
  /**
   * @Route("/login-exists={email}", name="login_exists")
   */
  public function loginExistsAction($email)
  {
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email));
    return $this->json(array(
      'exists' => $user !== null,
      'confirmed' => ($user !== null)?($user->confirmCode === null):false
    ));
  }
  /**
   * @Route("/login-check={email}/{password}", name="login_check")
   */
  public function loginCheckAction(Request $request, $email, $password)
  {
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email));
    if ($user !== null) {
      $session = $request->getSession();
      $session->start();
      $session->set('user', $user->getId());
    }
    return $this->json(array(
      'valid' => ($user !== null)?($user->password == md5($password)):false,
      'confirmed' => ($user !== null)?($user->confirmCode === null):false,
      'password_exists' => ($user !== null)?($user->password !== null):false
    ));
  }
  /**
   * @Route("/code-check={email}/{code}", name="code_check")
   */
  public function codeCheckAction(Request $request, $email, $code)
  {
    $em = $this->getDoctrine()->getManager();
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email));
    if ($user === null) {
      return $this->json(false);
    }
    if ($code != $user->confirmCode) {
      return $this->json(false);
    }
    $user->confirmCode = null;
    $em->persist($user);
    $em->flush();
    $session = $request->getSession();
    $session->start();
    $session->set('user', $user->getId());
    return $this->json(true);
  }
  /**
   * @Route("/logout", name="logout")
   */
  public function logoutAction(Request $request)
  {
    $session = $request->getSession();
    $session->start();
    $session->remove('user');
    return $this->redirect($request->getSchemeAndHttpHost());
  }
  /**
   * @Route("/register", name="register")
   * @Method({"POST"})
   */
  public function registerAction(Request $request)
  {
    $json = json_decode($request->getContent(), true);
    $user = new User();
    $user->register($json['email'], $json['password']);
    $em = $this->getDoctrine()->getManager();
    $em->persist($user);
    // TODO: convert PostEmail -> PostUser
    $em->flush();
    $message = \Swift_Message::newInstance()
      ->setSubject('Register symfony.llk-guild.ru')
      ->setFrom('support@symfony.llk-guild.ru')
      ->setTo($json['email'])
      ->setBody(
        $this->renderView(
          'Emails/register.html.twig',
          array('code' => $user->confirmCode)
        ),
        'text/html'
      )
    ;
    $this->get('mailer')->send($message);
    return $this->json(true);
  }
  /**
   * @Route("/send_restore_code", name="send_restore_code")
   * @Method({"POST"})
   */
  public function sendRestoreCodeAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $json = json_decode($request->getContent(), true);
    $email = $json['email'];
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email));
    if ($user->confirmCode === null) {
      $user->confirmCode = $user->generateRandomString();
      $em->persist($user);
      $em->flush();
    }
    $message = \Swift_Message::newInstance()
      ->setSubject('Restore symfony.llk-guild.ru')
      ->setFrom('support@symfony.llk-guild.ru')
      ->setTo($email)
      ->setBody(
        $this->renderView(
          'Emails/restore.html.twig',
          array('code' => $user->confirmCode)
        ),
        'text/html'
      )
    ;
    $this->get('mailer')->send($message);
    return $this->json(true);
  }
  /**
   * @Route("/send_register_code", name="send_register_code")
   * @Method({"POST"})
   */
  public function sendRegisterCodeAction(Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $json = json_decode($request->getContent(), true);
    $email = $json['email'];
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email));
    if ($user->confirmCode === null) {
      $user->confirmCode = $user->generateRandomString();
      $em->persist($user);
      $em->flush();
    }
    $message = \Swift_Message::newInstance()
      ->setSubject('Register symfony.llk-guild.ru')
      ->setFrom('support@symfony.llk-guild.ru')
      ->setTo($email)
      ->setBody(
        $this->renderView(
          'Emails/register.html.twig',
          array('code' => $user->confirmCode)
        ),
        'text/html'
      )
    ;
    $this->get('mailer')->send($message);
    return $this->json(true);
  }
}
