<?php
namespace Polcode\SSRBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 * @ORM\Table(name="krakow_depertuares")
 */
class Depertuare {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Line", inversedBy="depertuares")
     * @ORM\JoinColumn(name="line", referencedColumnName="id")
     */
    public $line;

    /**
     * @ORM\ManyToOne(targetEntity="Stop", inversedBy="depertuares")
     * @ORM\JoinColumn(name="stop", referencedColumnName="id")
     */
    public $stop;

    /*
     * @ORM\ManyToMany(targetEntity="Stop", inversedBy="depertuares")
     * @ORM\JoinTable(name="depertuares_stops",
     *      joinColumns={@ORM\JoinColumn(name="stop", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="depertuare", referencedColumnName="id")}
     * )
     */
    //public $stop;

    /**
     * @ORM\Column(type="string")
     */
    public $type;

    /**
     * @ORM\Column(type="integer")
     */
    public $hour;

    /**
     * @ORM\Column(type="integer")
     */
    public $minute;
}