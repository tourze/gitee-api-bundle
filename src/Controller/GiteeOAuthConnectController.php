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

#[Route('/gitee/oauth/connect/{applicationId}', name: 'gitee_oauth_connect')]
class GiteeOAuthConnectController extends AbstractController
{
    public function __construct(
        private readonly GiteeOAuthService $oauthService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request, GiteeApplication $application): Response
    {
        $callbackUrlParam = $request->query->get('callbackUrl');
        $callbackUrl = is_string($callbackUrlParam) ? $callbackUrlParam : null;

        $authUrl = $this->oauthService->getAuthorizationUrl(
            $application,
            $this->urlGenerator->generate('gitee_oauth_callback', [
                'applicationId' => $application->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            $callbackUrl
        );

        return new RedirectResponse($authUrl);
    }
}