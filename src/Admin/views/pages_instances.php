<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminPageAction;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Storage\Storage;

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
                    AdminController::getPageEditUrl($page->slug(), AdminPageAction::EDIT),
                    AdminController::getPageEditUrl($page->slug(), AdminPageAction::DELETE),
                ); ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
