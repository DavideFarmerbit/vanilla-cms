<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Pages\Page;

/** @param Page[] $archetypes */
function render_archetypes_list(array $archetypes): void
{
    ?>
    <h1 class="vcms-page-title">Archetypes</h1>
    <ul class="vcms-list">
        <?php foreach ($archetypes as $archetype): ?>
            <li class="vcms-list__item">
                <a class="vcms-list__link" href="<?= htmlspecialchars(AdminController::getArchetypeUrl($archetype->slug())) ?>">
                    <?= htmlspecialchars($archetype->label()) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
