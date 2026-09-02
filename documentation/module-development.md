# Motherboard module development

Motherboard loads every subdirectory of `public_html/modules/` that contains an `index.php`. No sample modules ship with the core; this file is the contract for building them.

Core version: **26.8.17.5**. Compare versions with PHP `version_compare()`.

## Module layout

```
public_html/modules/
  my-module/
    index.php          Required. Returns the module definition array.
    hooks.php          Optional extra PHP, included from boot if you want.
    assets/            Optional static files (serve them yourself or via a route).
```

The folder name is the default slug. Direct HTTP access to `modules/` is denied; Motherboard includes `index.php` from PHP.

## `index.php` definition

`index.php` **must return an array**. Minimum and Motherboard/PHP floors are required. Maximums may be `null`.

```php
<?php
return [
    'name' => 'My Module',
    'slug' => 'my-module',
    'description' => 'Short summary',
    'min_motherboard_version' => '26.8.14.1', // required
    'max_motherboard_version' => null,        // optional
    'min_php_version' => '8.4.0',             // required
    'max_php_version' => null,                // optional
    'boot' => function (array $module) {
        Hooks::addAction('app.ready', function () {
            // register hooks here
        });
    },
];
```

If `min_motherboard_version` or `min_php_version` is missing, the module is skipped. Incompatible version ranges are also skipped and recorded on `modules.loaded`.

## Hook API

```php
Hooks::addAction(string $hook, callable $callback, int $priority = 10): void;
Hooks::doAction(string $hook, ...$args): void;

Hooks::addFilter(string $hook, callable $callback, int $priority = 10): void;
Hooks::applyFilters(string $hook, $value, ...$args); // must return the (possibly changed) value
```

Lower `$priority` runs first. Use actions for side effects and filters to change data.

Helpers available to modules: `t()`, `I18n`, `Hooks`, `BASE_URL`, `APP_NAME`, models, and the `$router` passed into early hooks.

---

## Lifecycle hooks

### `app.boot` (action)

**When:** After English is loaded and the database object exists, before modules finish loading.

**Arguments:** `Router $router`, `Database $database`

Use for very early setup. Prefer registering most callbacks in `boot` and running them on `app.ready`.

### `module.loaded` (action)

**When:** After a single module passes version checks and its `boot` callable has run.

**Arguments:** `array $definition`

### `modules.loaded` (action)

**When:** After every module directory has been processed.

**Arguments:** `array $loaded`, `array $skipped`  
Each skipped item is `['slug' => string, 'reason' => string]`.

### `app.ready` (action)

**When:** All modules are loaded, before core routes are registered.

**Arguments:** `Router $router`, `Database $database`, `ModuleLoader $loader`

### `router.register` (action)

**When:** Immediately after Motherboard registers its built-in routes.

**Arguments:** `Router $router`

Add routes with `$router->addRoute('/path', 'ControllerName', 'method')`. Controllers must live in `public_html/controllers/` unless you pass the module controller file as the fourth argument: `$router->addRoute('/path', 'ControllerName', 'method', $definition['path'] . '/controllers/ControllerName.php')`.

### `request.uri` (filter)

**When:** After the request path is normalized, before route matching.

**Arguments:** `string $uri`  
**Return:** `string` URI (must start with `/`).

### `request.before` (action)

**When:** After URI filters, before the matched controller runs.

**Arguments:** `string $uri`

### `request.after` (action)

**When:** After the controller finishes (including 404).

**Arguments:** `string $uri`

---

## View and layout hooks

### `view.data` (filter)

**When:** Just before a view is rendered.

**Arguments:** `array $data`, `string $viewName`  
**Return:** `array` view data.

### `view.render.before` (action)

**When:** After data is filtered, immediately before the view file is included.

**Arguments:** `string $viewName`, `array $data`

### `view.render.after` (action)

**When:** After the view file has been included.

**Arguments:** `string $viewName`, `array $data`

### `layout.nav.before` (action)

**When:** Desktop navigation, before Home / Work Orders links.

Output HTML with `echo`.

### `layout.nav.after_customers` (action)

**When:** Desktop navigation, immediately after the Customers link (Admin only).

Output HTML with `echo`.

### `layout.nav.after_customers.mobile` (action)

**When:** Mobile menu, immediately after the Customers link (Admin only).

### `layout.nav` (action)

**When:** Desktop navigation, after admin links and before Log Out.

### `layout.nav.mobile` (action)

**When:** Mobile menu, before Log Out.

### `layout.footer` (action)

**When:** Authenticated footer, before the “Powered by” line.

---

## Authentication hooks

### `auth.login.after` (action)

**When:** Session is established after a successful login (including 2FA).

**Arguments:** `int $userId`, `array $user`

