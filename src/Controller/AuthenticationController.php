<?php

namespace App\Controller;

use App\Entity\Session;
use DateTimeImmutable;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthenticationController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser())
            return $this->redirectToRoute("app_user_search");

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('authentication/login.html.twig', [
            'controller_name' => 'AuthenticationController',
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
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
