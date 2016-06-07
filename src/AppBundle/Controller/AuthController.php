<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    return $this->json($user !== null);
  }
  /**
   * @Route("/login-check={email}/{password}", name="login_check")
   */
  public function loginCheckAction($email, $password)
  {
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email, 'password' => md5($password)));
    if ($user !== null) $_SESSION['user'] = $email;
    return $this->json($user !== null);
  }
  /**
   * @Route("/logout", name="logout")
   */
  public function logoutAction()
  {
    unset($_SESSION['user']);
    return $this->json(true);
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
    $json = json_decode($request->getContent(), true);
    $email = $json['email'];
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email));
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
    $json = json_decode($request->getContent(), true);
    $email = $json['email'];
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email));
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
