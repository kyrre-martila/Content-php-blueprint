<?php

declare(strict_types=1);

$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$slug = (string) ($old['slug'] ?? '');
$selectedViewType = (string) ($old['view_type'] ?? 'single');
$templateExistsMap = is_array($templateExistsMap ?? null) ? $templateExistsMap : [];
$relationshipRulesSummary = is_array($relationshipRulesSummary ?? null) ? $relationshipRulesSummary : [];

$initialTemplatePath = $selectedViewType === 'collection'
    ? sprintf('templates/collections/%s.php', $slug)
    : sprintf('templates/content/%s.php', $slug);
$initialTemplateStatus = ($templateExistsMap[str_replace('templates/', '', $initialTemplatePath)] ?? false) === true;
?>

<?php if (isset($errors['general'])): ?>
    <p role="alert"><span class="admin-badge admin-badge--danger"><?= $e((string) $errors['general']) ?></span></p>
<?php endif; ?>

<article class="admin-panel admin-card">
    <div class="admin-card__header">
        <h3 class="admin-card__title"><?= $e((string) $formTitle) ?></h3>
    </div>

    <form class="admin-form" method="post" action="<?= $e((string) $actionPath) ?>" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">

        <div class="admin-form__group">
            <label class="admin-form__label" for="name">Name</label>
            <input class="admin-form__input" id="name" name="name" type="text" required value="<?= $e((string) ($old['name'] ?? '')) ?>">
            <p class="admin-form__help">Human-friendly content type label used in admin listings.</p>
            <?php if (isset($errors['name'])): ?>
                <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label" for="slug">Slug</label>
            <input
                class="admin-form__input"
                id="slug"
                name="slug"
                type="text"
                required
                value="<?= $e($slug) ?>"
                <?= ($slugReadonly ?? false) === true ? 'readonly aria-readonly="true"' : '' ?>
            >
            <p class="admin-form__help">Lowercase machine key (letters, numbers, underscore).</p>
            <?php if (($slugReadonly ?? false) === true): ?>
                <p class="admin-form__help">Slug is locked after creation to preserve content references.</p>
            <?php endif; ?>
            <?php if (isset($errors['slug'])): ?>
                <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['slug']) ?></p>
            <?php endif; ?>
        </div>

        <div class="admin-form__group">
            <span class="admin-form__label">View type</span>
            <div class="admin-form__options" role="radiogroup" aria-label="View type">
                <label class="admin-form__option" for="view_type_single">
                    <input id="view_type_single" type="radio" name="view_type" value="single" <?= $selectedViewType === 'single' ? 'checked' : '' ?>>
                    <span>single</span>
                </label>
                <label class="admin-form__option" for="view_type_collection">
                    <input id="view_type_collection" type="radio" name="view_type" value="collection" <?= $selectedViewType === 'collection' ? 'checked' : '' ?>>
                    <span>collection</span>
                </label>
            </div>
            <?php if (isset($errors['view_type'])): ?>
                <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['view_type']) ?></p>
            <?php endif; ?>

            <p class="admin-form__help admin-form__help--muted" id="template-preview">
                Template mapping: <code><?= $e($initialTemplatePath) ?></code>
            </p>
            <p class="admin-form__help" id="template-status" role="status">
                <?php if ($initialTemplateStatus): ?>
                    <span class="admin-badge admin-badge--success">Exists</span>
                <?php else: ?>
                    <span class="admin-badge admin-badge--warning">Missing template</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="admin-form__actions">
            <a class="admin-action admin-action--secondary" href="/admin/content-types">Cancel</a>
            <button class="admin-action admin-action--primary" type="submit"><?= $e((string) $submitLabel) ?></button>
        </div>
    </form>
</article>

<?php if (($slugReadonly ?? false) === true): ?>
    <article class="admin-panel admin-card">
        <div class="admin-card__header">
            <h3 class="admin-card__title">Relationship Rules Summary</h3>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <caption>Incoming and outgoing relationship rules for this content type</caption>
                <thead>
                    <tr>
                        <th scope="col">Direction</th>
                        <th scope="col">From type</th>
                        <th scope="col">To type</th>
                        <th scope="col">Relation type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($relationshipRulesSummary === []): ?>
                        <tr>
                            <td colspan="4">No relationship rules configured for this type yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($relationshipRulesSummary as $rule): ?>
                            <tr>
                                <td>
                                    <?php if (($rule['direction'] ?? '') === 'outgoing'): ?>
                                        <span class="admin-badge admin-badge--primary">Outgoing</span>
                                    <?php else: ?>
                                        <span class="admin-badge admin-badge--muted">Incoming</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $e((string) $rule['from_label']) ?> <code><?= $e((string) $rule['from_slug']) ?></code></td>
                                <td><?= $e((string) $rule['to_label']) ?> <code><?= $e((string) $rule['to_slug']) ?></code></td>
                                <td><code><?= $e((string) $rule['relation_type']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
<?php endif; ?>

<script>
(() => {
    const slugInput = document.getElementById('slug');
    const previewNode = document.getElementById('template-preview');
    const statusNode = document.getElementById('template-status');
    const viewTypeInputs = Array.from(document.querySelectorAll('input[name="view_type"]'));
    const templateExistsMap = <?= json_encode($templateExistsMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function currentViewType() {
        const checked = viewTypeInputs.find((input) => input.checked);
        return checked ? checked.value : 'single';
    }

    function buildTemplatePath() {
        const slug = (slugInput.value || '').trim();
        const safeSlug = slug === '' ? '{slug}' : slug;
        const prefix = currentViewType() === 'collection' ? 'templates/collections/' : 'templates/content/';
        return `${prefix}${safeSlug}.php`;
    }

    function updatePreview() {
        const path = buildTemplatePath();
        previewNode.innerHTML = `Template mapping: <code>${path}</code>`;

        const relativePath = path.replace('templates/', '');
        const exists = !!templateExistsMap[relativePath];

        statusNode.innerHTML = exists
            ? '<span class="admin-badge admin-badge--success">Exists</span>'
            : '<span class="admin-badge admin-badge--warning">Missing template</span>';
    }

    slugInput.addEventListener('input', updatePreview);
    viewTypeInputs.forEach((input) => input.addEventListener('change', updatePreview));
    updatePreview();
})();
</script>
