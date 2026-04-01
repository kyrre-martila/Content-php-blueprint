<?php

declare(strict_types=1);

$old = is_array($old ?? null) ? $old : [];
$errors = is_array($errors ?? null) ? $errors : [];
$slug = (string) ($old['slug'] ?? '');
$selectedViewType = (string) ($old['view_type'] ?? 'single');
$selectedGroupIds = array_map(
    static fn (mixed $value): int => is_scalar($value) && is_numeric((string) $value) ? (int) $value : 0,
    is_array($old['allowed_category_group_ids'] ?? null) ? $old['allowed_category_group_ids'] : []
);
$templateExistsMap = is_array($templateExistsMap ?? null) ? $templateExistsMap : [];
$categoryGroups = is_array($categoryGroups ?? null) ? $categoryGroups : [];
$outgoingRelationshipRules = is_array($outgoingRelationshipRules ?? null) ? $outgoingRelationshipRules : [];
$incomingRelationshipRules = is_array($incomingRelationshipRules ?? null) ? $incomingRelationshipRules : [];
$fieldRows = is_array($old['fields'] ?? null) ? $old['fields'] : [];
if ($fieldRows === []) {
    $fieldRows = [[
        'name' => '',
        'label' => '',
        'field_type' => 'text',
        'is_required' => false,
        'default_value' => null,
        'placeholder' => null,
        'options_text' => null,
        'min_value' => null,
        'max_value' => null,
        'allowed_types_text' => null,
    ]];
}
$supportedFieldTypes = ['text', 'textarea', 'richtext', 'number', 'boolean', 'date', 'image', 'file', 'select'];

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

        <div class="admin-form__group">
            <label class="admin-form__label" for="allowed_category_group_ids">Allowed Category Groups</label>
            <select class="admin-form__input" id="allowed_category_group_ids" name="allowed_category_group_ids[]" multiple size="6">
                <?php foreach ($categoryGroups as $group): ?>
                    <?php $groupId = $group->id(); ?>
                    <?php if (!is_int($groupId)): continue; endif; ?>
                    <option value="<?= $e((string) $groupId) ?>" <?= in_array($groupId, $selectedGroupIds, true) ? 'selected' : '' ?>>
                        <?= $e($group->name()) ?> (<?= $e($group->slug()->value()) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="admin-form__help">Hold Ctrl (Windows) or Command (Mac) to select multiple groups.</p>
        </div>

        <section class="admin-form__group admin-field-schema" aria-labelledby="field-schema-title">
            <div class="admin-field-schema__header">
                <span class="admin-form__label" id="field-schema-title">Field schema</span>
                <button class="admin-action admin-action--secondary" type="button" data-field-schema-add>Add field</button>
            </div>
            <p class="admin-form__help">Define custom fields. Use arrows to reorder fields.</p>
            <div class="admin-field-schema__list" data-field-schema-list>
                <?php foreach ($fieldRows as $index => $fieldRow): ?>
                    <?php $fieldType = (string) ($fieldRow['field_type'] ?? 'text'); ?>
                    <fieldset class="admin-panel admin-card admin-field-schema__item" data-field-row>
                        <legend class="admin-field-schema__legend">Field <?= $e((string) ($index + 1)) ?></legend>
                        <div class="admin-field-schema__actions">
                            <button type="button" class="admin-action admin-action--secondary" data-action="move-up">↑ Move up</button>
                            <button type="button" class="admin-action admin-action--secondary" data-action="move-down">↓ Move down</button>
                            <button type="button" class="admin-action admin-action--danger" data-action="remove">Remove</button>
                        </div>

                        <div class="admin-form__group">
                            <label class="admin-form__label" for="field_name_<?= $e((string) $index) ?>">Name</label>
                            <input class="admin-form__input" id="field_name_<?= $e((string) $index) ?>" name="field_name[]" type="text" value="<?= $e((string) ($fieldRow['name'] ?? '')) ?>">
                            <?php if (isset($errors['fields.' . $index . '.name'])): ?>
                                <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['fields.' . $index . '.name']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="admin-form__group">
                            <label class="admin-form__label" for="field_label_<?= $e((string) $index) ?>">Label</label>
                            <input class="admin-form__input" id="field_label_<?= $e((string) $index) ?>" name="field_label[]" type="text" value="<?= $e((string) ($fieldRow['label'] ?? '')) ?>">
                            <?php if (isset($errors['fields.' . $index . '.label'])): ?>
                                <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['fields.' . $index . '.label']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="admin-form__group">
                            <label class="admin-form__label" for="field_type_<?= $e((string) $index) ?>">Field Type</label>
                            <select class="admin-form__input" id="field_type_<?= $e((string) $index) ?>" name="field_type[]" data-field-type>
                                <?php foreach ($supportedFieldTypes as $supportedFieldType): ?>
                                    <option value="<?= $e($supportedFieldType) ?>" <?= $supportedFieldType === $fieldType ? 'selected' : '' ?>><?= $e($supportedFieldType) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['fields.' . $index . '.field_type'])): ?>
                                <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['fields.' . $index . '.field_type']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="admin-form__group">
                            <label class="admin-form__label" for="field_default_<?= $e((string) $index) ?>">Default value</label>
                            <input class="admin-form__input" id="field_default_<?= $e((string) $index) ?>" name="field_default_value[]" type="text" value="<?= $e((string) ($fieldRow['default_value'] ?? '')) ?>">
                        </div>

                        <div class="admin-form__group" data-setting-group="placeholder">
                            <label class="admin-form__label" for="field_placeholder_<?= $e((string) $index) ?>">Placeholder</label>
                            <input class="admin-form__input" id="field_placeholder_<?= $e((string) $index) ?>" name="field_placeholder[]" type="text" value="<?= $e((string) ($fieldRow['placeholder'] ?? '')) ?>">
                        </div>

                        <div class="admin-form__group" data-setting-group="options">
                            <label class="admin-form__label" for="field_options_<?= $e((string) $index) ?>">Select options</label>
                            <textarea class="admin-form__input" id="field_options_<?= $e((string) $index) ?>" name="field_options[]" rows="3"><?= $e((string) ($fieldRow['options_text'] ?? '')) ?></textarea>
                            <p class="admin-form__help">One option per line.</p>
                            <?php if (isset($errors['fields.' . $index . '.options_text'])): ?>
                                <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['fields.' . $index . '.options_text']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="admin-field-schema__pair" data-setting-group="number_range">
                            <div class="admin-form__group">
                                <label class="admin-form__label" for="field_min_<?= $e((string) $index) ?>">Min</label>
                                <input class="admin-form__input" id="field_min_<?= $e((string) $index) ?>" name="field_min[]" type="text" value="<?= $e((string) ($fieldRow['min_value'] ?? '')) ?>">
                                <?php if (isset($errors['fields.' . $index . '.min_value'])): ?>
                                    <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['fields.' . $index . '.min_value']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="admin-form__group">
                                <label class="admin-form__label" for="field_max_<?= $e((string) $index) ?>">Max</label>
                                <input class="admin-form__input" id="field_max_<?= $e((string) $index) ?>" name="field_max[]" type="text" value="<?= $e((string) ($fieldRow['max_value'] ?? '')) ?>">
                                <?php if (isset($errors['fields.' . $index . '.max_value'])): ?>
                                    <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['fields.' . $index . '.max_value']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="admin-form__group" data-setting-group="allowed_types">
                            <label class="admin-form__label" for="field_allowed_types_<?= $e((string) $index) ?>">Allowed extensions/mime hints</label>
                            <input class="admin-form__input" id="field_allowed_types_<?= $e((string) $index) ?>" name="field_allowed_types[]" type="text" value="<?= $e((string) ($fieldRow['allowed_types_text'] ?? '')) ?>">
                            <p class="admin-form__help">Comma-separated list (e.g. <code>jpg,png,image/webp</code>).</p>
                        </div>

                        <?php if (isset($errors['fields.' . $index . '.settings'])): ?>
                            <p class="admin-form__help admin-form__help--danger" role="alert"><?= $e((string) $errors['fields.' . $index . '.settings']) ?></p>
                        <?php endif; ?>

                        <div class="admin-form__group">
                            <label class="admin-form__option" for="field_required_<?= $e((string) $index) ?>">
                                <input id="field_required_<?= $e((string) $index) ?>" type="checkbox" name="field_required[<?= $e((string) $index) ?>]" value="1" <?= (($fieldRow['is_required'] ?? false) === true || (string) ($fieldRow['is_required'] ?? '') === '1') ? 'checked' : '' ?> >
                                <span>Required</span>
                            </label>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if ($outgoingRelationshipRules !== [] || $incomingRelationshipRules !== []): ?>
            <div class="admin-form__group">
                <span class="admin-form__label">Relationship Rules Summary</span>
                <?php if ($outgoingRelationshipRules !== []): ?>
                    <p class="admin-form__help"><strong>Outgoing</strong></p>
                    <ul class="admin-list-block">
                        <?php foreach ($outgoingRelationshipRules as $rule): ?>
                            <li class="admin-list-item">
                                <code><?= $e((string) ($rule['from_type'] ?? '')) ?></code>
                                &nbsp;→&nbsp;
                                <code><?= $e((string) ($rule['to_type'] ?? '')) ?></code>
                                &nbsp;=&nbsp;
                                <code><?= $e((string) ($rule['relation_type'] ?? '')) ?></code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($incomingRelationshipRules !== []): ?>
                    <p class="admin-form__help"><strong>Incoming</strong></p>
                    <ul class="admin-list-block">
                        <?php foreach ($incomingRelationshipRules as $rule): ?>
                            <li class="admin-list-item">
                                <code><?= $e((string) ($rule['from_type'] ?? '')) ?></code>
                                &nbsp;→&nbsp;
                                <code><?= $e((string) ($rule['to_type'] ?? '')) ?></code>
                                &nbsp;=&nbsp;
                                <code><?= $e((string) ($rule['relation_type'] ?? '')) ?></code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="admin-form__actions">
            <a class="admin-action admin-action--secondary" href="/admin/content-types">Cancel</a>
            <button class="admin-action admin-action--primary" type="submit"><?= $e((string) $submitLabel) ?></button>
        </div>
    </form>
