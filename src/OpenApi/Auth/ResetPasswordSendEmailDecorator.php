<?php

declare(strict_types=1);

namespace App\OpenApi\Auth;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Model;

final class ResetPasswordSendEmailDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {

    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['ResetPasswordSendEmailCredentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'example' => 'johndoe@example.com',
                ]
            ],
        ]);

        $pathItem = new Model\PathItem(
            ref: 'Reset password send email',
            post: new Model\Operation(
                operationId: 'resetPasswordSendEmailItem',
                tags: ['Auth Reset Password'],
                responses: [
                    '200' => [
                        'description' => 'Email sended.'
                    ],
                    '401' => [
                        'description' => 'Unauthorized.',
                        'content' => [],
                    ]
                ],
                summary: 'Reset password send email.',
                requestBody: new Model\RequestBody(
                    description: 'Reset password send email.',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/ResetPasswordSendEmailCredentials',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/api/auth/reset-password/send-email', $pathItem);

        return $openApi;
    }
}