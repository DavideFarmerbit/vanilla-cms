<?php

use VanillaCms\Core\Registry\Page;

/** @param Page[] $archetypes */
function render_archetypes_list(array $archetypes): void
{
    ?>
    <h1>Archetypes</h1>
    <ul class="admin-list">
        <?php foreach ($archetypes as $archetype): ?>
            <li>
                <a href="/admin/archetypes/<?= htmlspecialchars($archetype->slug()) ?>">
                    <?= htmlspecialchars($archetype->label()) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
