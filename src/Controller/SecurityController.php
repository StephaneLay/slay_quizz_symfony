<?php

namespace App\Controller;

use App\Entity\User;
use App\Events\UserSubscribeEvent;
use App\Form\SubscriptionFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/connect/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/connect/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/connect/subscribe', name: 'subscribe')]
    public function subscribe(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        EventDispatcherInterface $dispatcher
    ): Response {
        $user = new User();
        $signForm = $this->createForm(SubscriptionFormType::class, $user);
        $signForm->handleRequest($request);

        if ($signForm->isSubmitted() && $signForm->isValid()) {
            // On regarde si cet email existe deja
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Un compte avec cet email existe déjà.');
                return $this->redirectToRoute('subscribe');
                
            } else {
                //Classical 
                $hashedPassword = $hasher->hashPassword(
                    $user,
                    $user->getPassword()
                );
                $user->setPassword($hashedPassword);
                $user->setRoles([$signForm->get('role')->getData()]);


                $em->persist($user);
                $em->flush();
                //Petit event pour rentabiliser la fonctionnalité notification => message d'accueil
                $dispatcher->dispatch(new UserSubscribeEvent($user),UserSubscribeEvent::NAME);
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/subscribe.html.twig', [
            'controller_name' => 'HomeController',
            'form' => $signForm->createView()
        ]);
    }
}
