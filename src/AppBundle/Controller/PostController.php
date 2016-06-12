<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Entity\PostEmail;
use AppBundle\Entity\PostUser;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;

class PostController extends Controller
{
  /**
   * @Route("/posts", name="posts_json")
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
    $posts = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findBy(array('author' => $userId));
    return $this->json($posts);
  }
  /**
   * RESTful create
   * @Route("/post", name="post_create")
   * @Method({"POST"})
   */
  public function createAction(Request $request) {
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($user === null) return $this->json(false);
    $json = json_decode($request->getContent(), true);
    $post = new Post();
    $post->title = $json['title'];
    $post->text = $json['content'];
    $post->author = $user->getId();
    $post->time = time();
    // Тут должны быть трёхэтажные маты в сторону symfony, что он умеет работать с mysql-timestamp
    // Из-за этого тут потенциальных багов целое море
    // Поймал несколько багов и решил переделать на integer
    $em = $this->getDoctrine()->getManager();
    $em->persist($post);
    $em->flush();
    return $this->json($post);
  }
  /**
   * RESTful view
   * @Route("/post={id}", name="post_view")
   * @Method({"GET"})
   */
  public function viewAction(Request $request, $id) {
    $session = $request->getSession();
    $session->start();
    $userId = $session->get('user');
    /**
     * @var User $user
     */
    $user = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('id' => $userId));
    if ($user === null) return $this->redirect('/');
    /**
     * @var Post $post
     */
    $post = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findOneBy(array('id' => $id));
    if ($post === null) return $this->redirect('/');
    return $this->render('post.html.twig', array('post' => $post));
  }
  /**
   * RESTful update
   * @Route("/post={id}", name="post_update")
   * @Method({"PUT"})
   */
  public function updateAction(Request $request, $id) {
    // TODO: удалять присоединённые неиспользуемые теперь картинки
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
   * @Route("/post={id}", name="post_delete")
   * @Method({"DELETE"})
   */
  public function deleteAction(Request $request, $id) {
    // TODO: удалять присоединённые картинки
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
  /**
   * RESTful update
   * @Route("/post={id}/share", name="post_share")
   * @Method({"PUT"})
   */
  public function shareAction(Request $request, $id) {
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
     * @var Post $post
     */
    $post = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findOneBy(array('id' => $id));
    if ($post === null) return $this->json(false);
    $post->shared = !$post->shared;
    $this->getDoctrine()->getManager()->flush();
    return $this->json(true);
  }
  /**
   * RESTful update
   * @Route("/post={id}/share_email", name="post_share_email")
   * @Method({"PUT"})
   */
  public function shareEmailAction(Request $request, $id) {
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
     * @var Post $post
     */
    $post = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findOneBy(array('id' => $id));
    if ($post === null) return $this->json(false);
    $json = json_decode($request->getContent(), true);
    $email = $json['email'];
    // Надо проверить - есть ли пользователь с таким email
    // Если есть - выдать по id доступ
    // Если нет - выдать по email доступ (то есть доступ будет выдан когда такой email появится у кого-то)
    // Самому себе не выдавать
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return $this->json('Надо указать существующий email');
    /**
     * @var User $userShare
     */
    $userShare = $this->getDoctrine()
      ->getRepository('AppBundle:User')
      ->findOneBy(array('email' => $email));
    if ($userShare === null) {
      $postEmail = $this->getDoctrine()
        ->getRepository('AppBundle:PostEmail')
        ->findOneBy(array('email' => $email));
      if ($postEmail !== null) return $this->json('Для '.$email.' уже есть сюда доступ');
      $postEmail = new PostEmail();
      $postEmail->idPost = $id;
      $postEmail->email = $email;
      $this->getDoctrine()->getManager()->persist($postEmail);
    } else {
      $postUser = $this->getDoctrine()
        ->getRepository('AppBundle:PostUser')
        ->findOneBy(array('idUser' => $userShare->id));
      if ($postUser !== null) return $this->json('Для '.$email.' уже есть сюда доступ');
      if ($userShare->id == $user->id) return $this->json('У вас и так уже есть доступ сюда');
      $postUser = new PostUser();
      $postUser->idPost = $id;
      $postUser->idUser = $userShare->id;
      $this->getDoctrine()->getManager()->persist($postUser);
    }
    $this->getDoctrine()->getManager()->flush();
    return $this->json(true);
  }
}
