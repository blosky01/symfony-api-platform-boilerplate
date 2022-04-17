<?php

declare(strict_types=1);

namespace App\OpenApi\Auth;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model;

final class ResendVerificationEmailDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {

    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $pathItem = new Model\PathItem(
            ref: 'Resend verification email',
            post: new Model\Operation(
                operationId: 'resendVerificationEmailItem',
                tags: ['Auth Verify User Email'],
                responses: [
                    '200' => [
                        'description' => 'Resended verification email'
                    ],
                    '401' => [
                        'description' => 'Unauthorized',
                        'content' => [],
                    ]
                ],
                summary: 'Resend verification email.'
            ),
        );
        $openApi->getPaths()->addPath('/api/auth/verification_email/resend', $pathItem);

        return $openApi;
    }
}