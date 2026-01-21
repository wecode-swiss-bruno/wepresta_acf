<?php

/**
 * @author WePresta
 * @copyright 2024-2025 WePresta
 * @license MIT
 */

declare(strict_types=1);

namespace WeprestaAcf\Application\Template;


if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Result of a field group import operation.
 */
final class ImportResult
{
    private bool $success;

    private string $message = '';

    private string $version = '';

    private string $source = '';

    /** @var array<string> */
    private array $created = [];

    /** @var array<string> */
    private array $updated = [];

    /** @var array<string, string> */
    private array $skipped = [];

    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string> */
    private array $warnings = [];

    private int $fieldsImported = 0;

    public function __construct(bool $success = true, string $message = '')
    {
        $this->success = $success;
        $this->message = $message;
    }

    public function isSuccess(): bool
    {
        return $this->success && empty($this->errors);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return array<string>
     */
    public function getCreated(): array
    {
        return $this->created;
    }

    /**
     * @return array<string>
     */
    public function getUpdated(): array
    {
        return $this->updated;
    }

    /**
     * @return array<string, string>
     */
    public function getSkipped(): array
    {
        return $this->skipped;
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getFieldsImported(): int
    {
        return $this->fieldsImported;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function addCreated(string $slug): self
    {
        $this->created[] = $slug;

        return $this;
    }

    public function addUpdated(string $slug): self
    {
        $this->updated[] = $slug;

        return $this;
    }

    public function addSkipped(string $slug, string $reason): self
    {
        $this->skipped[$slug] = $reason;

        return $this;
    }

    public function addError(string $slug, string $error): self
    {
        $this->errors[$slug] = $error;
        $this->success = false;

        return $this;
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;

        return $this;
    }

    public function addFieldsImported(int $count): self
    {
        $this->fieldsImported += $count;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'message' => $this->message,
            'version' => $this->version,
            'source' => $this->source,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'fields_imported' => $this->fieldsImported,
            'summary' => [
                'created_count' => \count($this->created),
                'updated_count' => \count($this->updated),
                'skipped_count' => \count($this->skipped),
                'error_count' => \count($this->errors),
            ],
        ];
    }
}
