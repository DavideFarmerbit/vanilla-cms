<?php

namespace VanillaCms\Admin\Tabs;

use VanillaCms\Admin\AdminTab;

class HomeTab extends AdminTab
{
    public function __construct()
    {
        parent::__construct('home', 'Home');
    }

    public static function getHomeUrl(): string
    {
        return '/admin/home';
    }

    public function dispatch(array $segments): void
    {
        $this->renderHome();
    }
    
    protected function renderHome(): void {
        ?>
        <h1 class="vcms-page-title">Vanilla Cms</h1>
        <p>Select a category from the side bar to get started.</p>
        <?php
    }
}