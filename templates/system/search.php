<?php

declare(strict_types=1);

/** @var callable(string): string $e */
/** @var string $query */
/** @var list<array{title: string, url: string, excerpt: string}> $results */

$layout = 'layouts/default.php';
?>
<article>
    <header>
        <h1>Search results</h1>
    </header>

    <p><strong>Query:</strong> <?= $query !== '' ? $e($query) : 'No query provided' ?></p>

    <?php if ($results !== []): ?>
        <section aria-label="Search results">
            <ul>
                <?php foreach ($results as $result): ?>
                    <li>
                        <a href="<?= $e($result['url']) ?>"><?= $e($result['title']) ?></a>
                        <p><?= $e($result['excerpt']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php else: ?>
        <p>No results found.</p>
    <?php endif; ?>
</article>
