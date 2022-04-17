<?php

declare(strict_types=1);

namespace App\OpenApi\Auth;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model;

final class RefreshJwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {

    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $pathItem = new Model\PathItem(
            ref: 'Refresh JWT Token',
            post: new Model\Operation(
                operationId: 'refreshCredentialsItem',
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
                summary: 'Refresh JWT token.'
            ),
        );
        $openApi->getPaths()->addPath('/api/auth/refresh_token', $pathItem);

        return $openApi;
    }
}