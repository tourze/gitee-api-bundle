<?php

namespace GiteeApiBundle\Controller;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Service\GiteeOAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/gitee/oauth')]
class OAuthController extends AbstractController
{
    public function __construct(
        private readonly GiteeOAuthService $oauthService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/connect/{applicationId}', name: 'gitee_oauth_connect')]
    public function connect(Request $request, GiteeApplication $application): Response
    {
        $callbackUrl = $request->query->get('callbackUrl');

        $authUrl = $this->oauthService->getAuthorizationUrl(
            $application,
            $this->urlGenerator->generate('gitee_oauth_callback', [
                'applicationId' => $application->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            $callbackUrl
        );

        return new RedirectResponse($authUrl);
    }

    #[Route('/callback/{applicationId}', name: 'gitee_oauth_callback')]
    public function callback(Request $request, GiteeApplication $application): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            throw $this->createNotFoundException('No authorization code provided');
        }

        $state = $request->query->get('state');
        $callbackUrl = $state ? $this->oauthService->verifyState($state) : null;

        $token = $this->oauthService->handleCallback(
            $code,
            $application,
            $this->urlGenerator->generate('gitee_oauth_callback', [
                'applicationId' => $application->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        if ($callbackUrl !== null) {
            // Replace template variables in callback URL
            $callbackUrl = strtr($callbackUrl, [
                '{accessToken}' => $token->getAccessToken(),
                '{userId}' => $token->getUserId(),
                '{giteeUsername}' => $token->getGiteeUsername(),
                '{applicationId}' => $application->getId(),
            ]);

            return new RedirectResponse($callbackUrl);
        }

        return $this->redirectToRoute('homepage');
    }
}
