<?php
namespace Polcode\SSRBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Polcode\SSRBundle\Entity\Depertuare;
use Polcode\SSRBundle\Entity\Line;
use Polcode\SSRBundle\Entity\Stop;
use Goutte\Client;

set_time_limit(2400);
class GetKrakowCommand extends ContainerAwareCommand {
	
	private $dev = false;
	private $entities = false;
	private $limit = 10;
	private $onlyTrams = false;
	private $SQL = true;

	protected function configure() {
		$this->setName('get:krakow')
			->setDescription('Pobiera rozklady dla miasta Krakowa');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {

		$em = $this->getContainer()->get('doctrine')->getManager();
		
		$client = new Client();
		
		$crawler = $client->request('GET', 'http://rozklady.mpk.krakow.pl/aktualne/przystan.htm');
		
		$stops = array();
		$counter = 0;
		$crawler->filter('li > a')->each(function ($node) use (&$em, &$stops, &$counter) {
			if($this->limit != 0 && $counter >= $this->limit) return;
		
			$id = str_replace('http://rozklady.mpk.krakow.pl/aktualne/p/', '', $node->link()->getUri());
			$id = str_replace('.htm', '', $id);
		
			$stops[$id]['name'] = $node->text();
		
			$counter++;
		});
		
		//if($this->entities) $em->flush();
	
		foreach($stops as $id => $stop) {
			$crawler = $client->request('GET', 'http://rozklady.mpk.krakow.pl/aktualne/p/' . $id . '.htm');
			$crawler->filter('li > a')->each(function ($node) use (&$em, &$stops, $id) {
				if($node->text() != 'Inne przystanki') {
					$line = explode(' - > ', $node->text());
					$tmp = explode('/', $node->link()->getUri());
					$tmp[count($tmp)-1] = str_replace('r', 't', $tmp[count($tmp)-1]);
	
					if($this->onlyTrams && $line[0] >= 100) return;
	
					$line[] = implode('/', $tmp);
	
					$stops[$id]['lines'][] = $line;
					//if($this->entities) $em->flush();
				}
			});
	
				if($this->dev) break;
		}
		
		foreach($stops as $id => $stop) {
			$depertuares = array();
			if(!array_key_exists('lines', $stop)) $stops[$id]['lines'] = array();
			foreach($stops[$id]['lines'] as $idL => $line) {
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
							}
						}
	
						//$em->flush();
						$x++;
					}
				});
	
					$stops[$id]['lines'][$idL][2] = $depertuares;
					if($this->dev) break;
			}
			if($this->dev) break;
		}
		
		//if($this->entities) $em->flush();
		//if(!$this->entities && !$this->SQL) { echo '<pre>'; print_r($stops); die('</pre>---'); }
	
		if($this->SQL) {
	
			foreach($stops as $stopID => $stop) {
	
				$stopOBJ = $em->getRepository('SSRBundle:Stop')->findOneBy((array('name' => $stop['name'])));
				if($stopOBJ == null) {
					$stopOBJ = new Stop();
					$stopOBJ->name = $stop['name'];
					$em->persist($stopOBJ);
				}
	
				//linie
				foreach($stops[$stopID]['lines'] as $lineID => $line) {
					$lineOBJ = $em->getRepository('SSRBundle:Line')->findOneBy((array('number' => $line[0], 'direction' => $line[1])));
					if($lineOBJ == null) {
						$lineOBJ = new Line();
						$lineOBJ->number = $line[0];
						$lineOBJ->direction = $line[1];
						$lineOBJ->addStop($stopOBJ);
						$em->persist($lineOBJ);
					}
	
					foreach($stops[$stopID]['lines'][$lineID][2] as $type => $depertuares) {
						foreach($depertuares as $hour => $minutes) {
							foreach($minutes as $minute) {
								$depertuareOBJ = new Depertuare();
								$depertuareOBJ->line = $lineOBJ;
								$depertuareOBJ->stop = $stopOBJ;
								$depertuareOBJ->type = $type;
								$depertuareOBJ->hour = $hour;
								$depertuareOBJ->minute = $minute;
	
								$em->persist($depertuareOBJ);
							}
						}
					}
	
					$em->flush();
				}
			}
		}
		//$output->writeln('Synchronization using Roo server...');
		$output->writeln('Done!');
	}
}