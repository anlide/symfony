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

    public function register($email, $password) {
        $this->email = $email;
        $this->password = md5($password);
    }
}

