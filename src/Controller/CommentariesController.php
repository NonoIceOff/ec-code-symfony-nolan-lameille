<?php

// src/Controller/CommentariesController.php
namespace App\Controller;

use App\Entity\BookRead;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BookReadRepository;
use App\Repository\CommentariesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;

class CommentariesController extends AbstractController
{
    private BookReadRepository $bookReadRepository;
    private CommentariesRepository $commentariesRepository;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(
        CommentariesRepository $commentariesRepository,
        BookReadRepository $bookReadRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository // Ajouter le UserRepository ici
    ) {
        $this->bookReadRepository = $bookReadRepository;
        $this->commentariesRepository = $commentariesRepository;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/commentaries/{bookReadId}', name: 'app_commentaries')]
    public function index(int $bookReadId): Response
    {

        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('auth_login');
        }

        $bookRead = $this->bookReadRepository->find($bookReadId);

        if (!$bookRead) {
            throw $this->createNotFoundException('Le livre n\'existe pas');
        }

        $commentaries = $this->commentariesRepository->findBy([
            'bookread_id' => $bookReadId
        ]);

        foreach ($commentaries as $commentary) {
            $user = $this->userRepository->find($commentary->getUserId());
            if ($user) {
                $commentary->userEmail = $user->getEmail();
            } else {
                $commentary->userEmail = 'Utilisateur non trouvé';
            }
        }

        return $this->render('commentaries/index.html.twig', [
            'controller_name' => 'CommentariesController',
            'bookread' => $bookRead,
            'commentaries' => $commentaries,
        ]);
    }
}
