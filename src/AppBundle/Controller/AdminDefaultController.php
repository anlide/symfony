<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\Session;

class AdminDefaultController extends Controller
{
  /**
   * @Route("/admin", name="admin_homepage")
   */
  public function indexAction(Request $request)
  {
    return $this->render('admin.html.twig', array());
  }
}
