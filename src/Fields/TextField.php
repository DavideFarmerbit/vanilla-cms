<?php

namespace VanillaCms\Fields;

class TextField extends Field
{
    private string $value = '';

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public function fromArray(array $data): void
    {
        $this->value = $data['value'];
    }

    public function render(string $name, array $config): void
    {
        ?>
        <label>
            <?= $config['label'] ?? 'value' ?>
            <input type="text" name="<?= "{$name}[value]" ?>" value="<?= $this->value ?>">
        </label>
        <?php
    }
}