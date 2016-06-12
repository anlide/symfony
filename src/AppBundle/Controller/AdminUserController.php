<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
  /**
   * RESTful view
   * @Route("/admin/user={id}", name="admin_user_view")
   * @Method({"GET"})
   */
  public function viewAction(Request $request, $id) {
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    if ($userId === null) throw new AccessDeniedHttpException();
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($user === null) throw new AccessDeniedHttpException();
    if ($user->role != 'moderator') return $this->json(false);
    /**
     * @var User $userEdit
     */
    $userEdit = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $id));
    if ($userEdit === null) throw new BadRequestHttpException();
    return $this->render('admin.user.html.twig', array('userEdit' => $userEdit, 'user' => $user));
  }
  /**
   * RESTful admin avatar remove
   * @Route("/admin/user-avatar-remove={id}", name="admin_avatar_remove")
   * @Method({"DELETE"})
   */
  public function profileAvatarRemoveAction(Request $request, $id) {
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
     * @var User $userEdit
     */
    $userEdit = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $id));
    if ($userEdit === null) return $this->json(false);
    $userEdit->avatar = null;
    $this->getDoctrine()->getManager()->flush();
    return $this->json(true);
  }
  /**
   * RESTful update
   * @Route("/admin/user={id}", name="admin_user_update")
   * @Method({"PUT"})
   */
  public function updateAction(Request $request, $id) {
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
    if ($user->role != 'moderator') return $this->json(false);
    /**
     * @var User $userEdit
     */
    $userEdit = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $id));
    if ($userEdit === null) return $this->json(false);
    $json = json_decode($request->getContent(), true);
    $userEdit->name = $json['name'];
    $this->getDoctrine()->getManager()->flush();
    $return['name'] = true;
    return $this->json($return);
  }
}
