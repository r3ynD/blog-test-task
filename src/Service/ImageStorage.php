<?php

declare(strict_types=1);

namespace App\Service;

use finfo;
use App\Exception\ValidationException;
use RuntimeException;

class ImageStorage
{
    public function __construct(private string $directory)
    {
    }

    public function upload(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationException('Не удалось загрузить изображение. Максимальный размер — 2 МБ.');
        }
        $path = $file['tmp_name'] ?? '';
        if (!is_string($path) || !is_uploaded_file($path) || filesize($path) > 2 * 1024 * 1024) {
            throw new ValidationException('Изображение должно быть не больше 2 МБ.');
        }
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $size = @getimagesize($path);
        if (!isset($extensions[$mime]) || !$size || $size[0] * $size[1] > 20000000) {
            throw new ValidationException('Загрузите JPEG, PNG или WebP, не больше 20 мегапикселей.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($path, $this->directory . '/' . $name)) {
            throw new RuntimeException('Could not save the uploaded image.');
        }
        return '/uploads/' . $name;
    }

    public function delete(string $image): void
    {
        if (preg_match('~\A/uploads/[a-f0-9]{32}\.(jpg|png|webp)\z~', $image)) {
            $path = $this->directory . '/' . basename($image);
            if (is_file($path) && !unlink($path)) {
                error_log('Could not remove image: ' . $path);
            }
        }
    }
}
