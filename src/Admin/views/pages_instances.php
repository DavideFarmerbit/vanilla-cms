<?php

use VanillaCms\Core\Registry\Page;

/** @param Page[] $pages */
function render_pages_instances(array $pages): void
{
    ?>
    <h1>Pages</h1>
    <table class="admin-table">
        <thead>
            <?php render_instance_row_header() ?>
        </thead>
        <tbody>
            <?php foreach ($pages as $page): ?>
                <?php $instance = Storage::findFirst($page->slug()); ?>
                <?php render_instance_row(
                    $page,
                    $instance,
                    "/admin/pages/{$page->slug()}/edit",
                    "/admin/pages/{$page->slug()}/delete"
                ); ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
