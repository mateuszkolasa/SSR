<?php

namespace Polcode\SSRBundle\Controller\Cities;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Polcode\SSRBundle\Controller\CityController;
use Symfony\Component\HttpFoundation\Response;

class KrakowController extends CityController
{
    
    public function indexAction() {
        return new Response('Witamy w KRakowie');
    }
    
}
