<?php

use VanillaCms\Core\Registry\Page;

/** @param Page[] $pages */
function render_pages_list(array $pages): void
{
    ?>
    <h1>Pages</h1>
    <ul class="admin-list">
        <?php foreach ($pages as $page): ?>
            <?php $instance = Storage::findFirst($page->slug()); ?>
            <li>
                <span><?= htmlspecialchars($page->label()) ?></span>
                <a href="/admin/pages/<?= htmlspecialchars($page->slug()) ?>/edit">
                    <?= $instance ? 'Edit' : 'Create' ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
