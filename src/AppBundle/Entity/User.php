<?php

namespace AppBundle\Entity;

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
    private $id;

    /**
     * @ORM\Column(name="email", type="text")
     */
    public $email;
    /**
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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

  function generateRandomString($length = 7) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  public static function getByEmail($email) {
    $obj = new self();
  }

    public function register($email, $password) {
        $this->email = $email;
        $this->password = md5($password);
        $this->confirmCode = $this->generateRandomString();
    }
}

