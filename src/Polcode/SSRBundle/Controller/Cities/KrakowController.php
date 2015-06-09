<?php

namespace Polcode\SSRBundle\Controller\Cities;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Polcode\SSRBundle\Controller\CityController;
use Symfony\Component\HttpFoundation\Response;
use Goutte\Client;
use Polcode\SSRBundle\Entity\Line;
use Polcode\SSRBundle\Entity\Stop;
use Polcode\SSRBundle\Entity\Depertuare;

class KrakowController extends CityController {
    
    private $dev = false;
    private $entities = true;
    private $limit = 1;
    
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
            if($this->limit != 0 && $counter >= $this->limit) return;
            
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
                            //$stops[$id]['OBJ']->addLine($entity);
                        }
                        
                        $line['OBJ'] = $entity;
                    }
                    
                    $stops[$id]['lines'][] = $line;
                }
            });
                
            if($this->dev) break;
        }
        
        if($this->entities) $em->flush();
        
        foreach($stops as $id => $stop) {
            $x = 0; $h = 0; $lastVariable = null; $depertuares = array();
            foreach($stop['lines'] as $idL => $line) {
                $crawler = $client->request('GET', $line[2]);
                $crawler->filter('.celldepart table tr td')->each(function ($node) use (&$stops, $id, $idL, &$line, &$x, &$h, &$depertuares, &$lastVariable, &$em) {
                    //nagłówki
                    if($h < 3) {
                        $h++;
                    } else {
                        if(!ctype_digit($node->text()) && ($x%6 == 0 || $x%6 == 2 || $x%6 == 4)) {
                            $lastVariable = $node->text();
                            $h++; $x++;
                            return;
                        }
                        if($x%6 == 0) $depertuares['powszedni'][$node->text()] = array();
                        if($x%6 == 2) $depertuares['sobota'][$node->text()] = array();
                        if($x%6 == 4) $depertuares['swieto'][$node->text()] = array();

                        if($x%6 == 0 || $x%6 == 2 || $x%6 == 4) {
                            $lastVariable = $node->text();
                            $x++;
                            return;
                        }
                        
                        $minutes = explode(' ', $node->text());
                        
                        if($x%6 == 1) $type = 'powszedni';
                        if($x%6 == 3) $type = 'sobota';
                        if($x%6 == 5) $type = 'swieto';
                        
                        foreach($minutes as $minute) {
                            $value = (int) preg_replace('#[a-zA-Z]#', '', $minute);
                            if(!empty($minute) && $minute != '-') {
                                $depertuares[$type][$lastVariable][] = $value;
                            
                                if($this->entities) {
                                    $entity = $em->getRepository('SSRBundle:Depertuare')->findOneBy((array('type' => $type, 'line' => $line['OBJ'], 'stop' => $stops[$id]['OBJ'])));
                                    if($entity == null) {
                                        $entity = new Depertuare();
                                        $entity->line = $line['OBJ'];
                                        $entity->stop = $stops[$id]['OBJ'];
                                        $entity->hour = $lastVariable;
                                        $entity->minute = $value;
                                        $entity->type = $type;
                                        $em->persist($entity);
                                    }
                                }
                            }
                        }
                        
                        $em->flush();
                        $lastVariable = $node->text();
                        $x++;
                    }
                });
                $stops[$id]['lines'][$idL][2] = $depertuares;
                if($this->dev) break;
            }
            if($this->dev) break;
        }
        
        if($this->entities) $em->flush();
        if(!$this->entities) { echo '<pre>'; print_r($stops); die('</pre>---'); }
        
        return new Response('DONE!');
    }
    
}