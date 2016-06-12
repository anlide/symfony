<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Entity\PostEmail;
use AppBundle\Entity\PostUser;
use AppBundle\Entity\PostView;
use AppBundle\Entity\User;
use AppBundle\Repository\PostRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    // Я уверен, что как-то можно сделать лучше - но время совсем поджимает TODO
    // Сделаю несколько вариантов запросов (хотя разумеется должна быть возможно сделать в виде одного запроса)
    // Если роль Модератор - просто выгребаем все сообщения
    // Если роль пользователь - то выгребаем все свои сообщения и все shared сообщения
    //
    // С прямыми ссылками ничего тут не делаем
    if ($user->role == 'moderator') {
      $posts = $this->getDoctrine()
        ->getRepository('AppBundle:Post')
        ->findAll();
      return $this->json($posts);
    }
    /**
     * @var PostRepository $repository
     */
    $repository = $this->getDoctrine()->getRepository('AppBundle:Post');
    $query = $repository->createQueryBuilder('p')
      ->select()
      ->leftJoin('AppBundle:PostUser', 'pu', 'WITH', 'p.id = pu.idPost AND pu.idUser = :user')
      ->where('p.author = :user')
      ->orWhere('pu.idUser = :user')
      ->setParameters(array('user' => $userId))
      ->getQuery();
    $posts = $query->getResult();
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
    //if ($userId === null) return $this->redirect('/'); // Гостей не блокируем в этом месте
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
    // Сообщение должно открываться для просмотра, а для редактирования надо сделать отдельный метод
    // Разрешить смотреть автору
    // Разрешить смотреть модератору
    // Разрешить смотреть если сообщение открыто по прямой ссылке
    // Разрешить смотреть если выдан персональный доступ
    $allow = $allowEdit = false;
    if ($post->author == $userId) $allow = $allowEdit = true;
    if (($user !== null) && ($user->role == 'moderator')) $allow = $allowEdit = true;
    if ($post->shared) $allow = true;
    $postUser = $this->getDoctrine()
      ->getRepository('AppBundle:PostUser')
      ->findOneBy(array('idUser' => $userId));
    if ($postUser !== null) $allow = true;
    if (!$allow) throw new AccessDeniedHttpException();
    // Подсчитаем количество просмотров и увеличим на 1, если данный пользователь не смотрел.
    // Да. Можно по разному считать просмотры, но задаче не сказано каким именно образом вести счёт.
    // Я выбрал такой. Один зарегистрированный пользовать = 1 просмотр. Любой гость = 1 просмотр.
    $postView = $this->getDoctrine()
      ->getRepository('AppBundle:PostView')
      ->findOneBy(array('idUser' => ($userId !== null)?$userId:0, 'idPost' => $id));
    if ($postView === null) {
      $postView = new PostView();
      $postView->idPost = $id;
      $postView->idUser = ($userId !== null)?$userId:0;
      $this->getDoctrine()->getManager()->persist($postView);
      $this->getDoctrine()->getManager()->flush();
    }
    /**
     * @var QueryBuilder $qb
     */
    $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
    $qb->select($qb->expr()->count('pv.idUser'));
    $qb->from('AppBundle:PostView', 'pv');
    $qb->where('pv.idPost = :post');
    $qb->setParameters(array('post' => $id));

    $views = $qb->getQuery()->getSingleScalarResult();

    return $this->render('post.html.twig', array('post' => $post, 'allowEdit' => $allowEdit, 'views' => $views));
  }
  /**
   * RESTful view
   * @Route("/post={id}/edit", name="post_edit")
   * @Method({"GET"})
   */
  public function editAction(Request $request, $id) {
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
    /**
     * @var Post $post
     */
    $post = $this->getDoctrine()
      ->getRepository('AppBundle:Post')
      ->findOneBy(array('id' => $id));
    if ($post === null) throw new NotFoundHttpException();
    // Сообщение должно открываться для просмотра, а для редактирования надо сделать отдельный метод
    // Разрешить редактировать автору
    // Разрешить редактировать модератору -- НО отправить его в админку
    $author = ($post->author == $user->id);
    if (($user->role == 'moderator') && (!$author)) {
      return $this->redirect('/admin/post='.$post->id);
    }
    if (!$author) throw new AccessDeniedHttpException();
    return $this->render('post.edit.html.twig', array('post' => $post));
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
