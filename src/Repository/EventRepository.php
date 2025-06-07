<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function search(array $criteria): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.location', 'l')
            ->addSelect('l')
            ->leftJoin('e.type', 't')
            ->addSelect('t')
            ->orderBy('e.startAt', 'ASC');
    
        if (!empty($criteria['q'])) {
            $qb->andWhere('e.title LIKE :q OR e.description LIKE :q')
               ->setParameter('q', '%' . $criteria['q'] . '%');
        }
    
        if (!empty($criteria['location'])) {
            $qb->andWhere('l.city LIKE :loc OR l.name LIKE :loc')
               ->setParameter('loc', '%' . $criteria['location'] . '%');
        }
    
        if (!empty($criteria['dateFrom'])) {
            $qb->andWhere('e.startAt >= :dateFrom')
               ->setParameter('dateFrom', $criteria['dateFrom']);
        }
    
        if (!empty($criteria['type'])) {
            $qb->andWhere('e.type = :type')
               ->setParameter('type', $criteria['type']);
        }
    
        if (!empty($criteria['onlyAvailable']) && $criteria['onlyAvailable']) {
            $qb->andWhere('e.capacity > (
                SELECT COALESCE(SUM(b.quantity), 0)
                FROM App\Entity\Booking b
                WHERE b.event = e
            )');
        }
    
        return $qb->getQuery()->getResult();
    }
    
    public function searchQueryBuilder(array $criteria): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.location', 'l')->addSelect('l')
            ->leftJoin('e.type', 't')->addSelect('t')
            ->orderBy('e.startAt', 'ASC');
    
        if (!empty($criteria['q'])) {
            $qb->andWhere('e.title LIKE :q OR e.description LIKE :q')
                ->setParameter('q', '%' . $criteria['q'] . '%');
        }
    
        // tu peux rajouter les autres filtres ici...
    
        return $qb;
    }    

    //    /**
    //     * @return Event[] Returns an array of Event objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Event
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function countCompletedEvents(): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.capacity = SIZE(e.bookings)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    public function findNextEvent(): ?Event
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.startAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function averageFillingRate(): float
    {
        $events = $this->findAll();
    
        $totalCapacity = 0;
        $totalBookings = 0;
    
        foreach ($events as $event) {
            $totalCapacity += $event->getCapacity();
            foreach ($event->getBookings() as $booking) {
                $totalBookings += $booking->getQuantity();
            }
        }
    
        return $totalCapacity > 0 ? round(($totalBookings / $totalCapacity) * 100, 2) : 0;
    }

    public function countByType(): array
    {
        return $this->createQueryBuilder('e')
            ->select('t.name as type, COUNT(e.id) as count')
            ->join('e.type', 't')
            ->groupBy('t.name')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingEvents(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.title, e.startAt')
            ->where('e.startAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }    
    
}
