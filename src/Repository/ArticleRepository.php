<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function search(array $filters = [])
    {
        // constructeur de requête
        // le "a" est l'alias de l'entité Article
        $qb = $this->createQueryBuilder('a');

        // tri sur la date de publication
        $qb->orderBy('a.publicationDate', 'DESC');

        if (!empty($filters['title'])) {
            $qb
                // ajoute un élément à la clause WHERE
                ->andWhere('a.title like :title')
                // bindValue du marqueur :title
                ->setParameter('title', '%' . $filters['title'] . '%')
            ;
        }

        if (!empty($filters['category'])) {
            $qb
                ->andWhere('a.category = :category')
                ->setParameter('category', $filters['category'])
            ;
        }

        if (!empty($filters['start_date'])) {
            $qb
                ->andWhere('a.publicationDate >= :start_date')
                ->setParameter('start_date', $filters['start_date'])
            ;
        }

        if (!empty($filters['end_date'])) {
            $qb
                ->andWhere('a.publicationDate <= :end_date')
                ->setParameter('end_date', $filters['end_date'])
            ;
        }

        // objet Query Généré
        $query = $qb->getQuery();

        // la requête SQL
        dump($query->getSQL());

        // retourne un tableau d'objets Article
        return $query->getResult();
    }
}
