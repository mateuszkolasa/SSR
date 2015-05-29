<?php

namespace Polcode\SSRBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('SSRBundle:Default:index.html.twig', array('name' => 'NAME'));
    }
}
