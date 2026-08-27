<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Core\Registry\Page;

/** @param Page[] $archetypes */
function render_archetypes_list(array $archetypes): void
{
    ?>
    <h1>Archetypes</h1>
    <ul class="admin-list">
        <?php foreach ($archetypes as $archetype): ?>
            <li>
                <a href="<?= AdminController::getArchetypeUrl($archetype->slug()) ?>">
                    <?= htmlspecialchars($archetype->label()) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
