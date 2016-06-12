<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
}
