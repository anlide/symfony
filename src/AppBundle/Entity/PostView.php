<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Post
 *
 * @ORM\Table(name="post_view")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class PostView
{
  /**
   * @var int
   *
   * @ORM\Column(name="id_post", type="integer")
   * @ORM\Id
   */
  public $idPost;
  /**
   * @var int
   *
   * @ORM\Column(name="id_user", type="integer")
   * @ORM\Id
   */
  public $idUser;

}

