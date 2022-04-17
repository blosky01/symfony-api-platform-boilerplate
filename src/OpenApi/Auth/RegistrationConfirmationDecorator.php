<?php

declare(strict_types=1);

namespace App\OpenApi\Auth;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model;

final class RegistrationConfirmationDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {

    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $pathItem = new Model\PathItem(
            ref: 'Verify User Email',
            get: new Model\Operation(
                operationId: 'verifyUserEmailItem',
                tags: ['Auth Verify User Email'],
                responses: [
                    '204' => [
                        'description' => 'Email verified',
                        'content' => [],
                    ],
                    '401' => [
                        'description' => 'Unauthorized',
                        'content' => [],
                    ],
                ],
                summary: 'Verify User Email.',
                parameters: [
                    [
                        'name' => 'expires',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string'
                        ]
                    ],
                    [
                        'name' => 'signature',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string'
                        ]
                    ],
                    [
                        'name' => 'extra',
                        'in' => 'query',
                        'required' => true,
                        'schema' => [
                            'type' => 'string'
                        ]
                    ],
                ],
            ),
        );
        $openApi->getPaths()->addPath('/api/auth/verification_email/verify', $pathItem);

        return $openApi;
    }
}