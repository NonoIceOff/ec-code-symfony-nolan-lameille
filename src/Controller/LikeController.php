<?php

// src/Controller/LikeController.php
namespace App\Controller;

use App\Entity\BookRead;
use App\Entity\Likes;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BookReadRepository;
use App\Repository\LikesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class LikeController extends AbstractController
{
    private BookReadRepository $bookReadRepository;
    private LikesRepository $likesRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(BookReadRepository $bookReadRepository, LikesRepository $likesRepository, EntityManagerInterface $entityManager)
    {
        $this->bookReadRepository = $bookReadRepository;
        $this->likesRepository = $likesRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/like/{bookReadId}', name: 'app_like', methods: ['POST'])]
    public function like(int $bookReadId): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('auth_login');
        }

        // Trouver l'objet BookRead par ID
        $bookRead = $this->bookReadRepository->find($bookReadId);

        if (!$bookRead) {
            return new JsonResponse(['status' => 'error', 'message' => 'BookRead pas trouvé'], 404);
        }

        $existingLike = $this->likesRepository->findOneBy([
            'user_id' => $user->getId(),
            'bookread_id' => $bookReadId
        ]);

        if ($existingLike) {
            return new JsonResponse(['status' => 'error', 'message' => 'Vous avez déjà liké ce livre'], 400);
        }

        // Créer un nouveau like
        $newLike = new Likes();
        $newLike->setUserId($user->getId());
        $newLike->setBookreadId($bookReadId);

        // Sauvegarder dans la base de données
        $this->entityManager->persist($newLike);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'success', 'message' => 'Liké']);
    }
}
