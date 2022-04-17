<?php

namespace App\Controller\Media;

use App\Entity\UserImage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class GetUserImageController extends AbstractController
{
    public function __invoke(UserImage $data): BinaryFileResponse
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/var/images/users/' . $data->filePath;

        return new BinaryFileResponse($filePath);
    }
}