<?php

namespace VanillaCms\Fields;

use VanillaCms\Fields\Field;

class RichTextField extends Field
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
        $shortClass = !empty($this->config['short']) ? ' vcms-field--rich-text--short' : '';
        ?>
        <div class="vcms-field vcms-field--rich-text<?= $shortClass ?>">
            <label class="vcms-field__label">
                <?= htmlspecialchars($this->config['label'] ?? 'value') ?>
                <textarea class="vcms-field__input" name="<?= "{$name}[value]" ?>"><?= htmlspecialchars($this->value) ?></textarea>
            </label>
        </div>
        <?php
    }

    public function getText(): string
    {
        return $this->value;
    }
}