<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Oauth\OauthAbstract;
use AppBundle\Oauth\OauthVk;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class OauthController extends Controller
{
  /**
   * @Route("/oauth/vk", name="oauth_vk")
   */
  public function vkAction(Request $request)
  {
    $code = $request->query->get('code');
    try {
      $vk = OauthAbstract::getInstance('Vk');
      $vk->fetchUserData($code, $request->getHost());
    } catch (\Exception $e) {
      // По какой-то причине данные не может получить
      return $this->redirect($request->getSchemeAndHttpHost());
    }
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('vk' => $vk->providerKey));
    $session = $request->getSession();
    $session->start();
    if ($user === null) {
      // Пользователя нет - значит надо предложить закончить регистрацию
      $session->set('oauth', $vk);
      return $this->redirect($request->getSchemeAndHttpHost().'/oauth/register');
    }
    // Пользователь есть - значит пишем его в сессию и обновляем страницу
    $session->set('user', $user->getId());
    return $this->redirect($request->getSchemeAndHttpHost());
  }
  /**
   * @Route("/oauth/register", name="oauth_register")
   */
  public function registerAction(Request $request)
  {
    $session = $request->getSession();
    $session->start();
    $vk = $session->get('oauth');
    return $this->render('oauth.register.html.twig', array('vk' => $vk));
  }
  /**
   * @Route("/oauth/register/finish", name="oauth_register_finish")
   */
  public function registerFinishAction(Request $request)
  {
    $session = $request->getSession();
    $session->start();
    /**
     * @var OauthVk $vk
     */
    $vk = $session->get('oauth');
    $user = new User();
    $user->email = null;
    $user->password = null;
    $user->confirmCode = null;
    $user->name = $vk->user;
    $user->vk = $vk->providerKey;
    $em = $this->getDoctrine()->getManager();
    $em->persist($user);
    $em->flush();
    $session->remove('oauth');
    $session->set('user', $user->getId());
    return $this->json(true);
  }
}
