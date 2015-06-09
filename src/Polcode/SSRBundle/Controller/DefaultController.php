<?php

namespace Polcode\SSRBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    
    public function indexAction() {
        
        $depertuares = $this->getDoctrine()->getManager()->getRepository('SSRBundle:Depertuare')->findAll();
        
        foreach($depertuares as $dep) {
            print_r($dep->hour);
        }
        
        return $this->render('SSRBundle:Default:index.html.twig', array('name' => 'NAME'));
    }
    
}
