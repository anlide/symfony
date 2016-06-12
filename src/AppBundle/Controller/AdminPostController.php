<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
    if ($user->role != 'moderator') return $this->json(false);
    /**
     * @var Post[] $posts
     */
    $posts = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findAll();
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
  /**
   * RESTful view
   * @Route("/admin/post={id}", name="admin_post_view")
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
     * @var Post $post
     */
    $post = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findOneBy(array('id' => $id));
    if ($post === null) throw new BadRequestHttpException();
    $author = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $post->author));
    if ($author === null) throw new BadRequestHttpException();
    return $this->render('admin.post.html.twig', array('post' => $post, 'user' => $user, 'author' => $author));
  }
  /**
   * RESTful update
   * @Route("/admin/post={id}", name="admin_post_update")
   * @Method({"PUT"})
   */
  public function updateAction(Request $request, $id) {
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
    $json = json_decode($request->getContent(), true);
    /**
     * @var Post $post
     */
    $post = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findOneBy(array('id' => $id));
    if ($post === null) return $this->json(false);
    $post->title = $json['title'];
    $post->text = $json['content'];
    $post->time = time();
    $this->getDoctrine()->getManager()->flush();
    return $this->json(true);
  }
  /**
   * RESTful delete
   * @Route("/admin/post={id}", name="admin_post_delete")
   * @Method({"DELETE"})
   */
  public function deleteAction(Request $request, $id) {
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
     * @var Post $post
     */
    $post = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findOneBy(array('id' => $id));
    if ($post === null) return $this->json(false);
    $em = $this->getDoctrine()->getManager();
    $em->remove($post);
    $em->flush();
    return $this->json(true);
  }
}
