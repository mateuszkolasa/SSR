<?php
namespace Polcode\SSRBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

class DepertuareRepository extends EntityRepository {

    public function getNextDepertuares($id) {
        $qb = $this->getEntityManager()
        ->createQueryBuilder()
        ->select('d')
        ->from('SSRBundle:Depertuare', 'd')
        ->join('d.stop', 's')
        ->where('d.hour > :hour')
        ->orWhere('(d.hour = :hourr AND d.minute >= :minute)')
        ->andWhere('s.id = :stop')
        ->orderBy('d.hour, d.minute', 'ASC');
            
        if(date("w") < 5) $qb->andWhere('d.type = \'p\'');
        if(date("w") == 5) $qb->andWhere('d.type = \'s\'');
        if(date("w") == 6) $qb->andWhere('d.type = \'h\'');
        
        $qb->setParameter('stop', $id);
        $qb->setParameter('hour', (int) date("H"));
        $qb->setParameter('hourr', (int) date("H"));
        $qb->setParameter('minute', (int) date("i"));
        
        return $qb->getQuery()->getResult();
    }
}