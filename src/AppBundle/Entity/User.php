<?php

namespace AppBundle\Entity;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class User
{
  /**
   * @var int
   *
   * @ORM\Column(name="id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  public $id;

  /**
   * @ORM\Column(name="email", type="text")
   */
  public $email;
  /**
   * TODO: посыпать солью
   * @ORM\Column(name="password", type="text")
   */
  public $password = null;
  /**
   * @ORM\Column(name="confirm_code", type="string", length=7)
   */
  public $confirmCode = null;
  /**
   * @ORM\Column(name="vk", type="text")
   */
  public $vk = null;
  /**
   * @ORM\Column(name="google", type="text")
   */
  public $google = null;
  /**
   * @ORM\Column(name="name", type="text")
   */
  public $name = null;
  /**
   * Я хочу обратить внимание, что некоторые рагульные соц сети, например VK отдают ссылку на картинку в виде "http://...", хотя везде явно указано использовать https.
   * Поэтому правильно - это получив ссылку сразу скачивать аватарку себе.
   * В данном примере это не актуально, но это бывает проблемой.
   * @ORM\Column(name="avatar", type="text")
   */
  public $avatar = null;
  /**
   * Даже проверять не буду - поддерживается ли ENUM
   * @ORM\Column(name="role", type="text")
   */
  public $role = 'user';

  /**
   * Get id
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  public function generateRandomString($length = 7) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  public function register($email, $password) {
    $this->email = $email;
    $this->password = md5($password);
    $this->confirmCode = $this->generateRandomString();
    $this->name = $email;
  }

  public function checkPassword($password) {
    return (md5($password) == $this->password);
  }

  public function mergeAccout(User $userOther, Registry $doctrine) {
    if ($this->name == '') $this->name = $userOther->name;
    if ($this->email == null) $this->email = $userOther->email;
    if ($this->password == null) $this->password = $userOther->password;
    if ($this->vk === null) $this->vk = $userOther->vk;
    if ($this->google === null) $this->google = $userOther->google;
    if ($this->avatar == '') $this->avatar = $userOther->avatar;
    if (($this->role == 'user') && ($userOther->role != 'user')) $this->role = $userOther->role;
    /**
     * @var Post[] $posts
     */
    $posts = $doctrine
      ->getRepository('AppBundle:Post')
      ->findBy(array('author' => $userOther->getId()));
    foreach ($posts as $post) {
      $post->author = $this->id;
    }
    $doctrine->getManager()->remove($userOther);
  }
}

