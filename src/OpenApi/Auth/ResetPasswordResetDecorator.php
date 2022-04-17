<?php

declare(strict_types=1);

namespace App\OpenApi\Auth;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model;

final class ResetPasswordResetDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {

    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['ResetPasswordResetCredentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'example' => 'string',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'string',
                ]
            ],
        ]);

        $pathItem = new Model\PathItem(
            ref: 'Reset password',
            post: new Model\Operation(
                operationId: 'resetPasswordResetItem',
                tags: ['Auth Reset Password'],
                responses: [
                    '200' => [
                        'description' => 'Password reset.'
                    ],
                    '401' => [
                        'description' => 'Unauthorized.',
                        'content' => [],
                    ]
                ],
                summary: 'Reset password.',
                requestBody: new Model\RequestBody(
                    description: 'Reset password.',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ResetPasswordResetCredentials',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/api/auth/reset-password/reset', $pathItem);

        return $openApi;
    }
}