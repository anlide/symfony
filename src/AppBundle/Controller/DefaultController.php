<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Session\Session;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
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
        if ($userId === null) return $this->render('guest.html.twig');
        return $this->render('user.html.twig', array('user' => $user));
    }
}
