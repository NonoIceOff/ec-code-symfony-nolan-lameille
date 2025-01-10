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

class ExplorerController extends AbstractController
{
    private BookReadRepository $bookReadRepository;
    private EntityManagerInterface $entityManager;
    private CategoryRepository $categoryRepository;
    private LikesRepository $likesRepository;

    public function __construct(BookReadRepository $bookReadRepository, LikesRepository $likesRepository, CategoryRepository $categoryRepository, EntityManagerInterface $entityManager)
    {
        $this->bookReadRepository = $bookReadRepository;
        $this->categoryRepository = $categoryRepository;
        $this->likesRepository = $likesRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/explorer', name: 'app_explorer')]
    public function index(): Response
    {

        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('auth_login');
        }

        $booksRead = $this->bookReadRepository->findAll();
        $categories = $this->categoryRepository->findAll();

        $booksLikes = [];
        foreach ($booksRead as $bookRead) {
            // Récupérer le nombre de likes pour chaque BookRead
            $likes = $this->likesRepository->findBy([
                'bookread_id' => $bookRead->getId()
            ]);
            
            $likeCount = count($likes); 
            
            
            $booksLikes[] = [
                'bookRead' => $bookRead,
                'likeCount' => $likeCount
            ];
            
        }

        return $this->render('explorer/index.html.twig', [
            'controller_name' => 'ExplorerController',
            "categories" => $categories,
            'booksLikes' => $booksLikes,
        ]);
    }
}
