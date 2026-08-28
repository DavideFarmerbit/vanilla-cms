## Vanilla CMS

A minimal, framework-free, PHP library for defining content types, storing their data, routing requests to pages, and 
editing content through a small admin panel.

### What's included
A `Router` class handles routing requests to pages. In your `index.php` file, you can use `Router::dispatch`, 
passing in an array of `RouterDispatcher` to associate url patterns with a callback function.

Site pages are defined by `Page` objects: extend `Page`, implement the `render_internal` method, and provide the 
parameters requested by the parent's constructor. Finally, register the page through `register_pages` which takes in an
array of `Page` classes.

In `Page` classes you can define `Field` derived members, which must be initialized in the constructor. These fields will
show up in the built-in admin panel to edit their values, and they can be freely accessed in the `render_internal` method.

The library has an Admin panel, which allows editing and creating pages from the types that were registered.

To fully initialize the CMS, you must call `Auth::set` passing in a `AuthDriver` object, and a link to the login page.
You must also call `Storage::set` passing in a `StorageDriver` object, to initialize the database connection. The library
comes with a `JsonStorage` class, which can be created by passing in a root directory for the JSON files to be stored in.
The final setup step is to call `AdminAssets::set` to register the admin panel .js and .css files (the library comes with
a default theme you can use).

### Minimal example

```injectablephp
// Register the page types
register_pages([
    HomePage::class,
    ArchivePage::class,
    ProductPage::class,
    LoginPage::class,
]);

// Set auth driver class for admin pannel
Auth::set(new class implements AuthDriver {
    public function isAdmin(): bool
    {
        return true;
    }
}, '/login');

// Set storage driver
Storage::set(new JsonStorage(__DIR__ . '/../storage'));

// Set admin assets
AdminAssets::set('/path/to/admin.css', '/path/to/admin.css');

Router::dispatch(default_router_dispatchers());
```

```injectablephp
namespace App\Pages;

use VanillaCms\Core\Registry\Page;
use VanillaCms\Fields\TextField;

class ProductPage extends Page {
    
    public TextField $productName;

    public function __construct()
    {
        parent::__construct('product', 'Product', true);
        $this->productName = new TextField(['label' => 'Name']);
    }

    public function render_internal(): void
    {
        require_once __DIR__ . '/../includes/header.php';
        require_once __DIR__ . '/../includes/footer.php';

        generate_header($this->meta()->name());
        ?>
        <h1><?= htmlspecialchars($this->label()) ?>: <?= htmlspecialchars($this->meta()->slug()) ?></h1>
        <p>Name: <?= htmlspecialchars($this->productName->getText()) ?></p>
        <?php
        generate_footer();
    }
}
```

```injectablephp
<?php

namespace App\Pages;

use VanillaCms\Admin\AdminController;
use VanillaCms\Auth\Auth;
use VanillaCms\Core\Registry\Page;
use VanillaCms\Core\Router\Router;

class LoginPage extends Page {

    public function __construct()
    {
        parent::__construct('login', 'Login', false);
    }

    public function render_internal(): void
    {
        // If the user is already logged in, redirect to the page they were trying to access before logging in.
        if (Auth::isAdmin()) {
            Router::return(AdminController::getHomeUrl());
        }

        require_once __DIR__ . '/../includes/header.php';
        require_once __DIR__ . '/../includes/footer.php';

        generate_header($this->meta()->name());
        ?>
        <h1>Login</h1>
        <p>Login is required</p>
        <?php
        generate_footer();
    }
}
```

### Requirements

- PHP >= 8.1

### Installation

```bash
composer require xylo-is-coding/vanilla-cms
```
