<?php
declare(strict_types=1);

namespace Domain\ValueObject;

/**
 * Путь к файлу
 */
class FilePath implements StringValueObjectInterface
{
    /** @var string Относительный путь */
    private string $relativePath;
    
    /**
     * @param string $value Относительный путь
     */
    public function __construct(string $value)
    {
        $this->relativePath = $value;
    }
    
    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->relativePath;
    }
    
    /**
     * Относительный путь от корня модуля.
     * @return string
     */
    public function getRelativePath(): string
    {
        return $this->relativePath;
    }
    
    /**
     * Пуст ли путь.
     * @return bool
     */
    public function isEmpty(): bool
    {
        return '' === $this->relativePath;
    }
}
