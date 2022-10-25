<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;


/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function add(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

public function findMore($minPrice, $maxPrice, $Cat,$word,$sortBy,$orderBy): Query
{
$entityManager = $this->getEntityManager();


          
//'SELECT p from App\Entity\Product p
//where p.Category=:Cat AND p.Price >= :minP AND p.Price <= :maxP
//order by p.Price ASC')
//->setParameter('minP', $minPrice)
//->setParameter('maxP', $maxPrice)
//->setParameter('Cat', $Cat);
    $qb = $entityManager->createQueryBuilder();
    $qb->select('p')
    ->from('App:Product','p');
    if(is_null($minPrice)|| empty($minPrice))  {
        $minPrice=0;
    }
    if((is_null($orderBy)|| empty($orderBy))){
        $qb->addOrderBy('p.Price', 'ASC'); 
    }
    if (($orderBy=='DESC')){
        $qb->addOrderBy('p.Price', 'DESC'); 
    }
    if (($orderBy=='ASC')){
        $qb->addOrderBy('p.Price', 'ASC'); 
    }
    $qb->where('p.Price>='.$minPrice);
    if(!(is_null($maxPrice)|| empty($maxPrice)))  {
        $qb->andWhere('p.Price<='.$maxPrice);
    }
    if(!(is_null($Cat)|| empty($Cat)))  {
            $qb->andWhere('p.Category='.$Cat);
    }
    if(!(is_null($word)|| empty($word))){
        $qb -> andWhere('p.Name like :word') ->setParameter('word','%'.$word.'%');
    }
    
    
    return $qb->getQuery();
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

}

