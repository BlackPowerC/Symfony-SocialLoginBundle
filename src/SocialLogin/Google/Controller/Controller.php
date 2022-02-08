<?php

namespace JFK\SocialLogin\Google\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author Jordy <jordy.fatigba@semoa-togo.com>
 */
class Controller extends AbstractController
{
    /**
     * @Route("/sociallogin/google", name="connect_google")
     *
     * @param ClientRegistry $clientRegistry
     * @return RedirectResponse
     */
    public function connectAction(ClientRegistry $clientRegistry) {
        return $clientRegistry->getClient("google")->redirect() ;
    }

    /**
     * @Route("/sociallogin/google/check", name="connect_google_check")
     *
     * @param TokenStorageInterface $tokenStorage
     * @param SessionAuthenticationStrategyInterface $sessionStrategy
     * @param AuthenticationManagerInterface $authManager
     * @param Request $request
     * 
     * @return RedirectResponse
     */
    public function connectCheckAction(
        TokenStorageInterface $tokenStorage,
        SessionAuthenticationStrategyInterface $sessionStrategy,
        AuthenticationManagerInterface $authManager,
        Request $request)
    {
        $user = $this->getUser() ;

        if(!$this->getUser())
        {
            $this->addFlash(
               "error",
               "Aucun utilisateur ne correspond à votre compte Google"
            );
            return $this->redirect("/login") ;
        }
        else
        {
            $token = new UsernamePasswordToken($user, $user->getPassword(), "google_social_login", $user->getRoles()) ;
            $session = $request->getSession();
            if (!$request->hasPreviousSession())
            {
                $request->setSession($session);
                $request->getSession()->start();
                $request->cookies->set($request->getSession()->getName(), $request->getSession()->getId());
            }
            
            $email = $user->getEmail() ;
            $session->set(Security::LAST_USERNAME, $email);
            
            // Authenticate user
            $authManager->authenticate($token);
            $sessionStrategy->onAuthentication($request, $token);

            // For older versions of Symfony, use "security.context" here
            $tokenStorage->setToken($token);
            $session->set('_security_main', serialize($token));

            $session->remove(Security::AUTHENTICATION_ERROR);
            $session->remove(Security::LAST_USERNAME);

            // Fire the login event
            $event = new InteractiveLoginEvent($request, $token);
            $this->get('event_dispatcher')->dispatch($event, SecurityEvents::INTERACTIVE_LOGIN);

            return $this->redirectToRoute("homepage") ;
        }
    }
}