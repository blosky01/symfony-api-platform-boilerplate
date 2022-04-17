<?php

declare(strict_types=1);

namespace App\OpenApi\Auth;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model;

final class ResetPasswordCheckTokenDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {

    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['ResetPasswordCheckTokenCredentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'example' => 'string',
                ]
            ],
        ]);

        $pathItem = new Model\PathItem(
            ref: 'Reset password check token',
            post: new Model\Operation(
                operationId: 'resetPasswordCheckTokenItem',
                tags: ['Auth Reset Password'],
                responses: [
                    '200' => [
                        'description' => 'Token checked.'
                    ],
                    '401' => [
                        'description' => 'Unauthorized.',
                        'content' => [],
                    ]
                ],
                summary: 'Reset password check token.',
                requestBody: new Model\RequestBody(
                    description: 'Reset password check token.',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ResetPasswordCheckTokenCredentials',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/api/auth/reset-password/check-token', $pathItem);

        return $openApi;
    }
}