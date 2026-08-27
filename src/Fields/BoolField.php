<?php

namespace VanillaCms\Fields;

class BoolField extends Field
{
    private bool $value = false;

    public function toArray(): array
    {
        return ['value' => $this->value];
    }

    public function fromArray(array $data): void
    {
        $this->value = $data['value'] ?? false;
    }

    public function render(string $name): void
    {
        ?>
        <label>
            <?= $this->config['label'] ?? 'value' ?>
            <input type="checkbox" role="switch" name="<?= "{$name}[value]" ?>" value="1" <?= $this->value ? 'checked' : '' ?>>
        </label>
        <?php
    }
}