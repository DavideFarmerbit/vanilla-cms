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
        $this->value = $data['value'] ?? '';
    }

    public function render(string $name): void
    {
        ?>
        <div class="vcms-field vcms-field--text">
            <label class="vcms-field__label">
                <?= htmlspecialchars($this->config['label'] ?? 'value') ?>
                <input class="vcms-field__input" type="text" name="<?= "{$name}[value]" ?>" value="<?= htmlspecialchars($this->value) ?>">
            </label>
        </div>
        <?php
    }
    
    public function getText(): string
    {
        return $this->value;
    }
}