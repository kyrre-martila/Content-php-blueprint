<?php

declare(strict_types=1);

/** @var callable(string): string $e */
/** @var string $searchQuery */
/** @var list<array{title: string, slug: string, excerpt: string}> $searchResults */

$layout = 'layouts/default.php';
?>
<article>
    <header>
        <h1>Search results</h1>
    </header>

    <?php if ($searchQuery === ''): ?>
        <p>Please enter a search query.</p>
    <?php elseif ($searchResults === []): ?>
        <p>No results found for "<?= $e($searchQuery) ?>".</p>
    <?php else: ?>
        <p>Showing results for "<?= $e($searchQuery) ?>".</p>
        <section aria-label="Search results">
            <ul>
                <?php foreach ($searchResults as $result): ?>
                    <li>
                        <a href="/<?= $e(ltrim($result['slug'], '/')) ?>"><?= $e($result['title']) ?></a>
                        <p><?= $e($result['excerpt']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</article>
