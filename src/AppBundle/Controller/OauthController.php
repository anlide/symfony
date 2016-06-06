<?php

namespace AppBundle\Controller;

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
    return $this->render(
      'guest.html.twig',
      array('luckyNumberList' => '12, 34')
    );
  }
}