</article>

<template id="field-schema-row-template">
    <fieldset class="admin-panel admin-card admin-field-schema__item" data-field-row>
        <legend class="admin-field-schema__legend">Field</legend>
        <div class="admin-field-schema__actions">
            <button type="button" class="admin-action admin-action--secondary" data-action="move-up">↑ Move up</button>
            <button type="button" class="admin-action admin-action--secondary" data-action="move-down">↓ Move down</button>
            <button type="button" class="admin-action admin-action--danger" data-action="remove">Remove</button>
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label">Name</label>
            <input class="admin-form__input" name="field_name[]" type="text" value="">
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label">Label</label>
            <input class="admin-form__input" name="field_label[]" type="text" value="">
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label">Field Type</label>
            <select class="admin-form__input" name="field_type[]" data-field-type>
                <?php foreach ($supportedFieldTypes as $supportedFieldType): ?>
                    <option value="<?= $e($supportedFieldType) ?>"><?= $e($supportedFieldType) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="admin-form__group">
            <label class="admin-form__label">Default value</label>
            <input class="admin-form__input" name="field_default_value[]" type="text" value="">
        </div>

        <div class="admin-form__group" data-setting-group="placeholder">
            <label class="admin-form__label">Placeholder</label>
            <input class="admin-form__input" name="field_placeholder[]" type="text" value="">
        </div>

        <div class="admin-form__group" data-setting-group="options">
            <label class="admin-form__label">Select options</label>
            <textarea class="admin-form__input" name="field_options[]" rows="3"></textarea>
            <p class="admin-form__help">One option per line.</p>
        </div>

        <div class="admin-field-schema__pair" data-setting-group="number_range">
            <div class="admin-form__group">
                <label class="admin-form__label">Min</label>
                <input class="admin-form__input" name="field_min[]" type="text" value="">
            </div>
            <div class="admin-form__group">
                <label class="admin-form__label">Max</label>
                <input class="admin-form__input" name="field_max[]" type="text" value="">
            </div>
        </div>

        <div class="admin-form__group" data-setting-group="allowed_types">
            <label class="admin-form__label">Allowed extensions/mime hints</label>
            <input class="admin-form__input" name="field_allowed_types[]" type="text" value="">
            <p class="admin-form__help">Comma-separated list (e.g. <code>jpg,png,image/webp</code>).</p>
        </div>

        <div class="admin-form__group">
            <label class="admin-form__option">
                <input type="checkbox" value="1" data-required-checkbox>
                <span>Required</span>
            </label>
            <input type="hidden" name="field_required[]" value="0" data-required-hidden>
        </div>
    </fieldset>
