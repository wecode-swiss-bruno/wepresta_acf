<?php

declare(strict_types=1);

namespace WeprestaAcf\Application\FieldType;

interface FieldTypeInterface
{
    public function getType(): string;
    public function getLabel(): string;
    /** @return class-string */
    public function getFormType(): string;
    /** @param array<string, mixed> $fieldConfig @param array<string, mixed> $validation @return array<string, mixed> */
    public function getFormOptions(array $fieldConfig, array $validation = []): array;
    /** @param array<string, mixed> $fieldConfig */
    public function normalizeValue(mixed $value, array $fieldConfig = []): mixed;
    /** @param array<string, mixed> $fieldConfig */
    public function denormalizeValue(mixed $value, array $fieldConfig = []): mixed;
    /** @param array<string, mixed> $fieldConfig @param array<string, mixed> $renderOptions */
    public function renderValue(mixed $value, array $fieldConfig = [], array $renderOptions = []): string;
    /** @param array<string, mixed> $fieldConfig */
    public function getIndexValue(mixed $value, array $fieldConfig = []): ?string;
    /** @return array<string, mixed> */
    public function getDefaultConfig(): array;
    /** @return array<string, array<string, mixed>> */
    public function getConfigSchema(): array;
    /** @param array<string, mixed> $fieldConfig @param array<string, mixed> $validation @return array<string> */
    public function validate(mixed $value, array $fieldConfig = [], array $validation = []): array;
    public function supportsTranslation(): bool;
    public function getCategory(): string;
    public function getIcon(): string;
    /** @param array<string, mixed> $field @param array<string, mixed> $context */
    public function renderAdminInput(array $field, mixed $value, array $context = []): string;
    /** @param array<string, mixed> $field */
    public function getJsTemplate(array $field): string;
}

