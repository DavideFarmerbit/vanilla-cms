<?php

namespace VanillaCms\Fields;

use DateTimeImmutable;

class DateField extends Field
{
    private ?DateTimeImmutable $value = null;

    public function toArray(): array
    {
        $dateStr = $this->value ? $this->value->format('Y-m-d') : '';
        return ['value' => $dateStr];
    }

    public function fromArray(array $data): void
    {
        $this->value = DateTimeImmutable::createFromFormat('Y-m-d', $data['value'] ?? '') ?? null;
    }
    
    public function getDate(): ?DateTimeImmutable
    {
        return $this->value;
    }

    public function render(string $name): void
    {
        $dateStr = $this->value ? $this->value->format('Y-m-d') : '';
        ?>
        <div class="vcms-field vcms-field--date">
            <label class="vcms-field__label">
                <?= htmlspecialchars($this->config['label'] ?? 'value') ?>
                <input class="vcms-field__input" type="date" role="switch" name="<?= "{$name}[value]" ?>" value="<?= htmlspecialchars($dateStr) ?>">
            </label>
        </div>
        <?php
    }
}