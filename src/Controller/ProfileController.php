<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createFormBuilder(null)
        ->add('oldPassword', PasswordType::class, [
            'label' => "Senha atual",
        ])
        ->add('newPassword',
            RepeatedType::class,
            [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Nova senha'],
                'second_options' => ['label' => 'Repetir nova senha'],
            ])
            ->add('Salvar', SubmitType::class)
            ->getForm();

        $form->handleRequest(request: $request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $data = $form->getData();
            if (!$passwordHasher->isPasswordValid($user, $data["oldPassword"])) {
                $this->addFlash("danger", "Senha atual incorreta.");
            } else {
                $hashedPassword = $passwordHasher->hashPassword($user, $data["newPassword"]);
                $user->setpassword($hashedPassword);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash("success", "Senha alterada com sucesso.");
            }
        }
        
        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'form' => $form
        ]);
    }
}
