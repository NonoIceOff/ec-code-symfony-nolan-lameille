<?php

namespace App\Controller;

use App\Entity\Commentaries;
use App\Entity\User;
use App\Form\AddCommentaryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\BookReadService;

class AddCommentaryController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BookReadService $bookReadService;

    public function __construct(BookReadService $bookReadService, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->bookReadService = $bookReadService;
    }

    // route to add a commentary to a book read
    #[Route('/add/commentary/{bookReadId}', name: 'app_add_commentary')]
    public function index(int $bookReadId, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('auth_login');
        }

        // create a new commentary
        $newCommentary = new Commentaries();
        $form = $this->createForm(AddCommentaryType::class, $newCommentary);
        $form->handleRequest($request);

        // check if the form has been submitted and is valid
        if ($form->isSubmitted() && $form->isValid()) {
            // associate the user and the book with the commentary
            $newCommentary->setUserId($user->getId());
            $newCommentary->setBookreadId($bookReadId);

            // persist the commentary in the database
            $this->entityManager->persist($newCommentary);
            $this->entityManager->flush();

            // redirect to the comments page for this book
            return $this->redirectToRoute('app_commentaries', [
                'bookReadId' => $bookReadId
            ]);
        }

        // get all rated books for searches
        $allBooksRead = $this->bookReadService->getAllBooksReadWithAverage();

        return $this->render('pages/add_commentary.html.twig', [
            'controller_name' => 'AddCommentaryController',
            'form' => $form->createView(),
            'allbooksRead' => $allBooksRead,
        ]);
    }
}
