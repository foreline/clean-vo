<?php
declare(strict_types=1);

namespace Domain\ValueObject\Web;

/** Метаданные страницы, независимые от сущности и UI. @noCodeGen */
class WebPageMetadata
{
    private string $name;
    private string $description;
    private ?WebsiteIcon $icon;
    private string $iconError;
    private string $iconUri;

    public function __construct(string $name, string $description, ?WebsiteIcon $icon = null, string $iconError = '', string $iconUri = '')
    {
        $this->name = $name;
        $this->description = $description;
        $this->icon = $icon;
        $this->iconError = $iconError;
        $this->iconUri = $iconUri;
    }

    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getIcon(): ?WebsiteIcon { return $this->icon; }
    public function getIconError(): string { return $this->iconError; }
    public function getIconUri(): string { return $this->iconUri; }
}
