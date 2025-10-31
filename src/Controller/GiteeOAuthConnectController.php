<?php

declare(strict_types=1);

namespace GiteeApiBundle\Controller;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Service\GiteeOAuthService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Autoconfigure(public: true)]
final class GiteeOAuthConnectController extends AbstractController
{
    public function __construct(
        private readonly GiteeOAuthService $oauthService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(path: '/gitee/oauth/connect/{applicationId}', name: 'gitee_oauth_connect', methods: ['GET', 'HEAD'])]
    public function __invoke(
        Request $request,
        #[MapEntity(id: 'applicationId')] GiteeApplication $application,
    ): Response {
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
