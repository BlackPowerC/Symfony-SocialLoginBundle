<?php

namespace JFK\SocialLogin\Google\Security;

use Symfony\Component\HttpFoundation\Request;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Guard authenticator for Google Social Login.
 * 
 * @author Jordy <jordy.fatigba@semoa-togo.com>
 */
class GoogleAuthenticator extends SocialAuthenticator
{
    private $router ;
    private $clientRegistry ;
    private $userManager ;

    public function __construct(SessionInterface $session, ClientRegistry $registry, UserManagerInterface $userManager, RouterInterface $router)
    {
        $this->router = $router ;
        $this->userManager = $userManager ;
        $this->clientRegistry = $registry ;
        $this->session = $session ;
    }

    public function supports(Request $request) {
        return $request->attributes->get('_route') === 'connect_google_check' && $request->isMethod("GET") ;
    }

    public function getCredentials(Request $request) {
        return $this->fetchAccessToken($this->getGoogleClient());
    }

    /**
     * Cette fonction va utiliser l'email de l'utilisateur Google pour vérifier
     * si un Utilisateur de l'app existe avec le même email ou pas.
     *
     * @param $credentials Les crédentials fournis par Google
     * @param UserProviderInterface $userProvider
     * @return UserInterface|null null si aucun utilisateur de l'app ne correspond à l'email de l'utilisateur Google.
     * @throws AuthenticationException Si l'utilisateur n'a pas de compte ou si son compte est inactif.
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $googleUser = $this->getGoogleClient()->fetchUserFromToken($credentials);

        $email = $googleUser->getEmail();

        $existingUser = $this->userManager->findUserByEmail($email) ;
                
        if($existingUser)
        {
            if(!$existingUser->isEnabled()) {
                throw new AuthenticationException("Votre compte d'utilisateur est inactif. Veuillez contacter l'administrateur du site.") ;
            }
            return $existingUser ;
        }
        
        throw new AuthenticationException("Aucun utilisateur ne correspond à votre compte Google") ;
    }

    /**
     * @return OAuth2ClientInterface
     */
    private function getGoogleClient()
    {
        return $this->clientRegistry
            // "google" is the key used in config/packages/knpu_oauth2_client.yaml
            ->getClient('google');
	}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetUrl = $this->router->generate('homepage');
        
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->getFlashBag()->add("error", $exception->getMessage()) ;

        return new RedirectResponse("/login", 302, ["referer" => "/login"]) ;
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null) {
        // return $this->clientRegistry->getClient("google")->redirect() ;
        return new RedirectResponse("/login", 302, ["referer" => "/login"]) ;

    }
}