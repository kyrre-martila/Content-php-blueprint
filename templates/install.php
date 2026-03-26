<?php

declare(strict_types=1);

$layout = 'layouts/default.php';

/** @var array<string, bool> $checks */
/** @var array<string, string> $errors */
/** @var array<string, string> $old */
?>
<section>
    <h1>Install Content PHP Blueprint</h1>

    <?php if ($errors !== []): ?>
        <p role="alert" style="color:#b42318;">Installation could not be completed. Fix the errors and try again.</p>
    <?php endif; ?>

    <?php if (isset($details) && is_string($details) && trim($details) !== ''): ?>
        <pre style="white-space:pre-wrap;color:#b42318;"><?= $e($details) ?></pre>
    <?php endif; ?>

    <h2>Environment checks</h2>
    <ul>
        <li>PHP version &gt;= 8.3: <?= ($checks['php_version'] ?? false) ? 'PASS' : 'FAIL' ?></li>
        <li>PDO extension: <?= ($checks['pdo'] ?? false) ? 'PASS' : 'FAIL' ?></li>
        <li>pdo_mysql extension: <?= ($checks['pdo_mysql'] ?? false) ? 'PASS' : 'FAIL' ?></li>
        <li>storage/ writable: <?= ($checks['storage_writable'] ?? false) ? 'PASS' : 'FAIL' ?></li>
        <li>.env writable (optional): <?= ($checks['env_writable'] ?? false) ? 'PASS' : 'WARN' ?></li>
    </ul>

    <?php if (isset($errors['environment'])): ?>
        <p role="alert" style="color:#b42318;"><?= $e($errors['environment']) ?></p>
    <?php endif; ?>

    <form method="post" action="/install" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">

        <h2>Database settings</h2>

        <label for="db_host">Database host</label>
        <input id="db_host" name="db_host" type="text" value="<?= $e($old['db_host']) ?>" required>
        <?php if (isset($errors['db_host'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['db_host']) ?></p><?php endif; ?>

        <label for="db_port">Database port</label>
        <input id="db_port" name="db_port" type="text" value="<?= $e($old['db_port']) ?>" required>
        <?php if (isset($errors['db_port'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['db_port']) ?></p><?php endif; ?>

        <label for="db_name">Database name</label>
        <input id="db_name" name="db_name" type="text" value="<?= $e($old['db_name']) ?>" required>
        <?php if (isset($errors['db_name'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['db_name']) ?></p><?php endif; ?>

        <label for="db_user">Database user</label>
        <input id="db_user" name="db_user" type="text" value="<?= $e($old['db_user']) ?>" required>
        <?php if (isset($errors['db_user'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['db_user']) ?></p><?php endif; ?>

        <label for="db_pass">Database password</label>
        <input id="db_pass" name="db_pass" type="password" value="<?= $e($old['db_pass']) ?>">

        <h2>Admin user</h2>

        <label for="admin_email">Admin email</label>
        <input id="admin_email" name="admin_email" type="email" value="<?= $e($old['admin_email']) ?>" required>
        <?php if (isset($errors['admin_email'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['admin_email']) ?></p><?php endif; ?>

        <label for="admin_password">Admin password</label>
        <input id="admin_password" name="admin_password" type="password" required>
        <?php if (isset($errors['admin_password'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['admin_password']) ?></p><?php endif; ?>

        <?php if (isset($errors['database'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['database']) ?></p><?php endif; ?>
        <?php if (isset($errors['migrations'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['migrations']) ?></p><?php endif; ?>
        <?php if (isset($errors['admin'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['admin']) ?></p><?php endif; ?>
        <?php if (isset($errors['install_state'])): ?><p role="alert" style="color:#b42318;"><?= $e($errors['install_state']) ?></p><?php endif; ?>

        <button type="submit">Install application</button>
    </form>
</section>
