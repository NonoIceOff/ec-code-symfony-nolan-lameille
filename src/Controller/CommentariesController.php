<?php

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
use App\Service\BookReadService;

class CommentariesController extends AbstractController
{
    private BookReadRepository $bookReadRepository;
    private CommentariesRepository $commentariesRepository;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private BookReadService $bookReadService;

    public function __construct(
        CommentariesRepository $commentariesRepository,
        BookReadRepository $bookReadRepository,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        BookReadService $bookReadService,
    ) {
        $this->bookReadRepository = $bookReadRepository;
        $this->commentariesRepository = $commentariesRepository;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->bookReadService = $bookReadService;
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
            throw $this->createNotFoundException('The book does not exist');
        }

        $commentaries = $this->commentariesRepository->findBy([
            'bookread_id' => $bookReadId
        ]);

        foreach ($commentaries as $commentary) {
            $user = $this->userRepository->find($commentary->getUserId());
            if ($user) {
                $commentary->userEmail = $user->getEmail();
            } else {
                $commentary->userEmail = 'User not found';
            }
        }

        // get all rated books for searches
        $allBooksRead = $this->bookReadService->getAllBooksReadWithAverage();


        return $this->render('pages/commentaries.html.twig', [
            'controller_name' => 'CommentariesController',
            'bookread' => $bookRead,
            'allbooksRead' => $allBooksRead,
            'commentaries' => $commentaries,
        ]);
    }
}
