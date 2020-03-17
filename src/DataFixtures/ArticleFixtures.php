<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Xvladqt\Faker\LoremFlickrProvider;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        // pour charger les images avec lorem flicker
        $faker->addProvider(new LoremFlickrProvider($faker));
        // paramètre upload_dir de config/services.yaml
        $imageDir = $this->parameterBag->get('upload_dir');

        for ($i = 0; $i < 50; $i++) {
            $article = new Article();
            // une catégorie en random
            $category = $this->getReference(
                CategoryFixtures::getRandom()
            );

            $article
                // phrase en lorem de 10 mots max
                ->setTitle($faker->sentence(10, true))
                // entre 2 et 10 paragraphes
                ->setContent(implode(PHP_EOL, $faker->paragraphs(rand(2, 10))))
                ->setPublicationDate($faker->dateTimeThisDecade())
                ->setAuthor($this->getReference('admin'))
                ->setCategory($category)
                // 80% d'articles avec image, catégorie computer (le nom du fichier et pas le chemin complet en bdd)
                ->setImage($faker->optional(0.8)->image($imageDir, 800, 300, ['computer'], false))
            ;

            for ($j = 0; $j <= rand(0, 20); $j++) {
                $comment = new Comment();

                $comment
                    ->setArticle($article)
                    ->setUser($this->getReference('user' . rand(1, 10)))
                    // entre la date de publication de l'article et maintenant
                    ->setPublicationDate($faker->dateTimeBetween($article->getPublicationDate()))
                    ->setContent(implode(PHP_EOL, $faker->sentences))
                ;

                $manager->persist($comment);
            }

            $manager->persist($article);
        }

        $manager->flush();
    }

    /**
     * Pour que ces fixtures soient chargées après catégorie et user
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class
        ];
    }
}
