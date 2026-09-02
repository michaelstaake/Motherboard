<?php
$title = t('modules.title') . ' - ' . ($companyName ?? APP_NAME);
ob_start();
?>

<div class="py-8">
    <div class="mb-6 sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= t('modules.title') ?></h1>
            <p class="mt-1 text-sm text-gray-600"><?= t('modules.subtitle') ?></p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="<?= BASE_URL ?>/settings" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <?= t('modules.back_to_settings') ?>
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
            <p class="text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
            <p class="text-sm text-green-600"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div class="space-y-4">
        <?php if (empty($modules)): ?>
            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-sm text-gray-600"><?= t('modules.none') ?></p>
            </div>
        <?php endif; ?>

        <?php foreach ($modules as $module): ?>
            <?php
                $nameKey = 'module.' . $module['slug'] . '.name';
                $descKey = 'module.' . $module['slug'] . '.description';
                $name = t($nameKey);
                $desc = t($descKey);
                if ($name === $nameKey) {
                    $name = $module['name'];
                }
                if ($desc === $descKey) {
                    $desc = $module['description'];
                }
            ?>
            <div class="bg-white shadow rounded-lg px-6 py-4 flex items-start justify-between gap-4">
                <div class="flex items-start gap-4 min-w-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($name) ?></h2>
                        <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($desc) ?></p>
                        <?php if (!empty($module['version'])): ?>
                            <p class="mt-1 text-xs text-gray-400">
                                <?= t('modules.version') ?> <?= htmlspecialchars($module['version']) ?>
                                <?php if (!empty($module['author'])): ?>
                                    <?= t('common.by', ['name' => htmlspecialchars($module['author'])]) ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    <?php if ($module['enabled'] && $module['has_settings']): ?>
                        <a href="<?= BASE_URL ?>/module-manager/<?= urlencode($module['slug']) ?>/settings" title="<?= htmlspecialchars(t('modules.configure')) ?>" class="p-2 rounded-md text-gray-500 hover:text-primary-600 hover:bg-gray-50">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                    <?php endif; ?>
                    <form method="POST" action="<?= BASE_URL ?>/module-manager">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="slug" value="<?= htmlspecialchars($module['slug']) ?>">
                        <input type="hidden" name="enable" value="<?= $module['enabled'] ? '0' : '1' ?>">
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 border text-sm font-medium rounded-md <?= $module['enabled'] ? 'border-red-200 text-red-700 bg-red-50 hover:bg-red-100' : 'border-transparent text-white bg-primary-600 hover:bg-primary-700' ?>">
                            <?= $module['enabled'] ? t('modules.disable') : t('modules.enable') ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($skipped)): ?>
        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-yellow-800"><?= t('modules.skipped') ?></h3>
            <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                <?php foreach ($skipped as $item): ?>
                    <li><?= htmlspecialchars($item['slug']) ?>: <?= htmlspecialchars($item['reason']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
