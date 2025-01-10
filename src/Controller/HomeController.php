<?php

namespace App\Controller;

use App\Entity\BookRead;
use App\Entity\User;
use App\Form\BookReadType;
use App\Form\BookReadModifyType;
use App\Repository\BookReadRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class HomeController extends AbstractController
{
    private BookReadRepository $bookReadRepository;
    private CategoryRepository $categoryRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(BookReadRepository $bookReadRepository, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager)
    {
        $this->bookReadRepository = $bookReadRepository;
        $this->categoryRepository = $categoryRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app.home')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('auth_login');
        }

        $userId = $user->getId();
        $booksReading = $this->bookReadRepository->findByUserId($userId, false);
        $booksReaded = $this->bookReadRepository->findByUserId($userId, true);

        $categories = $this->categoryRepository->findAll();
        

        // Formulaire d'ajout
        $newBookRead = new BookRead();
        $form = $this->createForm(BookReadType::class, $newBookRead);
        $form->handleRequest($request);

        // validation formulaire d'ajout de bouquins
        if ($form->isSubmitted() && $form->isValid()) {
            $book = $form->get('book')->getData();
            $description = $form->get('description')->getData();
            $isRead = $form->get('is_read')->getData();
            $rating = $form->get('rating')->getData();

            $newBookRead->setUser($user);
            $newBookRead->setBook($book);
            $newBookRead->setDescription($description);
            $newBookRead->setRead($isRead);
            $newBookRead->setRating($rating);
            $newBookRead->setUpdatedAt(new \DateTime());


            $this->entityManager->persist($newBookRead);
            $this->entityManager->flush();

            return $this->redirectToRoute('app.home');
        }

        
        

        

        if (count($booksReading) == 0) {
            $modifyForm = $this->createForm(BookReadModifyType::class, $newBookRead);
            $modifyForm->handleRequest($request);
        }

        // Formulaire de modification
        $modifyFormViews = [];
        foreach ($booksReading as $book) {
            $modifyForm = $this->createForm(BookReadModifyType::class, $book);
            $modifyForm->handleRequest($request);

            if ($modifyForm->isSubmitted() && $modifyForm->isValid()) {
                $book->setUpdatedAt(new \DateTime());

                $this->entityManager->persist($book);
                $this->entityManager->flush();

                return $this->redirectToRoute('app.home');
            }

        }

        return $this->render('pages/home.html.twig', [
            'booksReading' => $booksReading,
            'booksReaded' => $booksReaded,
            "categories" => $categories,
            'name' => 'Accueil',
            'form' => $form->createView(),
            'form_modify' => $modifyForm->createView(),
        ]);
    }
}
