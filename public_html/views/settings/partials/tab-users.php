<div class="px-6 py-4">
    <div class="flex flex-col">
        <div class="-mx-6 overflow-x-auto">
            <div class="inline-block min-w-full align-middle">
                <div class="overflow-hidden border-t border-gray-200">
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

    <?php if ($totalPages > 1): ?>
    <div class="px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4">
        <div class="flex-1 flex justify-between sm:hidden">
            <?php if ($currentPage > 1): ?>
            <a href="<?= BASE_URL ?>/settings?tab=users&page=<?= $currentPage - 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"><?= t('common.previous') ?></a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
            <a href="<?= BASE_URL ?>/settings?tab=users&page=<?= $currentPage + 1 ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"><?= t('common.next') ?></a>
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
                    $range = 2;
                    $showFirst = $currentPage > $range + 1;
                    $showLast = $currentPage < $totalPages - $range;

                    if ($showFirst): ?>
                        <a href="<?= BASE_URL ?>/settings?tab=users&page=1" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                    <?php endif; ?>

                    <?php
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == 1 && $showFirst) continue;
                        if ($i == $totalPages && $showLast) continue;

                        if ($i >= $currentPage - $range && $i <= $currentPage + $range):
                    ?>
                        <a href="<?= BASE_URL ?>/settings?tab=users&page=<?= $i ?>" class="<?= $i === $currentPage ? 'bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                            <?= $i ?>
                        </a>
                    <?php
                        endif;
                    endfor;
                    ?>

                    <?php if ($showLast): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                        <a href="<?= BASE_URL ?>/settings?tab=users&page=<?= $totalPages ?>" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?= $totalPages ?></a>
                    <?php endif; ?>
                </nav>

                <div class="ml-4 inline-flex items-center">
                    <span class="text-sm text-gray-700 mr-2"><?= t('wo.go_to_page') ?></span>
                    <input type="number" id="usersGotoPage" min="1" max="<?= $totalPages ?>" class="w-16 px-2 py-2 border-2 border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm bg-white" onkeydown="if(event.key === 'Enter') goToUsersPage(this.value)">
                </div>
            </div>
        </div>
    </div>
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
function goToUsersPage(page) {
    page = parseInt(page);
    const maxPage = <?= $totalPages ?? 1 ?>;

    if (page >= 1 && page <= maxPage) {
        window.location.href = '<?= BASE_URL ?>/settings?tab=users&page=' + page;
    } else {
        alert(<?= json_encode(t('js.page_range')) ?>.replace('{max}', String(maxPage)));
    }
}

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

document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideCreateModal();
    }
});

<?php if (isset($message) && $message && ($activeTab ?? '') === 'users'): ?>
document.addEventListener('DOMContentLoaded', function() {
    hideCreateModal();
});
<?php endif; ?>
</script>
