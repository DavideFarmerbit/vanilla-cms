<?php

namespace VanillaCms\Fields;

use BackedEnum;
use UnitEnum;

/** @template T of UnitEnum */
class EnumField extends Field
{
    /** @var class-string<T> */
    private string $enumClass;

    /** @var T|null */
    private ?UnitEnum $value = null;

    /**
     * @param class-string<T> $enumClass
     * @param array $config
     */
    public function __construct(string $enumClass, array $config)
    {
        parent::__construct($config);
        $this->enumClass = $enumClass;
    }

    /** @return T|null */
    public function value(): ?UnitEnum
    {
        return $this->value;
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    public function toArray(): array
    {
        return ['value' => $this->value instanceof BackedEnum ? $this->value->value : $this->value?->name];
    }

    public function fromArray(array $data): void
    {
        $this->value = $this->resolveCase($data['value'] ?? null);
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
                            <option value="<?= htmlspecialchars($this->caseValue($case)) ?>" <?= $case === $this->value ? 'selected' : '' ?>><?= htmlspecialchars($this->caseLabel($case)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </span>
            </label>
        </div>
        <?php
    }

    /*----------------------------------------------------------------------------------------------------------------*/

    /** @return T|null */
    private function resolveCase(mixed $raw): ?UnitEnum
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $enumClass = $this->enumClass;
        if (is_subclass_of($enumClass, BackedEnum::class)) {
            return $enumClass::tryFrom($raw);
        }

        foreach ($enumClass::cases() as $case) {
            if ($case->name === $raw) {
                return $case;
            }
        }
        return null;
    }

    /** @param T $case */
    private function caseValue(UnitEnum $case): string
    {
        return (string) ($case instanceof BackedEnum ? $case->value : $case->name);
    }

    /** @param T $case */
    private function caseLabel(UnitEnum $case): string
    {
        $labels = $this->config['labels'] ?? [];
        if (isset($labels[$case->name])) {
            return $labels[$case->name];
        }

        $base = $case instanceof BackedEnum ? (string) $case->value : $case->name;
        return ucfirst(str_replace('_', ' ', strtolower($base)));
    }
}
