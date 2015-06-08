<?php

namespace Polcode\SSRBundle\Controller\Cities;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Polcode\SSRBundle\Controller\CityController;
use Symfony\Component\HttpFoundation\Response;
use Goutte\Client;
use Polcode\SSRBundle\Entity\Line;
use Polcode\SSRBundle\Entity\Stop;

class KrakowController extends CityController {
    
    private $dev = false;
    private $entities = true;
    private $limit = 0;
    
    public function indexAction() {
        return $this->render('SSRBundle:Cities/Krakow:index.html.twig', array('name' => 'Kraków'));
    }
    
    public function downloadAction() {
        $em = $this->getDoctrine()->getManager();
        
        $client = new Client();
        
        $crawler = $client->request('GET', 'http://rozklady.mpk.krakow.pl/aktualne/przystan.htm');
        
        $stops = array();
        $counter = 0;
        $crawler->filter('li > a')->each(function ($node) use (&$em, &$stops, &$counter) {
            if($this->limit != 0 && $counter > $this->limit) return;
            $counter;
            
            $id = str_replace('http://rozklady.mpk.krakow.pl/aktualne/p/', '', $node->link()->getUri());
            $id = str_replace('.htm', '', $id);
            
            $stops[$id]['name'] = $node->text();
            
            if($this->entities) {
                $entity = $em->getRepository('SSRBundle:Stop')->findOneBy((array('name' => $node->text())));
                if($entity == null) {
                    $entity = new Stop();
                    $entity->name = $node->text();
                    
                    $em->persist($entity);
                }
                
                $stops[$id]['OBJ'] = $entity;
            }
            
            $counter++;
        });
        
        if($this->entities) $em->flush();
        
        foreach($stops as $id => $stop) {
            $crawler = $client->request('GET', 'http://rozklady.mpk.krakow.pl/aktualne/p/' . $id . '.htm');
            $crawler->filter('li > a')->each(function ($node) use (&$em, &$stops, $id) {
                if($node->text() != 'Inne przystanki') {
                    $line = explode(' - > ', $node->text());
                    $tmp = explode('/', $node->link()->getUri());
                    $tmp[count($tmp)-1] = str_replace('r', 't', $tmp[count($tmp)-1]); 
                    
                    $line[] = implode('/', $tmp);
                    
                    if($this->entities) {
                        $entity = $em->getRepository('SSRBundle:Line')->findOneBy((array('number' => $line[0], 'direction' => $line[1])));
                        if($entity == null) {
                            $entity = new Line();
                            $entity->number = $line[0];
                            $entity->direction = $line[1];
                            $entity->addStop($stops[$id]['OBJ']);
                            $em->persist($entity);
                            $line['OBJ'] = $entity;
                        }
                    }
                    
                    $stops[$id]['lines'][] = $line;
                }
            });
                
            if($this->dev) break;
        }
        
        if($this->entities) {
            
        }
        //echo '<pre>'; print_r($stops); die('---');
        die('---');
        
        foreach($stops as $id => $stop) {
            $x = 0; $h = 0; $lastVariable = null; $dupa = array();
            foreach($stop['lines'] as $idL => $line) {
                $crawler = $client->request('GET', $line[2]);
                $crawler->filter('.celldepart table tr td')->each(function ($node) use (&$stops, $id, $idL, &$x, &$h, &$dupa, &$lastVariable) {
                    //nagłówki
                    if($h < 3) {
                        $h++;
                    } else {
                        if(!ctype_digit($node->text()) && ($x%6 == 0 || $x%6 == 2 || $x%6 == 4)) {
                            $lastVariable = $node->text();
                            $h++; $x++;
                            return;
                        }
                        if($x%6 == 0) $dupa['powszedni'][$node->text()] = array();
                        if($x%6 == 2) $dupa['sobota'][$node->text()] = array();
                        if($x%6 == 4) $dupa['swieto'][$node->text()] = array();

                        $minutes = explode(' ', $node->text());
                        
                        if($x%6 == 1) {
                            foreach($minutes as $minute)
                                if(!empty($minute) && $minute != '-') $dupa['powszedni'][$lastVariable][] = (int) preg_replace('#[a-zA-Z]#', '', $minute);  
                        }
                        
                        if($x%6 == 3) {
                            foreach($minutes as $minute)
                                if(!empty($minute) && $minute != '-') $dupa['sobota'][$lastVariable][] = (int) preg_replace('#[a-zA-Z]#', '', $minute);  
                        }

                        if($x%6 == 5) {
                            foreach($minutes as $minute)
                                if(!empty($minute) && $minute != '-') $dupa['swieto'][$lastVariable][] = (int) preg_replace('#[a-zA-Z]#', '', $minute);
                        }
                        
                        $lastVariable = $node->text();
                        $x++;
                    }
                });
                $stops[$id]['lines'][$idL][2] = $dupa;
                if($this->dev) break;
            }
            if($this->dev) break;
        }
        
        return new Response('<pre>' . print_r($stops, 1) . '</pre>');
    }
    
}