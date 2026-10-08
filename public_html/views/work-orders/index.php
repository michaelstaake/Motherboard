<?php 
// Active work orders this many days after opening, and closed ones this many days after closing
// (until picked up), have their date tinted amber, then red, in the list.
const DAYS_OPEN_STALE = 7;
const DAYS_OPEN_OVERDUE = 14;

$title = t('wo.title') . ' - ' . ($companyName ?? APP_NAME);
[$sortColumn, $sortDirection] = $sort ?? ['number', 'desc'];
$priority = $priority ?? null;
$statusCounts = $statusCounts ?? [];
$sortQuery = [$sortColumn, $sortDirection] !== ['number', 'desc'];
// Builds a list URL that keeps the current filters, search, and non-default sort, with $overrides
// applied on top. Empty values are left out, so pass '' to drop one (status '' means All).
$listUrl = static function (array $overrides = []) use ($status, $priority, $search, $assignedTo, $sortColumn, $sortDirection, $sortQuery): string {
    $query = array_merge([
        'page' => '',
        'status' => $status !== 'All' ? $status : '',
        'priority' => (string) $priority,
        'search' => $search,
        'assigned_to' => (string) $assignedTo,
        'sort' => $sortQuery ? $sortColumn : '',
        'dir' => $sortQuery ? $sortDirection : '',
    ], $overrides);
    return BASE_URL . '/work-orders?' . http_build_query(array_filter($query, fn($value) => (string) $value !== ''));
};
// Clicking a column header sorts by it A to Z (oldest first for dates), or flips the direction
// if it is already sorted by it.
$sortHeader = static function (string $column, string $label) use ($sortColumn, $sortDirection, $listUrl): string {
    $active = $sortColumn === $column;
    $url = $listUrl(['sort' => $column, 'dir' => $active && $sortDirection === 'asc' ? 'desc' : 'asc']);
    $arrow = $active ? ($sortDirection === 'asc' ? '&#x25B2;' : '&#x25BC;') : '';
    return '<th scope="col"' . ($active ? ' aria-sort="' . ($sortDirection === 'asc' ? 'ascending' : 'descending') . '"' : '')
        . ' class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">'
        . '<a href="' . htmlspecialchars($url) . '" class="inline-flex items-center gap-1 whitespace-nowrap ' . ($active ? 'text-gray-900' : 'text-gray-500 hover:text-gray-700') . '">'
        . htmlspecialchars($label)
        . '<span aria-hidden="true">' . $arrow . '</span>'
        . '</a></th>';
};
// Tabs split work orders by where they are in their life. Active also covers its own statuses,
// which get sub-chips under the tabs while it (or one of them) is selected.
$inActive = $status === 'Active' || in_array($status, WorkOrder::ACTIVE_STATUSES, true);
$activeCounts = array_map(fn($included) => $statusCounts[$included] ?? 0, WorkOrder::ACTIVE_STATUSES);
$tabs = [
    'All' => array_sum($statusCounts),
    'Active' => array_sum($activeCounts),
    'Closed' => $statusCounts['Closed'] ?? 0,
    'Picked Up' => $statusCounts['Picked Up'] ?? 0,
];
$chips = ['Active' => array_sum($activeCounts)] + array_combine(WorkOrder::ACTIVE_STATUSES, $activeCounts);
ob_start();
?>

