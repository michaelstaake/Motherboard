<?php
$title = t('settings.title') . ' - ' . ($companyName ?? APP_NAME);
ob_start();

$tabLabels = [
    'general' => t('settings.tab.general'),
    'printout' => t('settings.tab.printout'),
    'security' => t('settings.tab.security'),
    'localization' => t('settings.tab.localization'),
    'attachments' => t('settings.tab.attachments'),
    'modules' => t('settings.tab.modules'),
    'users' => t('settings.tab.users'),
];

$tabHelp = [
    'general' => t('settings.company_help'),
    'printout' => t('settings.printout_help'),
    'security' => t('settings.security_help'),
    'localization' => t('settings.localization_help'),
    'attachments' => t('settings.attachments_help'),
    'modules' => t('modules.subtitle'),
    'users' => t('users.subtitle'),
];
?>

<div class="py-8" id="settingsPage">
    <div class="mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= t('settings.title') ?></h1>
            <p
                class="mt-1 text-sm text-gray-600"
                title="<?= htmlspecialchars(t('settings.php_version') . ': ' . ($phpVersion ?? t('common.unknown'))) ?>">
                <span class="text-gray-900"><?= htmlspecialchars(APP_NAME ?? 'Motherboard') ?></span><span class="text-gray-900"> <?= htmlspecialchars($appVersion ?? t('common.unknown')) ?></span>
            </p>
        </div>
    </div>

    <?php if (isset($error) && $error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($message) && $message): ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-600"><?= htmlspecialchars($message) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="border-b border-gray-200 mb-6">
        <div class="sm:hidden">
            <label for="settings-tab-select" class="sr-only"><?= t('settings.title') ?></label>
            <div class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500"><?= t('settings.title') ?></p>
                <select
                    id="settings-tab-select"
                    class="mt-2 block w-full rounded-md border-gray-300 py-3 pl-4 pr-10 text-base focus:border-primary-500 focus:ring-primary-500"
                    aria-label="<?= t('settings.title') ?>">
                    <?php foreach ($tabs as $tabId): ?>
                        <option value="<?= htmlspecialchars($tabId) ?>" <?= $activeTab === $tabId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tabLabels[$tabId] ?? $tabId) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <nav class="-mb-px hidden overflow-x-auto sm:flex sm:space-x-6" aria-label="Settings tabs">
            <?php foreach ($tabs as $tabId): ?>
                <button type="button"
                    class="settings-tab-btn whitespace-nowrap rounded-t-md px-2 py-3 border-b-2 font-medium text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 <?= $activeTab === $tabId ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>"
                    data-tab="<?= htmlspecialchars($tabId) ?>"
                    aria-pressed="<?= $activeTab === $tabId ? 'true' : 'false' ?>"
                    data-settings-nav>
                    <?= htmlspecialchars($tabLabels[$tabId] ?? $tabId) ?>
                </button>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php foreach ($tabs as $tabId): ?>
        <div class="settings-tab-panel bg-white shadow rounded-lg <?= $activeTab !== $tabId ? 'hidden' : '' ?>" data-tab-panel="<?= htmlspecialchars($tabId) ?>">
            <?php if (!empty($tabHelp[$tabId])): ?>
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($tabLabels[$tabId] ?? $tabId) ?></h2>
                        <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($tabHelp[$tabId]) ?></p>
                    </div>
                    <?php if ($tabId === 'users'): ?>
                        <button type="button" onclick="showCreateModal()" class="inline-flex flex-shrink-0 items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            <?= t('users.add') ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php include ROOT_PATH . '/views/settings/partials/tab-' . $tabId . '.php'; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Unsaved changes modal -->
<div id="unsavedChangesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900"><?= t('settings.unsaved_title') ?></h3>
                    <p class="mt-2 text-sm text-gray-500"><?= t('settings.unsaved_body') ?></p>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-2">
                <button type="button" id="unsavedSaveBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:w-auto sm:text-sm">
                    <?= t('settings.unsaved_save') ?>
                </button>
                <button type="button" id="unsavedDiscardBtn" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">
                    <?= t('settings.unsaved_discard') ?>
                </button>
                <button type="button" id="unsavedCancelBtn" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:w-auto sm:text-sm">
                    <?= t('common.cancel') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showPhoneFormatExample() {
    const format = document.getElementById('phone_number_format').value;
    const exampleText = document.getElementById('example_text');
    const patternText = document.getElementById('pattern_text');
    if (!exampleText || !patternText) return;

    switch(format) {
        case 'default':
            exampleText.textContent = <?= json_encode(t('settings.phone_ex_default')) ?>;
            patternText.textContent = <?= json_encode(t('settings.phone_pat_default')) ?>;
            break;
        case 'usa_format_a':
            exampleText.textContent = '(555) 555-5555';
            patternText.textContent = <?= json_encode(t('settings.phone_pat_a')) ?>;
            break;
        case 'usa_format_b':
            exampleText.textContent = '555-555-5555';
            patternText.textContent = <?= json_encode(t('settings.phone_pat_b')) ?>;
            break;
    }
}

