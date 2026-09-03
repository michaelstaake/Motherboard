<?php 
$title = t('customers.title') . ' - ' . ($companyName ?? APP_NAME);
ob_start(); 
?>

<div class="py-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-gray-900"><?= t('customers.title') ?></h1>
            <p class="mt-2 text-sm text-gray-700"><?= t('customers.subtitle') ?></p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex space-x-3">
            <?php if ($_SESSION['user_group'] === 'Admin'): ?>
            <a href="<?= BASE_URL ?>/customers/auto-merge" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto">
                <?= t('customers.auto_merge') ?>
            </a>
            <?php endif; ?>
            <button type="button" onclick="showCreateModal()" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 sm:w-auto">
                <?= t('customers.create') ?>
            </button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_GET['message']) || !empty($message)): ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-600"><?= htmlspecialchars($_GET['message'] ?? $message) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) || !empty($error)): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-600"><?= htmlspecialchars($_GET['error'] ?? $error) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="mt-6">
        <form method="GET" class="flex space-x-4">
            <div class="flex-1">
                <label for="search" class="sr-only"><?= t('common.search') ?></label>
                <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" placeholder="<?= htmlspecialchars(t('customers.search')) ?>" class="block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                <?= t('common.search') ?>
            </button>
            <?php if ($search): ?>
            <a href="<?= BASE_URL ?>/customers" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <?= t('common.clear') ?>
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Customer List -->
    <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('customers.name') ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('common.contact') ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('customers.orders') ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('common.created') ?></th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only"><?= t('common.actions') ?></span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    <?= $search ? t('customers.none_search') : t('customers.none_yet') ?>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($customer['name']) ?></div>
                                        <?php if ($customer['company']): ?>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($customer['company']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php if ($customer['email']): ?>
                                        <div><a href="mailto:<?= htmlspecialchars($customer['email']) ?>" class="text-primary-600 hover:text-primary-500"><?= htmlspecialchars($customer['email']) ?></a></div>
                                        <?php endif; ?>
                                        <?php if ($customer['phone']): ?>
                                        <div><a href="tel:<?= htmlspecialchars($customer['phone']) ?>" class="text-primary-600 hover:text-primary-500"><?= htmlspecialchars($customer['phone']) ?></a></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $customer['work_order_count'] ?? 0 ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= ldate($customer['created_at'], 'M j, Y') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="<?= BASE_URL ?>/customers/view/<?= $customer['id'] ?>" class="text-primary-600 hover:text-primary-900"><?= t('common.view') ?></a>
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
            <a href="?page=<?= $currentPage - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"><?= t('common.previous') ?></a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
            <a href="?page=<?= $currentPage + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"><?= t('common.next') ?></a>
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
                        <a href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?>" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                    <?php endif; ?>

                    <?php
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == 1 && $showFirst) continue;
                        if ($i == $totalPages && $showLast) continue;
                        
                        if ($i >= $currentPage - $range && $i <= $currentPage + $range):
                    ?>
                        <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="<?= $i === $currentPage ? 'bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                            <?= $i ?>
                        </a>
                    <?php 
                        endif;
                    endfor; 
                    ?>

                    <?php if ($showLast): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                        <a href="?page=<?= $totalPages ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?= $totalPages ?></a>
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
        
        if (page >= 1 && page <= maxPage) {
            window.location.href = '?page=' + page + search;
        } else {
            alert(<?= json_encode(t('js.page_range')) ?>.replace('{max}', String(maxPage)));
        }
    }
    </script>
    <?php endif; ?>
</div>

<!-- Create Customer Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 1000;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4"><?= t('customers.add_new') ?></h3>
            <form id="createCustomerForm" action="<?= BASE_URL ?>/customers" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="mb-4">
                    <label for="customer_name" class="block text-sm font-medium text-gray-700"><?= t('customers.name') ?> *</label>
                    <input type="text" id="customer_name" name="name" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                
                <div class="mb-4">
                    <label for="customer_company" class="block text-sm font-medium text-gray-700"><?= t('customers.company') ?></label>
                    <input type="text" id="customer_company" name="company" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                
                <div class="mb-4">
                    <label for="customer_email" class="block text-sm font-medium text-gray-700"><?= t('customers.email') ?></label>
                    <input type="email" id="customer_email" name="email" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                
                <div class="mb-4">
                    <label for="customer_phone" class="block text-sm font-medium text-gray-700"><?= t('customers.phone') ?> *</label>
                    <input type="tel" id="customer_phone" name="phone" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideCreateModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <?= t('common.cancel') ?>
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700">
                        <?= t('customers.create_btn') ?>
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
    document.getElementById('createCustomerForm').reset();
}

// Close modal when clicking outside
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideCreateModal();
    }
});
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
