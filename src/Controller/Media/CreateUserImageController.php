<?php

namespace App\Controller\Media;

use App\Entity\UserImage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class CreateUserImageController extends AbstractController
{
    public function __invoke(Request $request): UserImage
    {
        $uploadedFile = $request->files->get('file');
        
        if (!$uploadedFile) {
            throw new BadRequestHttpException('"file" is required');
        }

        $userImage = new UserImage();
        $userImage->file = $uploadedFile;

        return $userImage;
    }
}