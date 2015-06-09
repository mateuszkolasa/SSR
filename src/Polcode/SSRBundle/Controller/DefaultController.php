<?php

namespace Polcode\SSRBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller {
    
    public function indexAction($city) {
        return $this->render('SSRBundle:Default:index.html.twig', array('city' => $city));
    }
    
    public function apiAction($stock, $city) {
        if($stock == 'stops') {
            $entities = $this->getDoctrine()->getRepository('SSRBundle:Stop')->findAll();
            
            $stops = array();
            foreach($entities as $stop) {
                $stops[] = array('id' => $stop->id, 'name' => $stop->name);
            }
            
            return new JsonResponse($stops);
        }
    }
    
}