<div>
    <div class="py-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= t('wo.title') ?></h1>
                <?php if ($assignedTo): ?>
                    <p class="mt-1 text-sm text-gray-600"><?= t('wo.assigned_filter') ?></p>
                <?php endif; ?>
            </div>
            <?php if ($_SESSION['user_group'] !== 'Limited'): ?>
                <a href="<?= BASE_URL ?>/work-orders/create" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700">
                    <?= t('wo.create') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 pt-3 xl:pt-0 border-b border-gray-200 flex flex-col-reverse gap-3 xl:flex-row xl:items-end xl:justify-between">
            <!-- Status Tabs -->
            <nav class="-mb-px flex gap-6 overflow-x-auto">
                <?php foreach ($tabs as $tab => $count):
                    $selected = $tab === 'Active' ? $inActive : $status === $tab;
                ?>
                    <a href="<?= htmlspecialchars($listUrl(['status' => $tab !== 'All' ? $tab : ''])) ?>"
                       <?= $selected ? 'aria-current="page"' : '' ?>
                       class="inline-flex items-center gap-2 py-4 border-b-2 text-sm font-medium whitespace-nowrap transition-colors <?= $selected ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                        <?= t('status.' . $tab) ?>
                        <span class="px-2 py-0.5 rounded-full text-xs <?= $selected ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600' ?>"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="flex items-center gap-3 xl:py-3">
                <!-- Priority Toggle -->
                <a href="<?= htmlspecialchars($listUrl(['priority' => $priority ? '' : 'Priority'])) ?>"
                   aria-pressed="<?= $priority ? 'true' : 'false' ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 whitespace-nowrap rounded-full text-sm border transition-colors <?= $priority ? 'bg-red-50 border-red-300 text-red-700' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2z"></path>
                    </svg>
                    <?= t('status.Priority') ?>
                </a>

                <!-- Search -->
                <form method="GET" class="flex-1 xl:flex-none xl:w-80">
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                    <?php if ($priority): ?>
                        <input type="hidden" name="priority" value="<?= htmlspecialchars($priority) ?>">
                    <?php endif; ?>
                    <?php if ($assignedTo): ?>
                        <input type="hidden" name="assigned_to" value="<?= htmlspecialchars($assignedTo) ?>">
                    <?php endif; ?>
                    <?php if ($sortQuery): ?>
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sortColumn) ?>">
                        <input type="hidden" name="dir" value="<?= htmlspecialchars($sortDirection) ?>">
                    <?php endif; ?>
                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="search"
                               name="search"
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="<?= htmlspecialchars(t('wo.search')) ?>"
                               aria-label="<?= htmlspecialchars(t('wo.search')) ?>"
                               class="w-full pl-9 pr-3 py-2 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    </div>
                </form>
            </div>
        </div>

        <!-- Active Status Chips -->
        <?php if ($inActive): ?>
            <div class="px-6 py-3 flex flex-wrap items-center gap-2">
                <?php foreach ($chips as $chip => $count):
                    $selected = $status === $chip;
                ?>
                    <a href="<?= htmlspecialchars($listUrl(['status' => $chip])) ?>"
                       <?= $selected ? 'aria-current="page"' : '' ?>
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm border transition-colors <?= $selected ? 'bg-primary-50 border-primary-200 text-primary-700' : 'border-gray-300 text-gray-700 hover:bg-gray-50' ?>">
                        <?= $chip === 'Active' ? t('wo.any_status') : t('status.' . $chip) ?>
                        <span class="text-xs <?= $selected ? 'text-primary-600' : 'text-gray-500' ?>"><?= $count ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Active Filters -->
        <?php if ($assignedTo): ?>
            <div class="px-6 py-3 bg-blue-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-blue-700"><?= t('wo.active_filters') ?></span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?= t('wo.assigned_to_me') ?>
                            <a href="<?= htmlspecialchars($listUrl(['assigned_to' => ''])) ?>" 
                               class="ml-1 inline-flex items-center justify-center w-4 h-4 text-blue-400 hover:text-blue-600">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                        </span>
                    </div>
                    <a href="<?= BASE_URL ?>/work-orders" class="text-sm text-blue-600 hover:text-blue-500">
                        <?= t('wo.clear_filters') ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Work Orders Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <?= $sortHeader('number', t('wo.number')) ?>
                        <?= $sortHeader('customer', t('wo.customer')) ?>
                        <?= $sortHeader('opened', t('wo.date_opened')) ?>
                        <?= $sortHeader('computer', t('wo.computer')) ?>
                        <?= $sortHeader('technician', t('wo.technician')) ?>
                        <?= $sortHeader('status', t('common.status')) ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($workOrders)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                <?= t('wo.none') ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($workOrders as $workOrder): ?>
                            <?php // Clicking anywhere on a work order row opens it, except on the customer and technician links. ?>
                            <tr class="hover:bg-gray-50 cursor-pointer focus:outline-none focus:bg-gray-50" tabindex="0" role="link" data-href="<?= BASE_URL ?>/work-orders/view/<?= $workOrder['id'] ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base font-medium text-gray-900">#<?= $workOrder['id'] ?></span>
                                        <?php if ($workOrder['priority'] === 'Priority'): ?>
                                            <span title="<?= htmlspecialchars(t('priority.Priority')) ?>">
                                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2z"></path>
                                                </svg>
                                                <span class="sr-only"><?= t('priority.Priority') ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <a href="<?= BASE_URL ?>/customers/view/<?= $workOrder['customer_id'] ?>" class="text-primary-600 hover:text-primary-500">
                                            <?= htmlspecialchars($workOrder['customer_name']) ?>
                                        </a>
                                    </div>
                                    <?php if ($workOrder['customer_company']): ?>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($workOrder['customer_company']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php $isClosed = $workOrder['status'] === 'Closed' && !empty($workOrder['closed_at']); ?>
                                    <?php if ($isClosed || !in_array($workOrder['status'], ['Closed', 'Picked Up'], true)): ?>
                                        <?php $days = (new DateTime(date('Y-m-d', strtotime($isClosed ? $workOrder['closed_at'] : $workOrder['created_at']))))->diff(new DateTime('today'))->days; ?>
                                        <?php $daysLabel = t($isClosed ? 'wo.days_closed' : 'wo.days_open', ['count' => $days]); ?>
                                        <span class="<?= $days >= DAYS_OPEN_OVERDUE ? 'text-red-600 font-medium' : ($days >= DAYS_OPEN_STALE ? 'text-amber-600' : '') ?>"<?php if (empty($daysUnderDate)): ?> title="<?= htmlspecialchars($daysLabel) ?>"<?php endif; ?>>
                                            <?= ldate($workOrder['created_at'], 'M j, Y') ?>
                                        </span>
                                        <?php if (!empty($daysUnderDate)): ?>
                                            <div class="text-xs"><?= htmlspecialchars($daysLabel) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?= ldate($workOrder['created_at'], 'M j, Y') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900" title="<?= htmlspecialchars($workOrder['computer']) ?>">
                                        <?= htmlspecialchars(strlen($workOrder['computer']) > 30 ? substr($workOrder['computer'], 0, 30) . '...' : $workOrder['computer']) ?>
                                    </div>
                                    <?php if ($workOrder['model']): ?>
                                        <div class="text-sm text-gray-500" title="<?= htmlspecialchars($workOrder['model']) ?>">
                                            <?= htmlspecialchars(strlen($workOrder['model']) > 30 ? substr($workOrder['model'], 0, 30) . '...' : $workOrder['model']) ?>
                                        </div>
                                    <?php endif; ?>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($workOrder['technician_display_name']): ?>
                                        <a href="<?= BASE_URL ?>/users/view/<?= $workOrder['assigned_to'] ?>" class="text-primary-600 hover:text-primary-500">
                                            <?= htmlspecialchars($workOrder['technician_display_name']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400"><?= t('common.unassigned') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= $workOrder['status'] === 'Open' ? 'bg-orange-100 text-orange-800' :
                                            ($workOrder['status'] === 'In Progress' ? 'bg-yellow-100 text-yellow-800' :
                                            ($workOrder['status'] === 'Awaiting Parts' ? 'bg-purple-100 text-purple-800' :
                                            ($workOrder['status'] === 'Closed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'))) ?>">
                                        <?= htmlspecialchars(tlabel('status', $workOrder['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= htmlspecialchars($listUrl(['page' => $currentPage - 1])) ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <?= t('common.previous') ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= htmlspecialchars($listUrl(['page' => $currentPage + 1])) ?>" 
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php 
                            $range = 2; // Number of pages to show around current page
                            $showFirst = $currentPage > $range + 1;
                            $showLast = $currentPage < $totalPages - $range;
                            
                            // Always show page 1
                            if ($showFirst): ?>
                                <a href="<?= htmlspecialchars($listUrl(['page' => 1])) ?>" 
                                   class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                            <?php endif; ?>

                            <?php
                            for ($i = 1; $i <= $totalPages; $i++):
                                if ($i == 1 && $showFirst) continue;
                                if ($i == $totalPages && $showLast) continue;
                                
                                if ($i >= $currentPage - $range && $i <= $currentPage + $range):
                            ?>
                                <a href="<?= htmlspecialchars($listUrl(['page' => $i])) ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border text-sm font-medium 
                                   <?= $i === $currentPage ? 'z-10 bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php 
                                endif;
                            endfor; 
                            ?>

                            <?php if ($showLast): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">...</span>
                                <a href="<?= htmlspecialchars($listUrl(['page' => $totalPages])) ?>" 
                                   class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium"><?= $totalPages ?></a>
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
                // The current filters and sort; the page number is added on the end.
                const listUrl = <?= json_encode($listUrl()) ?>;
                
                if (page >= 1 && page <= maxPage) {
                    window.location.href = listUrl + (listUrl.endsWith('?') ? '' : '&') + 'page=' + page;
                } else {
                    showAlert(<?= json_encode(t('js.page_range')) ?>.replace('{max}', String(maxPage)), 'error');
                }
            }
            </script>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('tr[data-href]').forEach(function (row) {
    row.addEventListener('click', function (event) {
        // Links inside the row go to their own page, and selecting text should not navigate.
        if (event.target.closest('a') || window.getSelection().toString()) {
            return;
        }
        if (event.ctrlKey || event.metaKey) {
            window.open(row.dataset.href, '_blank');
        } else {
            window.location.href = row.dataset.href;
        }
    });
    row.addEventListener('auxclick', function (event) {
        if (event.button === 1 && !event.target.closest('a')) {
            window.open(row.dataset.href, '_blank');
        }
    });
    row.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.target === row) {
            window.location.href = row.dataset.href;
        }
    });
});
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