function initAttachmentExtensions() {
    const hidden = document.getElementById('attachment_allowed_extensions');
    const list = document.getElementById('attachment_extension_list');
    const input = document.getElementById('attachment_extension_input');
    const addButton = document.getElementById('attachment_extension_add');
    const modal = document.getElementById('wildcardWarningModal');
    const confirmButton = document.getElementById('wildcardWarningConfirm');
    const cancelButton = document.getElementById('wildcardWarningCancel');
    if (!hidden || !list || !input || !addButton) {
        return;
    }

    if (hidden.dataset.initialized === '1') {
        hidden.dispatchEvent(new CustomEvent('attachment-extensions-reset'));
        return;
    }
    hidden.dataset.initialized = '1';

    let extensions = hidden.value.split(',').map(function(item) {
        return item.trim().toLowerCase();
    }).filter(Boolean);

    function sync() {
        hidden.value = extensions.join(',');
        list.innerHTML = '';
        extensions.forEach(function(ext) {
            const tag = document.createElement('span');
            tag.className = ext === '%'
                ? 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800'
                : 'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
            tag.textContent = ext === '%' ? <?= json_encode(t('settings.attachment_all_types')) ?> : ext;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ml-1 text-current hover:opacity-70';
            remove.setAttribute('aria-label', <?= json_encode(t('common.delete')) ?>);
            remove.innerHTML = '&times;';
            remove.addEventListener('click', function() {
                extensions = extensions.filter(function(item) { return item !== ext; });
                sync();
                const form = document.getElementById('attachmentSettingsForm');
                if (form) form.dispatchEvent(new Event('change', { bubbles: true }));
            });
            tag.appendChild(remove);
            list.appendChild(tag);
        });
    }

    hidden.addEventListener('attachment-extensions-reset', function() {
        extensions = hidden.value.split(',').map(function(item) {
            return item.trim().toLowerCase();
        }).filter(Boolean);
        sync();
    });

    function normalize(value) {
        value = (value || '').trim().toLowerCase().replace(/^\./, '');
        return value;
    }

    function addExtension(value) {
        const ext = normalize(value);
        if (!ext) {
            return;
        }
        if (ext !== '%' && !/^[a-z0-9]{1,16}$/.test(ext)) {
            return;
        }
        if (extensions.indexOf(ext) !== -1) {
            input.value = '';
            return;
        }
        extensions.push(ext);
        input.value = '';
        sync();
        const form = document.getElementById('attachmentSettingsForm');
        if (form) form.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function openWildcardModal() {
        modal.classList.remove('hidden');
    }

    function closeWildcardModal() {
        modal.classList.add('hidden');
    }

    addButton.addEventListener('click', function() {
        const ext = normalize(input.value);
        if (ext === '%') {
            openWildcardModal();
            return;
        }
        addExtension(ext);
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addButton.click();
        }
    });

    confirmButton?.addEventListener('click', function() {
        addExtension('%');
        closeWildcardModal();
    });
    cancelButton?.addEventListener('click', closeWildcardModal);
    modal?.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeWildcardModal();
        }
    });

    sync();
}

