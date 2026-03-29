<?php

declare(strict_types=1);

$layout = 'layouts/admin.php';
$adminPageTitle = 'Edit content';
$adminPageDescription = 'Update structured content and metadata.';
$patternBlocks = is_array($old['pattern_blocks'] ?? null) ? $old['pattern_blocks'] : [];
?>
<section class="admin__stack">
    <header class="admin-page__header">
        <h1>Edit Content Item</h1>
        <p><a href="/admin/content">Back to content list</a></p>
    </header>

    <?php if (isset($errors['general'])): ?>
        <p role="alert" style="color:#b42318;"><?= $e($errors['general']) ?></p>
    <?php endif; ?>

    <form class="admin-form" method="post" action="/admin/content/<?= $e((string) $contentItem->id()) ?>/edit" novalidate>
        <input type="hidden" name="_csrf_token" value="<?= $e((string) $request->attribute('csrf_token')) ?>">
        <label for="title">Title</label>
        <input id="title" type="text" name="title" value="<?= $e((string) ($old['title'] ?? '')) ?>" required>
        <?php if (isset($errors['title'])): ?>
            <p role="alert" style="color:#b42318;"><?= $e($errors['title']) ?></p>
        <?php endif; ?>

        <label for="slug">Slug</label>
        <input id="slug" type="text" name="slug" value="<?= $e((string) ($old['slug'] ?? '')) ?>" required>
        <?php if (isset($errors['slug'])): ?>
            <p role="alert" style="color:#b42318;"><?= $e($errors['slug']) ?></p>
        <?php endif; ?>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="">Select status</option>
            <option value="draft" <?= (($old['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
            <option value="published" <?= (($old['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
        </select>
        <?php if (isset($errors['status'])): ?>
            <p role="alert" style="color:#b42318;"><?= $e($errors['status']) ?></p>
        <?php endif; ?>

        <label for="content_type">Content Type</label>
        <select id="content_type" name="content_type" required>
            <option value="">Select content type</option>
            <?php foreach ($contentTypes as $type): ?>
                <option
                    value="<?= $e($type->name()) ?>"
                    <?= (($old['content_type'] ?? '') === $type->name()) ? 'selected' : '' ?>
                >
                    <?= $e($type->label()) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errors['content_type'])): ?>
            <p role="alert" style="color:#b42318;"><?= $e($errors['content_type']) ?></p>
        <?php endif; ?>

        <label for="body">Body</label>
        <textarea id="body" name="body" rows="8" disabled aria-describedby="body-help"><?= $e((string) ($old['body'] ?? '')) ?></textarea>
        <p id="body-help">Body persistence will be wired through field-value infrastructure in a future step.</p>

        <hr>
        <h2>SEO metadata</h2>

        <label for="meta_title">Meta title</label>
        <input id="meta_title" type="text" name="meta_title" value="<?= $e((string) ($old['meta_title'] ?? '')) ?>">

        <label for="meta_description">Meta description</label>
        <textarea id="meta_description" name="meta_description" rows="3"><?= $e((string) ($old['meta_description'] ?? '')) ?></textarea>

        <label for="og_image">Open Graph image</label>
        <input id="og_image" type="text" name="og_image" value="<?= $e((string) ($old['og_image'] ?? '')) ?>">

        <label for="canonical_url">Canonical URL</label>
        <input id="canonical_url" type="url" name="canonical_url" value="<?= $e((string) ($old['canonical_url'] ?? '')) ?>">

        <label for="noindex">
            <input id="noindex" type="checkbox" name="noindex" value="1" <?= (($old['noindex'] ?? false) === true) ? 'checked' : '' ?>>
            Mark this item as noindex
        </label>

        <hr>
        <h2>Pattern blocks</h2>
        <p>Add structured blocks in order. Use Move up/down for simple reordering.</p>

        <div id="pattern-blocks"></div>
        <p>
            <button type="button" id="add-pattern-block">Add pattern block</button>
        </p>

        <button type="submit">Save</button>
    </form>
</section>

<script>
const availablePatterns = <?= json_encode($availablePatterns, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const existingBlocks = <?= json_encode($patternBlocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const patternBlocksContainer = document.getElementById('pattern-blocks');
const addPatternButton = document.getElementById('add-pattern-block');

function fieldLabel(name) {
    return name.replace(/_/g, ' ').replace(/\b\w/g, (match) => match.toUpperCase());
}

function buildFieldInput(blockIndex, field, value) {
    const wrapper = document.createElement('p');
    const label = document.createElement('label');
    label.textContent = fieldLabel(field.name);

    const fieldName = `pattern_blocks[${blockIndex}][data][${field.name}]`;

    if (field.type === 'textarea') {
        const textarea = document.createElement('textarea');
        textarea.name = fieldName;
        textarea.rows = 4;
        textarea.value = value || '';
        wrapper.append(label, textarea);
        return wrapper;
    }

    if (field.type === 'text') {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = fieldName;
        input.value = value || '';
        wrapper.append(label, input);
        return wrapper;
    }

    // Future support placeholder for image fields.
    const note = document.createElement('small');
    note.textContent = `Field "${field.name}" (${field.type}) is reserved for future support.`;
    wrapper.append(label, note);

    return wrapper;
}

function renderBlockFields(blockElement, blockIndex, patternSlug, values) {
    const dataContainer = blockElement.querySelector('.pattern-block-data');
    dataContainer.innerHTML = '';

    const pattern = availablePatterns[patternSlug];

    if (!pattern) {
        return;
    }

    for (const field of pattern.fields) {
        const value = values && values[field.name] ? values[field.name] : '';
        dataContainer.appendChild(buildFieldInput(blockIndex, field, value));
    }
}

function reindexBlocks() {
    const blocks = Array.from(patternBlocksContainer.querySelectorAll('.pattern-block'));

    blocks.forEach((blockElement, index) => {
        blockElement.querySelector('.pattern-block-title').textContent = `Block ${index + 1}`;

        const select = blockElement.querySelector('.pattern-select');
        select.name = `pattern_blocks[${index}][pattern]`;

        const values = {};
        blockElement.querySelectorAll('.pattern-block-data [name]').forEach((input) => {
            const match = input.name.match(/\[data\]\[(.+)\]$/);
            if (match) {
                values[match[1]] = input.value;
            }
        });

        renderBlockFields(blockElement, index, select.value, values);
    });
}

function addBlock(initialBlock = null) {
    const blockElement = document.createElement('fieldset');
    blockElement.className = 'pattern-block';

    const heading = document.createElement('legend');
    heading.className = 'pattern-block-title';

    const actions = document.createElement('p');

    const moveUp = document.createElement('button');
    moveUp.type = 'button';
    moveUp.textContent = 'Move up';
    moveUp.addEventListener('click', () => {
        const previous = blockElement.previousElementSibling;
        if (previous) {
            patternBlocksContainer.insertBefore(blockElement, previous);
            reindexBlocks();
        }
    });

    const moveDown = document.createElement('button');
    moveDown.type = 'button';
    moveDown.textContent = 'Move down';
    moveDown.addEventListener('click', () => {
        const next = blockElement.nextElementSibling;
        if (next) {
            patternBlocksContainer.insertBefore(next, blockElement);
            reindexBlocks();
        }
    });

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.textContent = 'Remove';
    remove.addEventListener('click', () => {
        blockElement.remove();
        reindexBlocks();
    });

    actions.append(moveUp, moveDown, remove);

    const selectLabel = document.createElement('label');
    selectLabel.textContent = 'Pattern';

    const select = document.createElement('select');
    select.className = 'pattern-select';

    Object.values(availablePatterns).forEach((pattern) => {
        const option = document.createElement('option');
        option.value = pattern.key;
        option.textContent = pattern.name;
        select.appendChild(option);
    });

    const dataContainer = document.createElement('div');
    dataContainer.className = 'pattern-block-data';

    select.addEventListener('change', () => {
        const blockIndex = Array.from(patternBlocksContainer.children).indexOf(blockElement);
        renderBlockFields(blockElement, blockIndex, select.value, {});
    });

    blockElement.append(heading, actions, selectLabel, select, dataContainer);
    patternBlocksContainer.appendChild(blockElement);

    if (initialBlock && initialBlock.pattern && availablePatterns[initialBlock.pattern]) {
        select.value = initialBlock.pattern;
    }

    reindexBlocks();

    const blockIndex = Array.from(patternBlocksContainer.children).indexOf(blockElement);
    renderBlockFields(blockElement, blockIndex, select.value, initialBlock ? (initialBlock.data || {}) : {});
}

if (Object.keys(availablePatterns).length === 0) {
    addPatternButton.disabled = true;
    addPatternButton.textContent = 'No valid patterns available';
} else if (Array.isArray(existingBlocks) && existingBlocks.length > 0) {
    existingBlocks.forEach((block) => addBlock(block));
} else {
    addBlock();
}

addPatternButton.addEventListener('click', () => addBlock());
</script>
