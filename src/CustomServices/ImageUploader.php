<?php

namespace App\CustomServices;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploader
{
    public function __construct(
        private string $targetDirectory,
        private SluggerInterface $slugger
    ) {
    }

    public function upload(?UploadedFile $file): string
    {
        if (!$file) {
            return 'images/default_quizz_pic.jpg';
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->targetDirectory, $newFilename);

        return '/uploads/quizzes/' . $newFilename;
    }
}