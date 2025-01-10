<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategoryRepository;
use App\Repository\LikesRepository;
use App\Repository\BookReadRepository;
use App\Repository\CommentariesRepository;
use App\Service\BookReadService;

class ExplorerController extends AbstractController
{
    private BookReadRepository $bookReadRepository;
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;
    private LikesRepository $likesRepository;
    private CommentariesRepository $commentsRepository;
    private BookReadService $bookReadService;

    public function __construct(CommentariesRepository $commentsRepository, BookReadService $bookReadService, BookReadRepository $bookReadRepository, LikesRepository $likesRepository, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager)
    {
        $this->bookReadRepository = $bookReadRepository;
        $this->categoryRepository = $categoryRepository;
        $this->likesRepository = $likesRepository;
        $this->entityManager = $entityManager;
        $this->bookReadService = $bookReadService;
        $this->commentsRepository = $commentsRepository;
    }

    // route to explore read books
    #[Route('/explorer', name: 'app_explorer')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('auth_login');
        }

        // fetch all read books and all categories
        $booksRead = $this->bookReadRepository->findAll();
        $categories = $this->categoryRepository->findAll();

        // initialize an array to store books with their likes and comments
        $booksLikes = [];
        foreach ($booksRead as $bookRead) {
            // fetch likes for the book read
            $likes = $this->likesRepository->findBy([
                'bookread_id' => $bookRead->getId()
            ]);

            // fetch comments for the book read
            $comments = $this->commentsRepository->findBy([
                'bookread_id' => $bookRead->getId()
            ]);
            
            // calculate the number of likes and comments
            $likeCount = count($likes); 
            $commentsCount = count($comments); 
            
            // add the book, likes, and comments to the array
            $booksLikes[] = [
                'bookRead' => $bookRead,
                'likeCount' => $likeCount,
                'commentsCount' => $commentsCount
            ];
        }

        // get all rated books for searches
        $allBooksRead = $this->bookReadService->getAllBooksReadWithAverage();

        return $this->render('pages/explorer.html.twig', [
            'controller_name' => 'ExplorerController',
            "categories" => $categories, 
            'allbooksRead' => $allBooksRead, 
            'booksLikes' => $booksLikes, 
        ]);
    }
}
