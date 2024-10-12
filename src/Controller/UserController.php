<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/registration', name: 'user_registration')]
    public function index(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $user->setUsername("root");
        $hashedPassword = $passwordHasher->hashPassword($user, "root");
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setRoles(["ROLE_ADMIN"]);

        $entityManager->persist($user);

        $entityManager->flush();

        return $this->render('user/index.html.twig', [
            'controller_name' =>  $user->getUsername(),
        ]);
    }
}
