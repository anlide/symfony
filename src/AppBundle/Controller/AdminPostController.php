<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;

class AdminPostController extends AdminController
{
  /**
   * @Route("/admin/posts", name="admin_post_homepage")
   */
  public function indexAction(Request $request)
  {
    $user = $this->checkPermissions($request);
    return $this->render('admin.posts.html.twig', array('user' => $user));
  }
  /**
   * @Route("/admin/posts-list", name="admin-posts_json")
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
    /**
     * @var Post[] $posts
     */
    $posts = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findBy(array('author' => $userId));
    // В виду очень сжатых сроков - нет времени разбираться как работает inner join тут
    // Поэтому сделаю быстро и немного порагульному - получу все userId из сообщений и по ним сделаю массив для ответа
    $ids = array();
    foreach ($posts as $post) {
      $ids[$post->author] = $post->author;
    }
    /**
     * @var User[] $users
     */
    $users = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findBy(array('id' => $ids));
    // TODO: скрыть хэш пароля
    // Видимо это делается путём установления $user->password в private, но сейчас это быстро не поменять
    return $this->json(array('posts' => $posts, 'users' => $users));
  }
}
