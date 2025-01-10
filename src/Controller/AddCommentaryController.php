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

class AddCommentaryController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/add/commentary/{bookReadId}', name: 'app_add_commentary')]
    public function index(int $bookReadId, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('auth_login');
        }

        $newCommentary = new Commentaries();
        $form = $this->createForm(AddCommentaryType::class, $newCommentary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newCommentary->setUserId($user->getId());
            $newCommentary->setBookreadId($bookReadId);


            $this->entityManager->persist($newCommentary);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_commentaries', [
                'bookReadId' => $bookReadId
            ]);
        }

        return $this->render('add_commentary/index.html.twig', [
            'controller_name' => 'AddCommentaryController',
            'form' => $form->createView(),
        ]);
    }
}