</template>

<script>
(() => {
    const slugInput = document.getElementById('slug');
    const previewNode = document.getElementById('template-preview');
    const statusNode = document.getElementById('template-status');
    const viewTypeInputs = Array.from(document.querySelectorAll('input[name="view_type"]'));
    const templateExistsMap = <?= json_encode($templateExistsMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const fieldList = document.querySelector('[data-field-schema-list]');
    const addFieldButton = document.querySelector('[data-field-schema-add]');
    const template = document.getElementById('field-schema-row-template');

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

    function refreshFieldOrder() {
        const rows = Array.from(fieldList.querySelectorAll('[data-field-row]'));

        rows.forEach((row, index) => {
            const legend = row.querySelector('.admin-field-schema__legend');
            if (legend) {
                legend.textContent = `Field ${index + 1}`;
            }

            const requiredHidden = row.querySelector('[data-required-hidden]');
            const requiredCheckbox = row.querySelector('[data-required-checkbox], input[name^="field_required["]');

            if (requiredCheckbox && requiredCheckbox.name !== '') {
                requiredCheckbox.name = `field_required[${index}]`;
            }

            if (requiredHidden) {
                requiredHidden.name = `field_required[${index}]`;
                requiredHidden.value = requiredCheckbox && requiredCheckbox.checked ? '1' : '0';
            }
        });
    }

    function updateSettingsVisibility(row) {
        const typeInput = row.querySelector('[data-field-type]');
        const fieldType = typeInput ? typeInput.value : 'text';

        const placeholderGroup = row.querySelector('[data-setting-group="placeholder"]');
        const optionsGroup = row.querySelector('[data-setting-group="options"]');
        const numberGroup = row.querySelector('[data-setting-group="number_range"]');
        const fileGroup = row.querySelector('[data-setting-group="allowed_types"]');

        if (placeholderGroup) {
            placeholderGroup.hidden = !['text', 'textarea', 'richtext'].includes(fieldType);
        }

        if (optionsGroup) {
            optionsGroup.hidden = fieldType !== 'select';
        }

        if (numberGroup) {
            numberGroup.hidden = fieldType !== 'number';
        }

        if (fileGroup) {
            fileGroup.hidden = !['file', 'image'].includes(fieldType);
        }
    }

    function bindFieldRow(row) {
        const typeInput = row.querySelector('[data-field-type]');
        if (typeInput) {
            typeInput.addEventListener('change', () => updateSettingsVisibility(row));
        }

        const requiredCheckbox = row.querySelector('[data-required-checkbox], input[name^="field_required["]');
        const requiredHidden = row.querySelector('[data-required-hidden]');
        if (requiredCheckbox && requiredHidden) {
            requiredCheckbox.addEventListener('change', () => {
                requiredHidden.value = requiredCheckbox.checked ? '1' : '0';
            });
            requiredHidden.value = requiredCheckbox.checked ? '1' : '0';
        }

        updateSettingsVisibility(row);
    }

    function addFieldRow() {
        if (!(template instanceof HTMLTemplateElement)) {
            return;
        }

        const fragment = template.content.cloneNode(true);
        const newRow = fragment.querySelector('[data-field-row]');
        if (!newRow) {
            return;
        }

        fieldList.appendChild(fragment);
        bindFieldRow(fieldList.lastElementChild);
        refreshFieldOrder();
    }

    function onFieldActions(event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const action = target.getAttribute('data-action');
        if (action === null) {
            return;
        }

        const row = target.closest('[data-field-row]');
        if (!(row instanceof HTMLElement)) {
            return;
        }

        if (action === 'remove') {
            row.remove();
            if (fieldList.querySelectorAll('[data-field-row]').length === 0) {
                addFieldRow();
            }
            refreshFieldOrder();
            return;
        }

        if (action === 'move-up' && row.previousElementSibling) {
            row.parentElement.insertBefore(row, row.previousElementSibling);
            refreshFieldOrder();
            return;
        }

        if (action === 'move-down' && row.nextElementSibling) {
            row.parentElement.insertBefore(row.nextElementSibling, row);
            refreshFieldOrder();
        }
    }

    slugInput.addEventListener('input', updatePreview);
    viewTypeInputs.forEach((input) => input.addEventListener('change', updatePreview));
    updatePreview();

    Array.from(fieldList.querySelectorAll('[data-field-row]')).forEach((row) => bindFieldRow(row));
    refreshFieldOrder();

    addFieldButton.addEventListener('click', addFieldRow);
    fieldList.addEventListener('click', onFieldActions);
})();
</script>
