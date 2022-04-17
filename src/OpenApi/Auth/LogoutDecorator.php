<?php

declare(strict_types=1);

namespace App\OpenApi\Auth;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model;

final class LogoutDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $pathItem = new Model\PathItem(
            ref: 'Logout',
            post: new Model\Operation(
                operationId: 'logoutItem',
                tags: ['Auth'],
                responses: [
                    '200' => [
                        'description' => 'Logout Successfully'
                    ],
                ],
                summary: 'Logout.'
            ),
        );
        $openApi->getPaths()->addPath('/api/auth/logout', $pathItem);

        return $openApi;
    }
}