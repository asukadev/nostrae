<?php

namespace App\Security;

// Importation des classes nécessaires
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    // Utilise un trait pour récupérer l’URL précédemment demandée avant la redirection vers login
    use TargetPathTrait;

    // Nom de la route de login utilisée dans le routeur Symfony
    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * Récupère les données soumises depuis le formulaire de connexion
     * et construit un objet Passport pour les transmettre au système de sécurité.
     */
    public function authenticate(Request $request): Passport
    {
        // Récupère les champs du formulaire de connexion
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token');

        // Crée un objet Passport avec les informations d'identification
        return new Passport(
            new UserBadge($username),                      // Identifiant (username/email selon config)
            new PasswordCredentials($password),            // Mot de passe non chiffré
            [
                new CsrfTokenBadge('authenticate', $csrfToken) // Vérification CSRF
            ]
        );
    }

    /**
     * Méthode appelée en cas de succès de l'authentification.
     * Redirige l'utilisateur vers la page d'origine (si connue) ou vers une page par défaut.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirige vers l’URL initialement demandée (si disponible)
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Sinon, redirection vers la page d’accueil (peut être modifié)
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    /**
     * Retourne l’URL de la page de login, utilisée pour les redirections
     * lorsqu’une authentification est nécessaire.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
