<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;

class AdminUserController extends AdminController
{
  /**
   * @Route("/admin/users", name="admin_user_homepage")
   */
  public function indexAction(Request $request)
  {
    $user = $this->checkPermissions($request);
    return $this->render('admin.users.html.twig', array('user' => $user));
  }
  /**
   * @Route("/admin/users-list", name="admin-users_json")
   * @Method({"GET"})
   */
  public function listAction(Request $request) {
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
    if ($user->role != 'moderator') return $this->json(false);
    /**
     * @var User[] $users
     */
    $users = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findAll();
    return $this->json(array('users' => $users));
  }
}
