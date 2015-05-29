<?php

namespace Polcode\SSRBundle\Controller\Cities;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Polcode\SSRBundle\Controller\CityController;
use Symfony\Component\HttpFoundation\Response;
use Goutte\Client;

class KrakowController extends CityController {
    
    private $dev = true;
    
    public function indexAction() {
        return $this->render('SSRBundle:Cities/Krakow:index.html.twig', array('name' => 'Kraków'));
    }
    
    public function downloadAction() {
        $client = new Client();
        
        $crawler = $client->request('GET', 'http://rozklady.mpk.krakow.pl/aktualne/przystan.htm');
        
        $stops = array();
        $crawler->filter('li > a')->each(function ($node) use (&$stops) {
            $id = str_replace('http://rozklady.mpk.krakow.pl/aktualne/p/', '', $node->link()->getUri());
            $id = str_replace('.htm', '', $id);
            
            $stops[$id]['name'] = $node->text();
        });
        
        foreach($stops as $id => $stop) {
            $crawler = $client->request('GET', 'http://rozklady.mpk.krakow.pl/aktualne/p/' . $id . '.htm');
            $crawler->filter('li > a')->each(function ($node) use (&$stops, $id) {
                if($node->text() != 'Inne przystanki') {
                    $line = explode(' - > ', $node->text());
                    $tmp = explode('/', $node->link()->getUri());
                    $tmp[count($tmp)-1] = str_replace('r', 't', $tmp[count($tmp)-1]); 
                    
                    $line[] = implode('/', $tmp);
                    $stops[$id]['lines'][] = $line;
                }
            });
            if($this->dev) break;
        }
        

        /*foreach($stops as $id => $stop) {
            foreach($stop['lines'] as $idL => $line) {
                $crawler = $client->request('GET', $line[2]);
                $crawler->filter('frame')->each(function ($node) use (&$stops, $id, $idL) {
                    if($node->attr('name') == 'R') {
                        $stops[$id]['lines'][$idL][2] = explode('/', $stops[$id]['lines'][$idL][2]);
                        array_pop($stops[$id]['lines'][$idL][2]);
                        $stops[$id]['lines'][$idL][2][] = $node->attr('src');
                        $stops[$id]['lines'][$idL][2] = implode('/', $stops[$id]['lines'][$idL][2]);
                    }
                });
                if($this->dev) break;
            }
            if($this->dev) break;
        }*/
        
        
        return new Response('<pre>' . print_r($stops['p0782'], 1) . '</pre>');
    }
    
}