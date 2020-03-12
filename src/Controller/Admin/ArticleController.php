<?php


namespace App\Controller\Admin;


use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ArticleController
 * @package App\Controller\Admin
 *
 * @Route("/article")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(ArticleRepository $repository)
    {
        $articles = $repository->findBy([], ['publicationDate' => 'DESC']);

        return $this->render(
            'admin/article/index.html.twig',
            ['articles' => $articles]
        );
    }

    /**
     * @Route("/edition/{id}", defaults={"id": null}, requirements={"id": "\d+"})
     */
    public function edit(Request $request, EntityManagerInterface $manager, $id)
    {
        /*
         * Intégrer le formulaire pour l'enregistrement d'un article
         * Validation : tous les champs obligatoires
         * Avant l'enregistrement setter la date de publication à maintenant
         * et l'auteur avec l'utilisateur connecté ($this->getUser() dans un contrôleur)
         *
         * Adapter la page pour la modification :
         * - pas de modification de la date de publication ni de l'auteur
         */
        $originalImage = null;

        if (is_null($id)) { // création
            $article = new Article();
            $article->setAuthor($this->getUser());
        } else { // modification
            $article = $manager->find(Article::class, $id);

            if (is_null($article)) {
                throw new NotFoundHttpException();
            }


            if (!is_null($article->getImage())) {
                // nom du fichier venant de la bdd
                $originalImage = $article->getImage();

                // pour le champ de formulaire qui attend un objet File
                $article->setImage(
                    new File($this->getParameter('upload_dir') . $article->getImage())
                );
            }
        }

        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                /** @var UploadedFile|null $image */
                $image = $article->getImage();

                // s'il y a eu une image uploadée
                if (!is_null($image)) {
                    // nom sous lequel on va enregistrer l'image
                    $filename = uniqid() . '.' .$image->guessExtension();

                    // déplace l'image uploadée
                    $image->move(
                        // dans quel répertoire
                        // cf config/services.yaml
                        $this->getParameter('upload_dir'),
                        // avec quel nom
                        $filename
                    );

                    // pour enregistrer le nom du fichier en bdd
                    $article->setImage($filename);

                    // suppression de l'ancienne image
                    // s'il y en a une
                    if (!is_null($originalImage)) {
                        unlink($this->getParameter('upload_dir') . $originalImage);
                    }
                } else {
                    // pour la modification, sans upload,
                    // on remet le nom de l'image venant de la bdd
                    $article->setImage($originalImage);
                }

                $manager->persist($article);
                $manager->flush();

                $this->addFlash(
                    'success',
                    "L'article est enregistré"
                );

                return $this->redirectToRoute('app_admin_article_index');
            } else {
                $this->addFlash(
                    'error',
                    'Le formulaire contient des erreurs'
                );
            }
        }

        return $this->render(
            'admin/article/edit.html.twig',
            [
                'form' => $form->createView(),
                'original_image' => $originalImage
            ]
        );
    }

    /**
     * @param EntityManagerInterface $manager
     * @param Article $article
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/suppression/{id}")
     */
    public function delete(EntityManagerInterface $manager, Article $article)
    {
        if (!is_null($article->getImage())) {
            $image = $this->getParameter('upload_dir') . $article->getImage();

            if (file_exists($image)) {
                unlink($image);
            }
        }

        // suppression en bdd
        $manager->remove($article);
        $manager->flush();

        $this->addFlash('success', "L'article est supprimé");

        return $this->redirectToRoute('app_admin_article_index');
    }
}
