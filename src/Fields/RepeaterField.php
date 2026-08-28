<?php

namespace VanillaCms\Fields;

use Closure;

/** @template T of Field */
class RepeaterField extends Field
{
    /** @var class-string<T> */
    private string $fieldClass;

    /** @var Closure(): T */
    private Closure $factory;

    /** @var T[] */
    private array $items = [];

    /**
     * @param class-string<T> $fieldClass
     * @param callable():T $factory
     * @param array $config
     */
    public function __construct(string $fieldClass, callable $factory, array $config)
    {
        parent::__construct($config);
        $this->fieldClass = $fieldClass;
        $this->factory = $factory;
    }

    /** @return T[] */
    public function items(): array {
        return $this->items;
    }
    
    /*----------------------------------------------------------------------------------------------------------------*/

    /** @return T */
    public function addItem(): Field {
        return $this->items[] = ($this->factory)();
    }

    /** @return T */
    public function insertItem(int $index): Field {
        $newField = ($this->factory)();
        array_splice($this->items, $index, 0, array($newField));
        return $newField;
    }

    public function removeItem(int $index): void {
        array_splice($this->items, $index, 1);
    }

    /*----------------------------------------------------------------------------------------------------------------*/
    
    public function toArray(): array
    {
        return ['items' => array_map(fn ($item) => $item->toArray(), $this->items)];
    }

    public function fromArray(array $data): void
    {
        $this->items = [];
        foreach ($data['items'] ?? [] as $itemData) {
            $this->addItem()->fromArray($itemData);
        }
    }

    public function render(string $name): void
    {
        ?>
        <div class="vcms-field vcms-field--repeater">
            <div class="vcms-field__label">
                <?= htmlspecialchars($this->config['label'] ?? 'value') ?>
                <div class="vcms-repeater" data-vcms-repeater>
                    <div class="vcms-repeater__items" data-vcms-repeater-items>
                        <?php foreach ($this->items as $index => $item): ?>
                            <?php $this->renderItem($item, $name, (string) $index); ?>
                        <?php endforeach; ?>
                    </div>
                    <template data-vcms-repeater-template>
                        <?php $this->renderItem(($this->factory)(), $name, self::INDEX_PLACEHOLDER); ?>
                    </template>
                    <button type="button" class="vcms-btn vcms-btn--repeater-add" data-vcms-repeater-add>Add</button>
                </div>
            </div>
        </div>
        <?php
    }

    private const INDEX_PLACEHOLDER = '__VCMS_REPEATER_INDEX__';

    /** @param T $item */
    private function renderItem(Field $item, string $name, string $index): void
    {
        ?>
        <div class="vcms-repeater__item" data-vcms-repeater-item>
            <div class="vcms-repeater__item-fields">
                <?php $item->render("{$name}[items][{$index}]"); ?>
            </div>
            <div class="vcms-repeater__item-actions">
                <button type="button" class="vcms-icon-btn vcms-btn--repeater-insert" data-vcms-repeater-insert>
                    <?php vcms_icon('add') ?>
                </button>
                <button type="button" class="vcms-icon-btn vcms-icon-btn--danger vcms-btn--repeater-delete" data-vcms-repeater-delete>
                    <?php vcms_icon('trash') ?>
                </button>
            </div>
        </div>
        <?php
    }
}