<?php

use VanillaCms\Admin\AdminController;
use VanillaCms\Admin\AdminPageAction;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Storage\Storage;

/** @param Page[] $pages */
function render_pages_instances(array $pages): void
{
    ?>
    <h1 class="vcms-page-title">Pages</h1>
    <?php if (empty($pages)): ?>
        <p class="vcms-empty-state">No pages yet.</p>
    <?php else: ?>
        <table class="vcms-table">
            <thead>
                <?php render_instance_row_header() ?>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <?php $instance = Storage::findFirstPageInstance($page->slug()); ?>
                    <?php render_instance_row(
                        $page,
                        $instance,
                        AdminController::getPageEditUrl($page->slug(), AdminPageAction::EDIT),
                        AdminController::getPageEditUrl($page->slug(), AdminPageAction::DELETE),
                    ); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php
}
