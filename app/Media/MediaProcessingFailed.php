<?php

namespace App\Media;

use RuntimeException;
use Throwable;

/** Оригинал фото не удалось прочитать или нарезать (неподдерживаемый формат, битый файл). */
class MediaProcessingFailed extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Не удалось обработать фото: формат не поддерживается или файл повреждён.', 0, $previous);
    }
}
