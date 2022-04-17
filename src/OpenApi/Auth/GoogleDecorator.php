<?php

declare(strict_types=1);

namespace App\OpenApi\Auth;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model;

final class GoogleDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {

    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $pathItem = new Model\PathItem(
            ref: 'Google oauth',
            post: new Model\Operation(
                operationId: 'googleOauthItem',
                tags: ['Auth'],
                responses: [
                    '204' => [
                        'description' => 'Refreshed JWT token'
                    ],
                    '401' => [
                        'description' => 'Unauthorized',
                        'content' => [],
                    ]
                ],
                summary: 'Google oauth.'
            ),
        );
        $openApi->getPaths()->addPath('/api/auth/connect/google', $pathItem);

        return $openApi;
    }
}