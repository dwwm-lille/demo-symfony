<?php

namespace App\Controller;

use App\Entity\RealEstate;
use App\Form\RealEstateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class RealEstateController extends AbstractController
{
    /**
     * @Route("/nos-biens", name="real_estate_list")
     *
     * La page qui affiche la liste des biens.
     */
    public function index(): Response
    {
        $sizes = [
            1 => 'Studio',
            2 => 'T2',
            3 => 'T3',
            4 => 'T4',
            5 => 'T5',
        ];
        // On appelle le dépôt d'une entité (là où sont stockés les entités)
        $repository = $this->getDoctrine()->getRepository(RealEstate::class);
        // Equivaut à un SELECT * FROM real_estate
        $properties = $repository->findAll();

        return $this->render('real_estate/index.html.twig', [
            'sizes' => $sizes,
            'properties' => $properties,
        ]);
    }

    /**
     * @Route("/nos-biens/{slug}_{id}", name="real_estate_show", requirements={"slug"="[a-z0-9\-]*"})
     *
     * La page qui affiche un bien en détail.
     */
    public function show(RealEstate $property)
    {
        // Avec le @ParamConverter, on n'a pas besoin d'écrire le code suivant
        // Il suffit de typer le pararmètre avec l'entité que l'on souhaite
        // récupèrer

        // On récupère la propriété en BDD
        //$property = $this->getDoctrine()->getRepository(RealEstate::class)
        //    ->find($id);

        // Renvoie une 404 si la propriété n'existe pas
        //if (!$property) {
        //    throw $this->createNotFoundException();
        //}

        return $this->render('real_estate/show.html.twig', [
            'property' => $property,
            'title' => $property->getTitle(),
        ]);
    }

    /**
     * @Route("/creer-un-bien", name="real_estate_create")
     */
    public function create(Request $request, SluggerInterface $slugger): Response
    {
        // Avec Symfony, on peut créer un formulaire
        // Le formulaire est toujours dans une classe à part
        // Dans la plupart des cas, on passe une entité à un formulaire
        $realEstate = new RealEstate(); // use App\Entity\RealEstate;
        $form = $this->createForm(RealEstateType::class, $realEstate);

        // Il faut lié le formulaire à la requête (pour récupèrer $_POST)
        $form->handleRequest($request);

        // On doit vérifier que le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Ici, on va ajouter l'annonce dans la base...
            // $form->getData() permet de récupérer les données d'un formulaire
            // $realEstate est la même chose que $form->getData()
            dump($realEstate);
            // On génére le slug et on fait l'upload avant l'ajout en base
            $slug = $slugger->slug($realEstate->getTitle())->lower(); // Le nom de l'annonce devient le-nom-de-l-annonce
            $realEstate->setSlug($slug);

            // On fait l'upload. Comment récupérer l'image ?
            // Equivalent du $_FILES['image']
            /** @var UploadedFile $image */
            $image = $form->get('image')->getData(); // On récupère la valeur du champ
            if ($image) { // Si on upload une image dans l'annnonce
                $fileName = uniqid() . '.' . $image->guessExtension();
                $image->move($this->getParameter('upload_directory'), $fileName);
                $realEstate->setImage($fileName);
            } else {
                // On mets une image par défaut si on upload pas
                $realEstate->setImage('default.png');
            }
            // dd($image); // dump & die

            // Je dois ajouter l'objet dans la BDD
            $entityManager = $this->getDoctrine()->getManager();
            // Je dois mettre l'objet "en attente"
            $entityManager->persist($realEstate);
            // Exécuter la requête
            $entityManager->flush();

            // Faire une redirection après l'ajout et affiche
            // un message de succès
            $this->addFlash('success', 'Votre annonce '.$realEstate->getId().' a bien été ajoutée');

            /*+
                Le tableau des messages ressemble à cela
                [
                    'success' => ['A', 'B', 'C'],
                    'danger' => ['D', 'E'],
                ]
            */

            // Faire la redirection vers la liste des annonces et afficher les messages flashs sur le html
            return $this->redirectToRoute('real_estate_list');
        }

        return $this->render('real_estate/create.html.twig', [
            // Permet d'afficher le formulaire
            'realEstateForm' => $form->createView(),
        ]);
    }
}
