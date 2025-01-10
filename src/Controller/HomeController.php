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

        // Logique pour regrouper les lectures par livre et calculer les moyennes
        $allBooksReadRaw = $this->bookReadRepository->findAll();
        $allBooksRead = [];

        foreach ($allBooksReadRaw as $bookRead) {
            $bookId = $bookRead->getBook()->getId();
            $bookName = $bookRead->getBook()->getName();
            $bookDescription = $bookRead->getBook()->getDescription();
            $rating = $bookRead->getRating();

            if (!isset($allBooksRead[$bookId])) {
                $allBooksRead[$bookId] = [
                    'book_id' => $bookId,
                    'book_name' => $bookName,
                    'book_description' => $bookDescription,
                    'total_rating' => 0,
                    'rating_count' => 0,
                    'average_rating' => 0,
                ];
            }

            $allBooksRead[$bookId]['total_rating'] += $rating;
            $allBooksRead[$bookId]['rating_count']++;
            $allBooksRead[$bookId]['average_rating'] = $allBooksRead[$bookId]['total_rating'] / $allBooksRead[$bookId]['rating_count'];
        }

        // Transformer en tableau indexé pour Twig
        $allBooksRead = array_values($allBooksRead);

        // Formulaire d'ajout
        $newBookRead = new BookRead();
        $form = $this->createForm(BookReadType::class, $newBookRead);
        $form->handleRequest($request);

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
            'allbooksRead' => $allBooksRead,
            "categories" => $categories,
            'name' => 'Accueil',
            'form' => $form->createView(),
            'form_modify' => $modifyForm->createView(),
        ]);
    }
}
