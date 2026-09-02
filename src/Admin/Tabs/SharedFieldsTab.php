<?php

namespace VanillaCms\Admin\Tabs;

use VanillaCms\Admin\AdminTab;
use VanillaCms\Core\Router\Router;

class SharedFieldsTab extends AdminTab
{
    public function __construct() {
        parent::__construct('shared-fields', 'Shared Fields');
    }

    public static function getSharedFieldsUrl(): string {
        return "/admin/shared-fields";
    }

    public static function getSharedFieldUrl(string $slug): string {
        return "/admin/shared-fields/{$slug}";
    }
    
    public function dispatch(array $segments): void
    {
        Router::notFound();
    }
}