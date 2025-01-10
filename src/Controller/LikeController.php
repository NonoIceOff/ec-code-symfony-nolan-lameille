<?php

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

    // route to add a like to a book read
    #[Route('/like/{bookReadId}', name: 'app_like', methods: ['POST'])]
    public function like(int $bookReadId): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('auth_login');
        }

        // find the BookRead object by its id
        $bookRead = $this->bookReadRepository->find($bookReadId);

        if (!$bookRead) {
            // return an error if the book is not found
            return new JsonResponse(['status' => 'error', 'message' => 'BookRead not found'], 404);
        }

        // check if the user has already liked this book
        $existingLike = $this->likesRepository->findOneBy([
            'user_id' => $user->getId(),
            'bookread_id' => $bookReadId
        ]);

        if ($existingLike) {
            // return an error if the user has already liked this book
            return new JsonResponse(['status' => 'error', 'message' => 'You have already liked this book'], 400);
        }

        // create a new like
        $newLike = new Likes();
        $newLike->setUserId($user->getId());
        $newLike->setBookreadId($bookReadId);

        // save the like to the database
        $this->entityManager->persist($newLike);
        $this->entityManager->flush();

        // return a success response
        return new JsonResponse(['status' => 'success', 'message' => 'Liked']);
    }
}
