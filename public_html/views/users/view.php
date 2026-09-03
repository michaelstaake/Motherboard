<?php 
$title = $user['username'] . ' - ' . t('users.title') . ' - ' . ($companyName ?? APP_NAME);
ob_start(); 
?>

<div class="py-8">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($user['username']) ?></h1>
                <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <?php if ($_SESSION['user_group'] === 'Admin'): ?>
            <div>
                <a href="<?= BASE_URL ?>/settings?tab=users" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" />
                    </svg>
                    <?= t('users.back') ?>
                </a>
            </div>
            <?php endif; ?>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Details -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900"><?= t('users.info') ?></h2>
                </div>
                
                <?php if ($_SESSION['user_group'] === 'Admin'): ?>
                <!-- Admin View - Full Edit Form -->
                <form method="POST" class="px-6 py-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700"><?= t('users.username') ?> *</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" readonly class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm bg-gray-100 cursor-not-allowed sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500"><?= t('users.username_locked') ?></p>
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700"><?= t('users.full_name') ?></label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                            <p class="mt-1 text-xs text-gray-500"><?= t('users.username_blank') ?></p>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700"><?= t('users.email') ?> *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        </div>

                        <div>
                            <label for="user_group" class="block text-sm font-medium text-gray-700"><?= t('users.role') ?> *</label>
                            <select id="user_group" name="user_group" required <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?> class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm <?= $user['id'] == $_SESSION['user_id'] ? 'bg-gray-100' : 'bg-white' ?>">
                                <option value="Admin" <?= $user['user_group'] === 'Admin' ? 'selected' : '' ?>><?= t('group.Admin') ?></option>
                                <option value="Technician" <?= $user['user_group'] === 'Technician' ? 'selected' : '' ?>><?= t('group.Technician') ?></option>
                                <option value="Limited" <?= $user['user_group'] === 'Limited' ? 'selected' : '' ?>><?= t('group.Limited') ?></option>
                            </select>
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <p class="mt-1 text-sm text-gray-500"><?= t('users.cannot_change_role') ?></p>
                                <input type="hidden" name="user_group" value="<?= htmlspecialchars($user['user_group']) ?>">
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="is_active" class="block text-sm font-medium text-gray-700"><?= t('common.status') ?></label>
                            <select id="is_active" name="is_active" <?= $user['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?> class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm <?= $user['id'] == $_SESSION['user_id'] ? 'bg-gray-100' : 'bg-white' ?>">
                                <option value="1" <?= ($user['is_active'] ?? 1) ? 'selected' : '' ?>><?= t('users.active') ?></option>
                                <option value="0" <?= !($user['is_active'] ?? 1) ? 'selected' : '' ?>><?= t('common.inactive') ?></option>
                            </select>
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <p class="mt-1 text-sm text-gray-500"><?= t('users.cannot_deactivate_self') ?></p>
                                <input type="hidden" name="is_active" value="<?= $user['is_active'] ?? 1 ?>">
                            <?php endif; ?>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700"><?= t('users.password') ?></label>
                            <button type="button" onclick="openPasswordModal()" class="mt-1 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9a2 2 0 012-2m0 0V7a2 2 0 012-2h4zm-6 4h6m-6 4h6" />
                                </svg>
                                <?= t('users.change_password') ?>
                            </button>
                            <p class="mt-1 text-sm text-gray-500"><?= t('users.change_password_help') ?></p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <?= t('users.update') ?>
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <!-- Non-Admin View - Read Only -->
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('users.username') ?></dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($user['username']) ?></dd>
                        </div>
                        <?php if (!empty($user['name'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('users.full_name') ?></dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($user['name']) ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary Stats -->
        <div>
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900"><?= t('customers.summary') ?></h2>
                </div>
                <div class="px-6 py-4">
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('users.role') ?></dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php 
                                    switch($user['user_group']) {
                                        case 'Admin': echo 'bg-red-100 text-red-800'; break;
                                        case 'Technician': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'Limited': echo 'bg-gray-100 text-gray-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?= htmlspecialchars(tlabel('group', $user['user_group'])) ?>
                                </span>
                            </dd>
                        </div>
                        
                        <?php if ($_SESSION['user_group'] === 'Admin'): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('common.status') ?></dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?= ($user['is_active'] ?? 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ($user['is_active'] ?? 1) ? t('users.active') : t('common.inactive') ?>
                                </span>
                            </dd>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('users.current_wo') ?></dt>
                            <dd class="mt-1 text-2xl font-semibold text-gray-900"><?= count($workOrders) ?></dd>
                        </div>
                        
                        <?php if ($_SESSION['user_group'] === 'Admin'): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('users.last_login') ?></dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= $user['last_login'] ? ldate($user['last_login'], 'M j, Y g:i A') : t('common.never') ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('common.created') ?></dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= ldate($user['created_at'], 'M j, Y') ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Work Orders -->
    <?php if (!empty($workOrders)): ?>
    <div class="mt-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900"><?= t('users.current_wo') ?></h2>
            </div>
            <div class="overflow-hidden">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('common.id') ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('wo.customer') ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('common.device') ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('common.status') ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('common.created') ?></th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only"><?= t('common.actions') ?></span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($workOrders as $workOrder): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <a href="<?= BASE_URL ?>/work-orders/view/<?= $workOrder['id'] ?>" class="text-primary-600 hover:text-primary-500">
                                    #<?= $workOrder['id'] ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($workOrder['customer_name']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" title="<?= htmlspecialchars($workOrder['device_type']) ?>">
                                    <?= htmlspecialchars(strlen($workOrder['device_type']) > 30 ? substr($workOrder['device_type'], 0, 30) . '...' : $workOrder['device_type']) ?>
                                </div>
                                <div class="text-sm text-gray-500" title="<?= htmlspecialchars($workOrder['device_model']) ?>">
                                    <?= htmlspecialchars(strlen($workOrder['device_model']) > 30 ? substr($workOrder['device_model'], 0, 30) . '...' : $workOrder['device_model']) ?>
                                </div>
                                <?php if (!empty($workOrder['serial_number'])): ?>
                                <div class="text-sm text-gray-500" title="<?= htmlspecialchars($workOrder['serial_number']) ?>">
                                    <?= t('wo.sn_short', ['sn' => htmlspecialchars(strlen($workOrder['serial_number']) > 30 ? substr($workOrder['serial_number'], 0, 30) . '...' : $workOrder['serial_number'])]) ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($workOrder['imei'])): ?>
                                <div class="text-sm text-gray-500" title="<?= htmlspecialchars($workOrder['imei']) ?>">
                                    <?= t('wo.imei_short', ['imei' => htmlspecialchars(strlen($workOrder['imei']) > 30 ? substr($workOrder['imei'], 0, 30) . '...' : $workOrder['imei'])]) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php 
                                        switch($workOrder['status']) {
                                            case 'Open': echo 'bg-orange-100 text-orange-800'; break;
                                            case 'In Progress': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'Awaiting Parts': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'Closed': echo 'bg-green-100 text-green-800'; break;
                                            case 'Picked Up': echo 'bg-gray-100 text-gray-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?= htmlspecialchars(tlabel('status', $workOrder['status'])) ?>
                                    </span>
                                    <?php if ($workOrder['priority'] === 'Priority'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <?= t('priority.Priority') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= ldate($workOrder['created_at'], 'M j, Y') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= BASE_URL ?>/work-orders/view/<?= $workOrder['id'] ?>" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <?= t('common.view') ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Password Change Modal -->
<div id="passwordModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closePasswordModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="passwordForm" method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9a2 2 0 012-2m0 0V7a2 2 0 012-2h4zm-6 4h6m-6 4h6" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                <?= t('users.change_password_for', ['name' => htmlspecialchars($user['username'])]) ?>
                            </h3>
                            <div class="mt-4">
                                <label for="modal_new_password" class="block text-sm font-medium text-gray-700"><?= t('auth.new_password') ?> *</label>
                                <input type="password" id="modal_new_password" name="new_password" required minlength="8" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                <p class="mt-1 text-sm text-gray-500"><?= t('auth.min_chars') ?></p>
                            </div>
                            <div class="mt-4">
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700"><?= t('auth.confirm_password') ?> *</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <?= t('users.change_password') ?>
                    </button>
                    <button type="button" onclick="closePasswordModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        <?= t('common.cancel') ?>
                    </button>
                </div>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="password_change" value="1">
            </form>
        </div>
    </div>
</div>

<script>
function openPasswordModal() {
    document.getElementById('passwordModal').classList.remove('hidden');
    document.getElementById('modal_new_password').focus();
}

function closePasswordModal() {
    document.getElementById('passwordModal').classList.add('hidden');
    document.getElementById('passwordForm').reset();
}

// Validate password confirmation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('modal_new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert(<?= json_encode(t('js.passwords_mismatch')) ?>);
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert(<?= json_encode(t('js.password_short')) ?>);
        return false;
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePasswordModal();
    }
});
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
