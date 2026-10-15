<?php
declare(strict_types=1);

namespace Domain\ValueObject\Web;

/** Ответ публичного сайта. @noCodeGen */
class WebResource
{
    private string $url;
    private string $body;
    private string $contentType;
    private int $status;

    public function __construct(string $url, string $body, string $contentType, int $status)
    {
        $this->url = $url;
        $this->body = $body;
        $this->contentType = $contentType;
        $this->status = $status;
    }

    public function getUrl(): string { return $this->url; }
    public function getBody(): string { return $this->body; }
    public function getContentType(): string { return $this->contentType; }
    public function getStatus(): int { return $this->status; }
}
