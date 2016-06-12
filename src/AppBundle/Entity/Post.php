<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Post
 *
 * @ORM\Table(name="post")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 */
class Post
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
   * @ORM\Column(name="author", type="integer")
   */
  public $author;
  /**
   * В этом месте symfony ведёт себя по-рагульному! Он не умеет работать с timestamp плак-плак!
   * @ORM\Column(name="time", type="integer")
   */
  public $time;
  /**
   * @ORM\Column(name="title", type="text")
   */
  public $title;
  /**
   * @ORM\Column(name="text", type="text")
   */
  public $text;
  /**
   * @ORM\Column(name="shared", type="boolean")
   */
  public $shared = false;

  /**
   * Get id
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

}

