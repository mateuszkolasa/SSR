<?php

namespace Polcode\SSRBundle\Controller\Cities;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Polcode\SSRBundle\Controller\CityController;
use Symfony\Component\HttpFoundation\Response;
use Goutte\Client;
use Polcode\SSRBundle\Entity\Line;
use Polcode\SSRBundle\Entity\Stop;
use Polcode\SSRBundle\Entity\Depertuare;
use Symfony\Component\HttpFoundation\JsonResponse;


set_time_limit(600);
class KrakowController extends CityController {
    
    private $dev = false;
    private $entities = true;
    private $limit = 0;
    private $onlyTrams = true;
    
    public function indexAction() {
        return $this->render('SSRBundle:Cities/Krakow:index.html.twig', array('name' => 'Kraków'));
    }
    
    public function apiAction($stopID) {
        
        if($stopID == 0) {
            $depertuares = $this->getDoctrine()->getManager()->getRepository('SSRBundle:Depertuare')->getNextDepertuares(3);
        } else {
            $depertuares = $this->getDoctrine()->getManager()->getRepository('SSRBundle:Depertuare')->getNextDepertuares($stopID);
        }
        
        $returnArray = array();
        $x = 0;
        foreach($depertuares as $dep) {
            $returnArray[] = array('line' => $dep->line->number, 'direction' => $dep->line->direction, 'time' => $dep->hour .':' . $dep->minute);
            if(++$x > 4) break;
        }
        
        return new JsonResponse($returnArray);
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
                    
                    if($this->onlyTrams && $line[0] >= 100) return;
                    
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
                    if($this->entities) $em->flush();
                }
            });
                
            if($this->dev) break;
        }
        
        foreach($stops as $id => $stop) {
            $depertuares = array();
            if(!array_key_exists('lines', $stop)) $stop['lines'] = array();
            foreach($stop['lines'] as $idL => $line) {
                $crawler = $client->request('GET', $line[2]);
                $crawler->filter('.celldepart table tr')->each(function ($node) use (&$stops, $id, $idL, &$line, &$depertuares, &$em) {
                    if($node->children()->attr('class') == 'cellday' || $node->children()->attr('class') == 'cellinfo') {
                        return;
                    }
                    
                    $x = 0; $type = 'p'; $hour = 0;
                    foreach($node->children() as $child) {
                        if($child->getAttributeNode('class')->value == 'cellhour') {
                            if($x%6 == 0) $type = 'p';
                            if($x%6 == 2) $type = 's';
                            if($x%6 == 4) $type = 'h';
                            $hour = $child->textContent;
                            $x++;
                            continue;
                        }
                        
                        $minutes = explode(' ', $child->textContent);
                        
                        foreach($minutes as $minute) {
                            $value = preg_replace('#[a-zA-Z]#', '', $minute);
                            if(!empty($minute) && $minute != '-') {
                                $depertuares[$type][$hour][] = $value;
                        
                                if($this->entities) {
                                    $entity = $em->getRepository('SSRBundle:Depertuare')->findOneBy((array('type' => $type, 'line' => $line['OBJ'], 'stop' => $stops[$id]['OBJ'])));
                                    if($entity == null) {
                                        $entity = new Depertuare();
                                        $entity->line = $line['OBJ'];
                                        $entity->stop = $stops[$id]['OBJ'];
                                        $entity->hour = $hour;
                                        $entity->minute = $value;
                                        $entity->type = $type;
                                        $em->persist($entity);
                                    }
                                }
                            }
                        }

                        $em->flush();
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