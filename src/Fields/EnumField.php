<?php

namespace VanillaCms\Fields;

use BackedEnum;
use InvalidArgumentException;

/** @template T of BackedEnum */
class EnumField extends Field
{
    /** @var class-string<T> */
    private string $enumClass;

    /** @var T|null */
    private ?BackedEnum $value = null;

    /**
     * @param class-string<T> $enumClass
     * @param array $config
     */
    public function __construct(string $enumClass, array $config)
    {
        if (!is_subclass_of($enumClass, BackedEnum::class)) {
            throw new InvalidArgumentException("EnumField expects a backed enum, {$enumClass} given.");
        }

        parent::__construct($config);
        $this->enumClass = $enumClass;
    }

    /** @return T|null */
    public function value(): ?BackedEnum
    {
        return $this->value;
    }
    
    public function displayValue(): string
    {
        return $this->caseLabel($this->value);
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    public function toArray(): array
    {
        return ['value' => $this->value?->value];
    }

    public function fromArray(array $data): void
    {
        $raw = $data['value'] ?? null;
        $this->value = $raw === null || $raw === '' ? null : $this->enumClass::tryFrom($raw);
    }

    public function render(string $name): void
    {
        $enumClass = $this->enumClass;
        ?>
        <div class="vcms-field vcms-field--select">
            <label class="vcms-field__label">
                <?= htmlspecialchars($this->config['label'] ?? 'value') ?>
                <span class="vcms-field__select-wrap vcms-field__select-wrap--block">
                    <select class="vcms-field__input" name="<?= "{$name}[value]" ?>">
                        <?php foreach ($enumClass::cases() as $case): ?>
                            <option value="<?= htmlspecialchars((string) $case->value) ?>" <?= $case === $this->value ? 'selected' : '' ?>><?= htmlspecialchars($this->caseLabel($case)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
        </div>
        <?php
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    /** @param T $case */
    private function caseLabel(BackedEnum $case): string
    {
        if (isset($this->config['vcms-label-provider'])) {
            return ($this->config['vcms-label-provider'])($case);
        }
        
        $labels = $this->config['vcms-labels'] ?? [];
        if (isset($labels[$case->name])) {
            return $labels[$case->name];
        }

        return ucfirst(str_replace(['_', '-'], ' ', strtolower((string) $case->value)));
    }
}
