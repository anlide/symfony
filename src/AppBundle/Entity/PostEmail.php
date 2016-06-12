<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Post
 *
 * @ORM\Table(name="post_email")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class PostEmail
{
  /**
   * @var int
   *
   * @ORM\Column(name="id_post", type="integer")
   * @ORM\Id
   */
  public $idPost;
  /**
   * @var string
   *
   * @ORM\Column(name="email", type="text")
   * @ORM\Id
   */
  public $email;

}

