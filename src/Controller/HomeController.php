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
use App\Service\BookReadService;

class HomeController extends AbstractController
{
    private BookReadRepository $bookReadRepository;
    private CategoryRepository $categoryRepository;
    private EntityManagerInterface $entityManager;
    private BookReadService $bookReadService;

    // constructor to initialize dependencies
    public function __construct(BookReadService $bookReadService, BookReadRepository $bookReadRepository, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager)
    {
        $this->bookReadRepository = $bookReadRepository;
        $this->categoryRepository = $categoryRepository;
        $this->entityManager = $entityManager;
        $this->bookReadService = $bookReadService;
    }

    // route to fetch radar chart data
    #[Route('/radar-data', name: 'app.radar_data', methods: ['GET'])]
    public function getRadarData(): JsonResponse
    {
        // fetch the logged-in user
        $user = $this->getUser();

        if (!$user instanceof User) {
            // redirect to login page if the user is not authenticated
            return $this->redirectToRoute('auth_login');
        }

        // fetch all books read by the user
        $allBooksRead = $this->bookReadRepository->findBy(['user_id' => $user->getId()]);

        // group books by category
        $categories = [];
        foreach ($allBooksRead as $bookRead) {
            $categoryId = $bookRead->getBook()->getCategoryId();
            $categoryName = $this->categoryRepository->findOneBy(['id' => $categoryId])->getName();

            if (!isset($categories[$categoryName])) {
                $categories[$categoryName] = 0;
            }

            $categories[$categoryName]++;
        }

        // format data for the frontend
        $data = [
            'categories' => array_keys($categories),
            'values' => array_values($categories),
        ];

        return new JsonResponse($data); // returns a json response with formatted data
    }

    // main route to display the home page
    #[Route('/', name: 'app.home')]
    public function index(Request $request): Response
    {
        // fetch the logged-in user
        $user = $this->getUser();

        if (!$user instanceof User) {
            // redirect to login page if the user is not authenticated
            return $this->redirectToRoute('auth_login');
        }

        // fetch books currently being read and books already read
        $userId = $user->getId();
        $booksReading = $this->bookReadRepository->findByUserId($userId, false);
        $booksReaded = $this->bookReadRepository->findByUserId($userId, true);

        // fetch all categories
        $categories = $this->categoryRepository->findAll();

        // create the form to add a book read
        $newBookRead = new BookRead();
        $form = $this->createForm(BookReadType::class, $newBookRead);
        $form->handleRequest($request);

        // check if the form has been submitted and is valid
        if ($form->isSubmitted() && $form->isValid()) {
            $book = $form->get('book')->getData();
            $description = $form->get('description')->getData();
            $isRead = $form->get('is_read')->getData();
            $rating = $form->get('rating')->getData();

            // configure the bookread entity with the submitted data
            $newBookRead->setUser($user);
            $newBookRead->setBook($book);
            $newBookRead->setDescription($description);
            $newBookRead->setRead($isRead);
            $newBookRead->setRating($rating);
            $newBookRead->setUpdatedAt(new \DateTime());

            // persist the data into the database
            $this->entityManager->persist($newBookRead);
            $this->entityManager->flush();

            // redirect to the home page after submission
            return $this->redirectToRoute('app.home');
        }

        // handle the modification form for books currently being read
        $modifyFormViews = [];
        foreach ($booksReading as $book) {
            $modifyForm = $this->createForm(BookReadModifyType::class, $book);
            $modifyForm->handleRequest($request);

            if ($modifyForm->isSubmitted() && $modifyForm->isValid()) {
                $book->setUpdatedAt(new \DateTime());

                // persist the modifications into the database
                $this->entityManager->persist($book);
                $this->entityManager->flush();

                // redirect to the home page after modification
                return $this->redirectToRoute('app.home');
            }
        }

        // fetch all rated books for searches
        $allBooksRead = $this->bookReadService->getAllBooksReadWithAverage();

        // pass variables to twig (frontend part)
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
