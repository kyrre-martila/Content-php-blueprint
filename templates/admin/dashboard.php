<?php

declare(strict_types=1);

$layout = 'layouts/default.php';

$displayName = is_array($authUser) && is_string($authUser['display_name'] ?? null)
    ? $authUser['display_name']
    : 'Unknown';

$role = is_array($authUser) && is_string($authUser['role'] ?? null)
    ? $authUser['role']
    : 'unknown';
?>
<section>
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?= $e($displayName) ?>.</p>
    <p>Role: <strong><?= $e($role) ?></strong></p>

    <p>Admin content CRUD screens will be added next.</p>

    <form method="post" action="/admin/logout">
        <button type="submit">Logout</button>
    </form>
</section>
