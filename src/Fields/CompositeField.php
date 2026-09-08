<?php

namespace VanillaCms\Fields;

abstract class CompositeField extends Field
{
    public function toArray(): array
    {
        return array_map(fn ($field) => $field->toArray(), $this->getFields());
    }
    
    public function fromArray(array $data): void
    {
        foreach ($this->getFields() as $name => $field) {
            $field->fromArray($data[$name] ?? []);
        }
    }
    
    public function render(string $name): void
    {
        $renderFields = function(string $name)
        {
            // Sort owned fields by priority
            $sortedFields = $this->getFields();
            uasort($sortedFields, function($a, $b) {
                return $b->priority() <=> $a->priority();
            });
            // Rended sorted fields
            foreach ($sortedFields as $fieldName => $field) {
                $field->render("{$name}[{$fieldName}]");
            }
        };
        
        $unwrap = !empty($this->config['vcms-unwrap']);
        $columns = !empty($this->config['vcms-columns']);
        ?>
        <div class="vcms-field vcms-field--composite">
            <div class="vcms-field__label">
                <span class="vcms-field__label-text<?= $unwrap ? ' vcms-field__label-text--hidden' : '' ?>"><?= htmlspecialchars($this->config['label'] ?? 'value') ?></span>
                <div class="vcms-field__group<?= $unwrap ? ' vcms-field__group--unwrap' : ''?><?= $columns ? ' vcms-field__group--columns' : ''?>">
                    <?php $renderFields($name) ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get all the Fields of this class.
     * @return array<string, Field> Name => Field
     */
    public function getFields(): array
    {
        return FieldReflection::getFields($this);
    }
}