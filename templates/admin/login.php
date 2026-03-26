<?php

declare(strict_types=1);

$layout = 'layouts/default.php';
?>
<section>
    <h1>Admin Login</h1>

    <?php if (isset($error) && is_string($error) && $error !== ''): ?>
        <p role="alert" style="color:#b42318;"><?= $e($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/admin/login">
        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" autocomplete="username" required>

        <label for="password">Password</label>
        <input id="password" type="password" name="password" autocomplete="current-password" required>

        <button type="submit">Login</button>
    </form>
</section>
