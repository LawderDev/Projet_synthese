<?php

namespace App\Repository;

use App\Entity\Series;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Series|null find($id, $lockMode = null, $lockVersion = null)
 * @method Series|null findOneBy(array $criteria, array $orderBy = null)
 * @method Series[]    findAll()
 * @method Series[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    public function countWithTitle($value){
        return $this->createQueryBuilder('s')
            ->select('count(s)')
            ->where('s.title LIKE :value')
            ->setParameter('value','%'.$value.'%')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countSeries(){
        return $this->createQueryBuilder('s')
            ->select('count(s)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findWithTitle($value){
        return $this->createQueryBuilder('s')
            ->where('s.title LIKE :value')
            ->setParameter('value','%'.$value.'%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSeriesFirstPage(){
        return $this->createQueryBuilder('s')
            ->select('s', 'avg(r.value)')
            ->leftJoin('App\Entity\Rating', 'r', 'with', 's.id = r.series')
            ->groupBy('s.id')
            ->setFirstResult(0)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSeriesFirstPageTitle($pageNumber,$value){
        return $this->createQueryBuilder('s')
            ->where('s.title LIKE :value')
            ->setParameter('value','%'.$value.'%')
            ->setFirstResult($pageNumber-1)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;

    }
    
    public function findSeriesTitle($pageNumber,$value){
        return $this->createQueryBuilder('s')
            ->where('s.title LIKE :value')
            ->setParameter('value','%'.$value.'%')
            ->setFirstResult((10*$pageNumber)-10)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSeries($pageNumber){
        return $this->createQueryBuilder('s')
            ->setFirstResult((10*$pageNumber)-10)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function ascSeries(){
        return $this->createQueryBuilder('s')
            ->select('s', 'avg(r.value)')
            ->leftJoin('App\Entity\Rating', 'r', 'with', 's.id = r.series')
            ->groupBy('s.id')
            ->orderBy('avg(r.value)', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function descSeries(){
        return $this->createQueryBuilder('s')
            ->select('s', 'avg(r.value)')
            ->leftJoin('App\Entity\Rating', 'r', 'with', 's.id = r.series')
            ->groupBy('s.id')
            ->orderBy('avg(r.value)', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    // /**
    //  * @return Series[] Returns an array of Series objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Series
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
