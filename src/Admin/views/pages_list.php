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
                <?php if ($instance): ?>
                    <form method="post" action="/admin/pages/<?= htmlspecialchars($page->slug()) ?>/delete" data-confirm="Delete this entry? This cannot be undone.">
                        <button type="submit" class="admin-danger">Delete</button>
                    </form>
                <?php else: ?>
                    <span class="admin-disabled">Delete</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
