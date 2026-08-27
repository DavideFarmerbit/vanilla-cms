<?php

namespace VanillaCms\Fields;

use DateTimeImmutable;

class DateField extends Field
{
    private ?DateTimeImmutable $value = null;

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public function fromArray(array $data): void
    {
        $this->value = $data['value'] ?? null;
    }

    public function render(string $name): void
    {
        $dateStr = $this->value ? $this->value->format('Y-m-d') : '';
        ?>
        <label>
            <?= $this->config['label'] ?? 'value' ?>
            <input type="date" role="switch" name="<?= "{$name}[value]" ?>" value="<?= htmlspecialchars($dateStr) ?>">
        </label>
        <?php
    }
}