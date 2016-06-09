<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\Session;

class PostController extends Controller
{
  /**
   * @Route("/new", name="post_new")
   */
  public function indexAction(Request $request)
  {
    return $this->render('post.html.twig', array());
  }
}
