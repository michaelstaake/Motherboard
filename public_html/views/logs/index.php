<?php 
$title = t('logs.title') . ' - ' . ($companyName ?? APP_NAME);
ob_start(); 
?>

<div class="py-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-gray-900"><?= t('logs.title') ?></h1>
            <p class="mt-2 text-sm text-gray-700"><?= t('logs.subtitle') ?></p>
        </div>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="mt-6 bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-600"><?= htmlspecialchars($_GET['message']) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-600"><?= htmlspecialchars($_GET['error']) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="mt-6 bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900"><?= t('logs.filters') ?></h2>
        </div>
        <div class="px-6 py-4">
            <form method="GET" action="<?= BASE_URL ?>/logs" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700"><?= t('common.search') ?></label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="<?= htmlspecialchars(t('common.search')) ?>..." class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                <div>
                    <label for="action" class="block text-sm font-medium text-gray-700"><?= t('logs.action') ?></label>
                    <select id="action" name="action" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        <option value=""><?= t('logs.all_actions') ?></option>
                        <?php foreach ($uniqueActions as $uniqueAction): ?>
                            <option value="<?= htmlspecialchars($uniqueAction) ?>" <?= $action === $uniqueAction ? 'selected' : '' ?>>
                                <?= htmlspecialchars(tlabel('log', $uniqueAction)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="user_id" class="block text-sm font-medium text-gray-700"><?= t('users.user') ?></label>
                    <select id="user_id" name="user_id" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                        <option value=""><?= t('logs.all_users') ?></option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['display_name'] ?? $user['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:text-sm">
                        <?= t('common.filter') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <?= t('logs.title') ?> 
                <span class="text-sm text-gray-500">(<?= t('logs.total', ['count' => number_format($totalLogs)]) ?>)</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?= t('logs.timestamp') ?>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?= t('users.user') ?>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?= t('logs.action') ?>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?= t('logs.details') ?>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?= t('logs.ip') ?>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?= t('logs.user_agent') ?>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                <?= t('logs.none') ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('M j, Y g:i A', strtotime($log['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($log['user_id']): ?>
                                        <span class="font-medium"><?= htmlspecialchars($log['display_name'] ?? $log['username']) ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-500 italic"><?= t('logs.system') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?php
                                        switch($log['action']) {
                                            case 'user_login':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'user_logout':
                                                echo 'bg-gray-100 text-gray-800';
                                                break;
                                            case 'user_created':
                                            case 'user_updated':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'user_deleted':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'work_order_created':
                                            case 'work_order_updated':
                                                echo 'bg-purple-100 text-purple-800';
                                                break;
                                            case 'settings_updated':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?= htmlspecialchars(tlabel('log', $log['action'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                    <?= htmlspecialchars($log['details']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($log['ip_address']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                    <?= htmlspecialchars($log['user_agent']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <?php if ($currentPage > 1): ?>
                    <a href="<?= BASE_URL ?>/logs?page=<?= $currentPage - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $userId ? '&user_id=' . urlencode($userId) : '' ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <?= t('common.previous') ?>
                    </a>
                <?php endif; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= BASE_URL ?>/logs?page=<?= $currentPage + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $userId ? '&user_id=' . urlencode($userId) : '' ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <?= t('common.next') ?>
                    </a>
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
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= BASE_URL ?>/logs?page=<?= $currentPage - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $userId ? '&user_id=' . urlencode($userId) : '' ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <?= t('common.previous') ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        $range = 2; // Number of pages to show around current page
                        $showFirst = $currentPage > $range + 1;
                        $showLast = $currentPage < $totalPages - $range;
                        
                        // Always show page 1
                        if ($showFirst): ?>
                            <a href="<?= BASE_URL ?>/logs?page=1<?= $search ? '&search=' . urlencode($search) : '' ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $userId ? '&user_id=' . urlencode($userId) : '' ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">1</a>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                        <?php endif; ?>

                        <?php
                        for ($i = 1; $i <= $totalPages; $i++):
                            if ($i == 1 && $showFirst) continue;
                            if ($i == $totalPages && $showLast) continue;
                            
                            if ($i >= $currentPage - $range && $i <= $currentPage + $range):
                        ?>
                            <a href="<?= BASE_URL ?>/logs?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $userId ? '&user_id=' . urlencode($userId) : '' ?>" 
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?= $i === $currentPage ? 'z-10 bg-primary-50 border-primary-500 text-primary-600' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php 
                            endif;
                        endfor; 
                        ?>

                        <?php if ($showLast): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                            <a href="<?= BASE_URL ?>/logs?page=<?= $totalPages ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $userId ? '&user_id=' . urlencode($userId) : '' ?>" 
                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium border-gray-300 bg-white text-gray-500 hover:bg-gray-50"><?= $totalPages ?></a>
                        <?php endif; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= BASE_URL ?>/logs?page=<?= $currentPage + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $action ? '&action=' . urlencode($action) : '' ?><?= $userId ? '&user_id=' . urlencode($userId) : '' ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <?= t('common.next') ?>
                            </a>
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
            const search = '<?= $search ? '&search=' . urlencode($search) : '' ?>';
            const action = '<?= $action ? '&action=' . urlencode($action) : '' ?>';
            const userId = '<?= $userId ? '&user_id=' . urlencode($userId) : '' ?>';
            
            if (page >= 1 && page <= maxPage) {
                window.location.href = '<?= BASE_URL ?>/logs?page=' + page + search + action + userId;
            } else {
                alert(<?= json_encode(t('js.page_range')) ?>.replace('{max}', String(maxPage)));
            }
        }
        </script>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
