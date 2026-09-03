<?php 
$title = t('users.title') . ' - ' . ($companyName ?? APP_NAME);
ob_start(); 
?>

<div class="py-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-gray-900"><?= t('users.title') ?></h1>
            <p class="mt-2 text-sm text-gray-700"><?= t('users.subtitle') ?></p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <button type="button" onclick="showCreateModal()" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto">
                <?= t('users.add') ?>
            </button>
        </div>
    </div>

    <?php if (isset($error) && $error): ?>
        <div class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
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
        <div class="mt-6 bg-green-50 border border-green-200 rounded-md p-4">
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

    <!-- User List -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('users.user') ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('users.role') ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('common.status') ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('users.last_login') ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('common.created') ?></th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only"><?= t('common.actions') ?></span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    <?= t('users.none') ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700"><?= strtoupper(substr($user['username'], 0, 2)) ?></span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= !empty($user['name']) ? htmlspecialchars($user['name']) : htmlspecialchars($user['username']) ?>
                                                <?php if (!empty($user['name'])): ?>
                                                    <span class="text-xs text-gray-500 font-normal">(<?= htmlspecialchars($user['username']) ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= ($user['is_active'] ?? 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= ($user['is_active'] ?? 1) ? t('users.active') : t('common.inactive') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $user['last_login'] ? ldate($user['last_login'], 'M j, Y g:i A') : t('common.never') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= ldate($user['created_at'], 'M j, Y') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="<?= BASE_URL ?>/users/view/<?= $user['id'] ?>" class="text-primary-600 hover:text-primary-900"><?= t('common.view') ?></a>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button onclick="toggleUserStatus(<?= $user['id'] ?>, <?= ($user['is_active'] ?? 1) ? 'false' : 'true' ?>)" class="<?= ($user['is_active'] ?? 1) ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' ?>">
                                            <?= ($user['is_active'] ?? 1) ? t('users.deactivate') : t('users.activate') ?>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4">
        <div class="flex-1 flex justify-between sm:hidden">
            <?php if ($currentPage > 1): ?>
            <a href="?page=<?= $currentPage - 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"><?= t('common.previous') ?></a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?= $currentPage + 1 ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"><?= t('common.next') ?></a>
            <?php endif; ?>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    <?= t('wo.showing_page', ['current' => $currentPage, 'total' => $totalPages]) ?>
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php 
                    $range = 2; // Number of pages to show around current page
                    $showFirst = $currentPage > $range + 1;
                    $showLast = $currentPage < $totalPages - $range;
                    
                    // Always show page 1
                    if ($showFirst): ?>
                        <a href="?page=1" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                    <?php endif; ?>

                    <?php
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == 1 && $showFirst) continue;
                        if ($i == $totalPages && $showLast) continue;
                        
                        if ($i >= $currentPage - $range && $i <= $currentPage + $range):
                    ?>
                        <a href="?page=<?= $i ?>" class="<?= $i === $currentPage ? 'bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                            <?= $i ?>
                        </a>
                    <?php 
                        endif;
                    endfor; 
                    ?>

                    <?php if ($showLast): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                        <a href="?page=<?= $totalPages ?>" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?= $totalPages ?></a>
                    <?php endif; ?>
                </nav>
                
                <!-- Go to Page Input -->
                <div class="ml-4 inline-flex items-center">
                    <span class="text-sm text-gray-700 mr-2"><?= t('wo.go_to_page') ?></span>
                    <input type="number" id="gotoPage" min="1" max="<?= $totalPages ?>" class="w-16 px-2 py-2 border-2 border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm bg-white" onkeydown="if(event.key === 'Enter') goToPage(this.value)">
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function goToPage(page) {
        page = parseInt(page);
        const maxPage = <?= $totalPages ?>;
        
        if (page >= 1 && page <= maxPage) {
            window.location.href = '?page=' + page;
        } else {
            alert(<?= json_encode(t('js.page_range')) ?>.replace('{max}', String(maxPage)));
        }
    }
    </script>
    <?php endif; ?>
</div>

<!-- Create User Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 1000;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4"><?= t('users.add_new') ?></h3>
            <form id="createUserForm" action="<?= BASE_URL ?>/users" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="mb-4">
                    <label for="user_username" class="block text-sm font-medium text-gray-700"><?= t('users.username') ?> *</label>
                    <input type="text" id="user_username" name="username" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                
                <div class="mb-4">
                    <label for="user_name" class="block text-sm font-medium text-gray-700"><?= t('users.full_name') ?></label>
                    <input type="text" id="user_name" name="name" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-xs text-gray-500"><?= t('users.username_blank') ?></p>
                </div>
                
                <div class="mb-4">
                    <label for="user_email" class="block text-sm font-medium text-gray-700"><?= t('users.email') ?> *</label>
                    <input type="email" id="user_email" name="email" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                
                <div class="mb-4">
                    <label for="user_password" class="block text-sm font-medium text-gray-700"><?= t('users.password') ?> *</label>
                    <input type="password" id="user_password" name="password" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                
                <div class="mb-4">
                    <label for="user_group" class="block text-sm font-medium text-gray-700"><?= t('users.role') ?> *</label>
                    <select id="user_group" name="user_group" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        <option value=""><?= t('users.select_role') ?></option>
                        <option value="Admin"><?= t('group.Admin') ?></option>
                        <option value="Technician"><?= t('group.Technician') ?></option>
                        <option value="Limited"><?= t('group.Limited') ?></option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideCreateModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <?= t('common.cancel') ?>
                    </button>
                    <button type="submit" name="create_user" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700">
                        <?= t('users.create') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function hideCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createUserForm').reset();
}

function toggleUserStatus(userId, activate) {
    const action = activate ? <?= json_encode(t('users.activate')) ?> : <?= json_encode(t('users.deactivate')) ?>;
    if (confirm(<?= json_encode(t('users.confirm_toggle', ['action' => '{action}'])) ?>.replace('{action}', action.toLowerCase()))) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= BASE_URL ?>/users';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = '<?= $csrf_token ?>';
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'is_active';
        statusInput.value = activate ? '1' : '0';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'toggle_status';
        
        form.appendChild(csrfToken);
        form.appendChild(userIdInput);
        form.appendChild(statusInput);
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideCreateModal();
    }
});

// Hide modal if user was created successfully
<?php if (isset($message) && $message): ?>
document.addEventListener('DOMContentLoaded', function() {
    hideCreateModal();
});
<?php endif; ?>
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
