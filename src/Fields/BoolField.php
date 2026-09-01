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
    
    public function getValue(): bool {
        return $this->value;
    }

    public function render(string $name): void
    {
        ?>
        <div class="vcms-field vcms-field--bool">
            <label class="vcms-field__label">
                <?= htmlspecialchars($this->config['label'] ?? 'value') ?>
                <input class="vcms-field__input" type="checkbox" role="switch" name="<?= "{$name}[value]" ?>" value="1" <?= $this->value ? 'checked' : '' ?>>
            </label>
        </div>
        <?php
    }
}