(function() {
    const settingsBase = <?= json_encode(BASE_URL . '/settings') ?>;
    let currentTab = <?= json_encode($activeTab) ?>;
    let pendingNavigation = null;
    let allowUnload = false;
    const formSnapshots = new Map();
    const unsavedModal = document.getElementById('unsavedChangesModal');

    function serializeForm(form) {
        const data = new FormData(form);
        const pairs = [];
        for (const [key, value] of data.entries()) {
            pairs.push(key + '=' + value);
        }
        return pairs.sort().join('&');
    }

    function snapshotForm(form) {
        formSnapshots.set(form, serializeForm(form));
    }

    function isFormDirty(form) {
        if (!formSnapshots.has(form)) {
            snapshotForm(form);
        }
        return serializeForm(form) !== formSnapshots.get(form);
    }

    function getDirtyFormForTab(tabId) {
        const form = document.querySelector('.settings-saveable-form[data-tab="' + tabId + '"]');
        return form && isFormDirty(form) ? form : null;
    }

    function markFormDirty(form) {
        // Dirty state is detected by comparing current serialization to the saved snapshot.
    }

    function resetForm(form) {
        if (!formSnapshots.has(form)) return;
        const snapshot = formSnapshots.get(form);
        const params = new URLSearchParams(snapshot);
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(function(field) {
            const name = field.name;
            if (!name) return;
            if (field.type === 'checkbox') {
                field.checked = params.get(name) === field.value;
            } else if (field.type === 'radio') {
                field.checked = params.get(name) === field.value;
            } else {
                const val = params.get(name);
                if (val !== null) {
                    field.value = val;
                }
            }
        });
        if (form.id === 'attachmentSettingsForm') {
            const hidden = document.getElementById('attachment_allowed_extensions');
            if (hidden) {
                hidden.dispatchEvent(new CustomEvent('attachment-extensions-reset'));
            }
        }
        if (document.getElementById('phone_number_format')) {
            showPhoneFormatExample();
        }
        snapshotForm(form);
    }

    function showTab(tabId, updateUrl) {
        currentTab = tabId;
        document.querySelectorAll('.settings-tab-panel').forEach(function(panel) {
            const isActive = panel.getAttribute('data-tab-panel') === tabId;
            panel.classList.toggle('hidden', !isActive);
        });
        document.querySelectorAll('.settings-tab-btn').forEach(function(btn) {
            const isActive = btn.getAttribute('data-tab') === tabId;
            btn.classList.toggle('border-primary-500', isActive);
            btn.classList.toggle('text-primary-600', isActive);
            btn.classList.toggle('border-transparent', !isActive);
            btn.classList.toggle('text-gray-500', !isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            if (isActive) {
                btn.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        });
        const tabSelect = document.getElementById('settings-tab-select');
        if (tabSelect && tabSelect.value !== tabId) {
            tabSelect.value = tabId;
        }
        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            if (tabId !== 'users') {
                url.searchParams.delete('page');
            }
            history.replaceState(null, '', url.toString());
        }
    }

    function navigateTo(url) {
        allowUnload = true;
        window.location.href = url;
    }

    function openUnsavedModal(onSave, onDiscard) {
        pendingNavigation = { onSave: onSave, onDiscard: onDiscard };
        unsavedModal.classList.remove('hidden');
    }

    function closeUnsavedModal() {
        pendingNavigation = null;
        unsavedModal.classList.add('hidden');
    }

    function attemptNavigation(targetTab, url) {
        const dirtyForm = getDirtyFormForTab(currentTab);
        if (!dirtyForm) {
            if (url) {
                navigateTo(url);
            } else if (targetTab) {
                showTab(targetTab, true);
            }
            return;
        }

        openUnsavedModal(
            function() {
                dirtyForm.requestSubmit();
            },
            function() {
                resetForm(dirtyForm);
                if (url) {
                    navigateTo(url);
                } else if (targetTab) {
                    showTab(targetTab, true);
                }
            }
        );
    }

    document.querySelectorAll('.settings-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const tabId = btn.getAttribute('data-tab');
            if (tabId === currentTab) return;
            attemptNavigation(tabId, null);
        });
    });

    const tabSelect = document.getElementById('settings-tab-select');
    if (tabSelect) {
        tabSelect.addEventListener('change', function() {
            const tabId = tabSelect.value;
            if (tabId === currentTab) return;
            attemptNavigation(tabId, null);
        });
    }

    document.querySelectorAll('.settings-saveable-form').forEach(function(form) {
        snapshotForm(form);
        form.addEventListener('input', function() { markFormDirty(form); });
        form.addEventListener('change', function() { markFormDirty(form); });
        form.addEventListener('submit', function() {
            allowUnload = true;
            snapshotForm(form);
        });
    });

    document.getElementById('unsavedSaveBtn').addEventListener('click', function() {
        if (pendingNavigation && pendingNavigation.onSave) {
            pendingNavigation.onSave();
        }
        closeUnsavedModal();
    });

    document.getElementById('unsavedDiscardBtn').addEventListener('click', function() {
        if (pendingNavigation && pendingNavigation.onDiscard) {
            pendingNavigation.onDiscard();
        }
        closeUnsavedModal();
    });

    document.getElementById('unsavedCancelBtn').addEventListener('click', closeUnsavedModal);

    unsavedModal.addEventListener('click', function(e) {
        if (e.target === unsavedModal) {
            closeUnsavedModal();
        }
    });

    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link || !link.href) return;
        if (link.hasAttribute('data-settings-nav')) return;
        if (!document.getElementById('settingsPage')) return;

        const url = new URL(link.href, window.location.origin);
        if (url.origin !== window.location.origin) return;

        const isSamePageTab = url.pathname.endsWith('/settings') && url.searchParams.get('tab');
        if (isSamePageTab) {
            e.preventDefault();
            const tabId = url.searchParams.get('tab');
            if (tabId === currentTab && url.search === window.location.search) {
                return;
            }
            if (tabId !== currentTab) {
                attemptNavigation(tabId, null);
            } else {
                attemptNavigation(null, link.href);
            }
            return;
        }

        const currentPath = window.location.pathname;
        if (url.pathname === currentPath && url.search === window.location.search) return;

        const dirtyForm = getDirtyFormForTab(currentTab);
        if (!dirtyForm) return;

        e.preventDefault();
        openUnsavedModal(
            function() { dirtyForm.requestSubmit(); },
            function() {
                resetForm(dirtyForm);
                navigateTo(link.href);
            }
        );
    }, true);

    window.addEventListener('beforeunload', function(e) {
        if (allowUnload) return;
        const dirtyForm = getDirtyFormForTab(currentTab);
        if (dirtyForm) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        showPhoneFormatExample();
        initAttachmentExtensions();
    });
})();
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
