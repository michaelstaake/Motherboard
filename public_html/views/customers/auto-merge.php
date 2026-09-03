<?php 
$title = t('customers.auto_title') . ' - ' . ($companyName ?? APP_NAME);
ob_start(); 
?>

<div class="py-8">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?= t('customers.auto_title') ?></h1>
                <p class="mt-1 text-sm text-gray-600"><?= t('customers.auto_help') ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="<?= BASE_URL ?>/customers" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                    </svg>
                    <?= t('customers.back') ?>
                </a>
            </div>
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

    <?php if (empty($duplicateGroups)): ?>
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900"><?= t('customers.no_dupes') ?></h3>
            <p class="mt-1 text-sm text-gray-500"><?= t('customers.no_dupes_help') ?></p>
        </div>
    <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/customers/auto-merge">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="bg-white shadow overflow-hidden sm:rounded-md mb-6">
                <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        <?= t('customers.dup_groups', ['count' => count($duplicateGroups)]) ?>
                    </h3>
                    <div class="flex space-x-3">
                        <button type="button" onclick="toggleAll(true)" class="text-sm text-primary-600 hover:text-primary-500"><?= t('customers.select_all') ?></button>
                        <span class="text-gray-300">|</span>
                        <button type="button" onclick="toggleAll(false)" class="text-sm text-primary-600 hover:text-primary-500"><?= t('customers.deselect_all') ?></button>
                    </div>
                </div>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($duplicateGroups as $index => $group): ?>
                    <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="group_<?= $index ?>" name="merge_groups[]" value="<?= $group['ids'] ?>" type="checkbox" checked class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 w-full">
                                <label for="group_<?= $index ?>" class="font-medium text-gray-700">
                                    <?= htmlspecialchars($group['name']) ?> 
                                    <span class="text-gray-500 font-normal">(<?= htmlspecialchars($group['phone']) ?>)</span>
                                </label>
                                <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <?php foreach ($group['customers'] as $customer): ?>
                                    <div class="relative rounded-lg border border-gray-300 bg-white px-3 py-2 shadow-sm flex items-center space-x-3 hover:border-gray-400">
                                        <div class="flex-1 min-w-0">
                                            <a href="<?= BASE_URL ?>/customers/view/<?= $customer['id'] ?>" target="_blank" class="focus:outline-none">
                                                <span class="absolute inset-0" aria-hidden="true"></span>
                                                <p class="text-sm font-medium text-gray-900">
                                                    <?= t('common.id') ?>: #<?= $customer['id'] ?>
                                                </p>
                                                <p class="text-sm text-gray-500 truncate">
                                                    <?= t('common.created') ?>: <?= ldate($customer['created_at'], 'M j, Y') ?>
                                                </p>
                                                <p class="text-sm text-gray-500 truncate">
                                                    <?= t('customers.orders') ?>: <?= count($customer['work_orders'] ?? []) ?>
                                                </p>
                                                <?php if ($customer['email']): ?>
                                                <p class="text-sm text-gray-500 truncate">
                                                    <?= htmlspecialchars($customer['email']) ?>
                                                </p>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">
                                    <?= t('customers.keep_oldest') ?>
                                </p>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <?= t('customers.merge_selected') ?>
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function toggleAll(checked) {
    const checkboxes = document.querySelectorAll('input[name="merge_groups[]"]');
    checkboxes.forEach(cb => cb.checked = checked);
}
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>