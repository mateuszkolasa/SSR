<?php
namespace Polcode\SSRBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 * @ORM\Table(name="krakow_stops")
 */
class Stop {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    
    /**
     * @ORM\Column(type="string")
     */
    public $name;
    
    /**
     * @ORM\ManyToMany(targetEntity="Line", cascade={"persist"}, inversedBy="lines")
     * @ORM\JoinTable(name="krakow_stops_lines",
     *      joinColumns={@ORM\JoinColumn(name="line_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="stop_id", referencedColumnName="id")}
     * )
     */
    public $lines;
    
    public function __construct() {
        $this->lines = new ArrayCollection();
    }
    
    public function addLine($line) {
        $this->lines->add($line);
    }
    
}