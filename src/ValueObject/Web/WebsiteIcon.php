<?php
declare(strict_types=1);

namespace Domain\ValueObject\Web;

/** Проверенное изображение сайта в формате PNG. @noCodeGen */
class WebsiteIcon
{
    private string $png;

    public function __construct(string $png)
    {
        $size = @getimagesizefromstring($png);
        if (65536 < strlen($png) || false === $size || IMAGETYPE_PNG !== $size[2]
            || 0 >= $size[0] || 0 >= $size[1] || 64 < $size[0] || 64 < $size[1]) {
            throw new \InvalidArgumentException('Иконка должна быть PNG размером не более 64 × 64.');
        }
        $this->png = $png;
    }

    public function getPng(): string { return $this->png; }
    public function getHash(): string { return hash('sha256', $this->png); }
    /** Ключ помещается в EXTERNAL_ID текущего файлового хранилища (50 символов). */
    public function getStorageKey(): string { return 'website-icon:' . substr($this->getHash(), 0, 32); }
}
