<?php
namespace Polcode\SSRBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 * @ORM\Table(name="krakow_lines")
 */
class Line {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    
    /**
     * @ORM\Column(type="string")
     */
    public $number;
    
    /**
     * @ORM\Column(type="string")
     */
    public $direction;
    
    /**
     * @ORM\ManyToMany(targetEntity="Stop", mappedBy="lines")
     */
    public $stops;
    
    public function __construct() {
        $this->stops = new ArrayCollection();
    }
    
    public function addStop($stop) {
        $this->stops->add($stop);
    }
}