### `auth.logout` (action)

**When:** User logs out, before the session is destroyed.

**Arguments:** `int $userId`

---

## Work order hooks

### `work_order.create.data` (filter)

**When:** Step 4 of create, before insert.

**Arguments:** `array $workOrderData`  
**Return:** `array`

### `work_order.create.before` (action)

**Arguments:** `array $workOrderData`

### `work_order.create.after` (action)

**Arguments:** `int $workOrderId`, `array $workOrderData`

### `work_order.update.data` (filter)

**When:** Saving the work order details form.

**Arguments:** `array $updateData`, `int $id`  
**Return:** `array`

### `work_order.update.before` (action)

**Arguments:** `int $id`, `array $updateData`

### `work_order.update.after` (action)

**Arguments:** `int $id`, `array $updateData`

### `work_order.delete.before` (action)

**Arguments:** `int $id`, `array $workOrder`

### `work_order.delete.after` (action)

**Arguments:** `int $id`, `array $workOrder`  
The row is already deleted; `$workOrder` is the snapshot from before delete.

### `work_order.view.before_attachments` (action)

**When:** Work order details view, immediately before the Attachments section.

**Arguments:** `array $workOrder`, `array $context`  
`$context` includes `canEdit` (bool) and `csrf_token` (string).

Output HTML with `echo` or `include`.

### `work_order.print.before_attachments` (action)

**When:** Printable work order, immediately before the Attachments list.

**Arguments:** `array $workOrder`

---

## Customer hooks

### `customer.create.after` (action)

**Arguments:** `int $customerId`, `array $customerData`

### `customer.update.after` (action)

**Arguments:** `int $id`, `array $updateData`

### `customer.delete.after` (action)

**Arguments:** `int $id`, `array $customer`

---

## Attachment storage

Modules can add storage backends for work order attachments. Pending uploads during work order creation stay on the local disk until the work order is saved. Final files use the destination selected under Settings → Attachments.

Each attachment row stores `storage_destination` so existing files stay readable after the current destination changes.

### `attachment.destinations` (filter)

**When:** Building the storage destination dropdown and validating the saved destination.

**Arguments:** `array $destinations` (`value => label`, already includes `local`)  
**Return:** `array`

```php
Hooks::addFilter('attachment.destinations', function (array $destinations): array {
    $destinations['s3'] = 'S3-compatible storage';
    return $destinations;
});
```

### `attachment.storage.put` (filter)

**When:** Storing a finalized attachment on a non-local destination.

**Arguments:** `array $result`, `string $destination`, `string $key`, `string $sourcePath`, `array $meta`  
`$meta` includes `mime_type`, `original_filename`, and `file_size`.  
**Return:** `array` with `handled` (bool), `ok` (bool), and `error` (string).

If `handled` is false, Motherboard treats the destination as unavailable.

### `attachment.storage.fetch` (filter)

**When:** Downloading or displaying an attachment that was stored on a non-local destination.

**Arguments:** `array $result`, `string $destination`, `string $key`, `array $attachment`  
**Return:** `array` with `handled`, `ok`, `path` (local temp file to read), and `error`. Motherboard deletes the temp file after sending it.

### `attachment.storage.delete` (filter)

**When:** Removing a stored attachment file (single delete or work order delete).

**Arguments:** `array $result`, `string $destination`, `string $key`, `array $attachment`  
**Return:** `array` with `handled`, `ok`, and `error`.

### `settings.attachments.destination_help` (action)

**When:** Settings page, under the storage destination dropdown.

**Arguments:** `string $currentDestination`

Output extra help HTML with `echo`.

---

## Settings and install

### `settings.saved` (action)

**When:** After a settings form section is saved.

**Arguments:** `string $section` (`company`, `security`, `format`, `language`, or `attachments`), `array $posted`

### `install.complete` (action)

**When:** Schema, admin user, and default settings have been created.

**Arguments:** none

---

## Adding your own hooks

Core code can grow more `Hooks::doAction` / `applyFilters` calls. Prefer namespaced names: `area.object.event` (for example `invoice.pdf.before`). Document new hooks in this file when you add them to core.

## Localization from modules

Use `t('your.key')`. Core keys live in `public_html/lang/*.php`. A module may register extra strings with:

```php
Hooks::addFilter('view.data', function ($data) {
    return $data;
});
```

For new UI strings, either ship language files the shop copies into `lang/`, or keep module UI in the module and translate inside the module folder.

## Compatibility rules

- Always set `min_motherboard_version` and `min_php_version`.
- Set `max_*` only when a breaking change is known.
- Do not assume write access outside the module directory.
- Do not replace core files; use hooks.
- Treat work order `status` and `priority` database values as English enums; translate only for display with `t('status.Open')`, etc.
