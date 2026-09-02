<?php 
$title = t('customers.merge') . ' - ' . ($companyName ?? APP_NAME);
ob_start(); 
?>

<div>
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900"><?= t('customers.merge') ?></h1>
        
        <!-- Progress Indicator -->
        <div class="mt-6">
            <div class="flex items-center">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div class="flex items-center <?= $i < 3 ? 'flex-1' : '' ?>">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full 
                            <?= $step >= $i ? 'bg-primary-600 text-white' : 'bg-gray-300 text-gray-600' ?>">
                            <?= $i ?>
                        </div>
                        <div class="ml-2 text-sm font-medium 
                            <?= $step >= $i ? 'text-primary-600' : 'text-gray-500' ?>">
                            <?= ['', t('customers.merge_source'), t('customers.merge_dest'), t('customers.merge_confirm_step')][$i] ?>
                        </div>
                        <?php if ($i < 3): ?>
                            <div class="flex-1 h-0.5 mx-4 
                                <?= $step > $i ? 'bg-primary-600' : 'bg-gray-300' ?>"></div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4">
            <?php if ($step === 1): ?>
                <!-- Step 1: Source Customer -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4"><?= t('customers.merge_step1') ?></h2>
                <p class="text-gray-600 mb-6"><?= t('customers.merge_step1_help') ?></p>
                
                <form method="POST" action="<?= BASE_URL ?>/customers/merge?step=2">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="next_step" value="2">
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?= t('customers.source_label') ?></label>
                        <?php if ($sourceId && $sourceCustomer): ?>
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800"><?= htmlspecialchars($sourceCustomer['name']) ?></h3>
                                        <?php if ($sourceCustomer['company']): ?>
                                        <p class="text-sm text-blue-600"><?= htmlspecialchars($sourceCustomer['company']) ?></p>
                                        <?php endif; ?>
                                        <p class="text-sm text-blue-600">
                                            <?= htmlspecialchars($sourceCustomer['phone']) ?> • 
                                            <?= t('customers.wo_count', ['count' => count($sourceCustomer['work_orders'])]) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="source_customer" value="<?= $sourceId ?>">
                        <?php else: ?>
                            <div class="relative">
                                <input type="text" 
                                       id="source_customer_search" 
                                       class="w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
                                       placeholder="<?= htmlspecialchars(t('wo.search_customer_ph')) ?>">
                                <div id="source_customer_results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 hidden max-h-60 overflow-auto"></div>
                            </div>
                            <input type="hidden" name="source_customer" id="selected_source_customer_id" required>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-between">
                        <a href="<?= BASE_URL ?>/customers" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                            <?= t('common.cancel') ?>
                        </a>
                        <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700">
                            <?= t('customers.next_dest') ?>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step === 2): ?>
                <!-- Step 2: Destination Customer -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4"><?= t('customers.merge_step2') ?></h2>
                <p class="text-gray-600 mb-6"><?= t('customers.merge_step2_help') ?></p>
                
                <form method="POST" action="<?= BASE_URL ?>/customers/merge?step=3">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="next_step" value="3">
                    <input type="hidden" name="source_customer" value="<?= $sourceId ?>">
                    
                    <?php if ($sourceCustomer): ?>
                    <div class="mb-6 bg-gray-50 border border-gray-200 rounded-md p-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-2"><?= t('customers.source_will_delete') ?></h3>
                        <div class="text-sm text-gray-900">
                            <strong><?= htmlspecialchars($sourceCustomer['name']) ?></strong>
                            <?= $sourceCustomer['company'] ? ' - ' . htmlspecialchars($sourceCustomer['company']) : '' ?>
                            <span class="text-gray-600">(<?= t('customers.wo_count', ['count' => count($sourceCustomer['work_orders'])]) ?>)</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?= t('customers.dest_label') ?></label>
                        <div class="relative">
                            <input type="text" 
                                   id="destination_customer_search" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
                                   placeholder="<?= htmlspecialchars(t('wo.search_customer_ph')) ?>">
                            <div id="destination_customer_results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 hidden max-h-60 overflow-auto"></div>
                        </div>
                        <input type="hidden" name="destination_customer" id="selected_destination_customer_id" required>
                        
                        <?php if ($destinationId && $destinationCustomer): ?>
                        <div class="mt-3 bg-green-50 border border-green-200 rounded-md p-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800"><?= t('customers.selected', ['name' => htmlspecialchars($destinationCustomer['name'])]) ?></h3>
                                    <?php if ($destinationCustomer['company']): ?>
                                    <p class="text-sm text-green-600"><?= htmlspecialchars($destinationCustomer['company']) ?></p>
                                    <?php endif; ?>
                                    <p class="text-sm text-green-600">
                                        <?= htmlspecialchars($destinationCustomer['phone']) ?> • 
                                        <?= t('customers.wo_count', ['count' => count($destinationCustomer['work_orders'])]) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <script>
                        // Pre-fill destination customer if already selected
                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('selected_destination_customer_id').value = <?= json_encode((string) $destinationId) ?>;
                            document.getElementById('destination_customer_search').value = <?= json_encode(
                                $destinationCustomer['name'],
                                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                            ) ?>;
                        });
                        </script>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-between">
                        <a href="<?= BASE_URL ?>/customers/merge?step=1&source=<?= $sourceId ?>" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                            <?= t('customers.back_source') ?>
                        </a>
                        <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700">
                            <?= t('customers.next_confirm') ?>
                        </button>
                    </div>
                </form>
                
            <?php elseif ($step === 3): ?>
                <!-- Step 3: Confirm -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4"><?= t('customers.merge_step3') ?></h2>
                <p class="text-red-600 mb-6"><?= t('customers.merge_warning') ?></p>
                
                <form method="POST" action="<?= BASE_URL ?>/customers/merge">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="source_customer" value="<?= $sourceId ?>">
                    <input type="hidden" name="destination_customer" value="<?= $destinationId ?>">
                    <input type="hidden" name="confirm_merge" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <?php if ($sourceCustomer): ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-red-800 mb-3">
                                <svg class="inline h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <?= t('customers.to_delete') ?>
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div><strong><?= t('customers.name') ?>:</strong> <?= htmlspecialchars($sourceCustomer['name']) ?></div>
                                <?php if ($sourceCustomer['company']): ?>
                                <div><strong><?= t('customers.company') ?>:</strong> <?= htmlspecialchars($sourceCustomer['company']) ?></div>
                                <?php endif; ?>
                                <?php if ($sourceCustomer['email']): ?>
                                <div><strong><?= t('customers.email') ?>:</strong> <?= htmlspecialchars($sourceCustomer['email']) ?></div>
                                <?php endif; ?>
                                <?php if ($sourceCustomer['phone']): ?>
                                <div><strong><?= t('customers.phone') ?>:</strong> <?= htmlspecialchars($sourceCustomer['phone']) ?></div>
                                <?php endif; ?>
                                <div><strong><?= t('customers.orders') ?>:</strong> <?= count($sourceCustomer['work_orders']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($destinationCustomer): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-green-800 mb-3">
                                <svg class="inline h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?= t('customers.to_keep') ?>
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div><strong><?= t('customers.name') ?>:</strong> <?= htmlspecialchars($destinationCustomer['name']) ?></div>
                                <?php if ($destinationCustomer['company']): ?>
                                <div><strong><?= t('customers.company') ?>:</strong> <?= htmlspecialchars($destinationCustomer['company']) ?></div>
                                <?php endif; ?>
                                <?php if ($destinationCustomer['email']): ?>
                                <div><strong><?= t('customers.email') ?>:</strong> <?= htmlspecialchars($destinationCustomer['email']) ?></div>
                                <?php endif; ?>
                                <?php if ($destinationCustomer['phone']): ?>
                                <div><strong><?= t('customers.phone') ?>:</strong> <?= htmlspecialchars($destinationCustomer['phone']) ?></div>
                                <?php endif; ?>
                                <div><strong><?= t('customers.current_wo') ?>:</strong> <?= count($destinationCustomer['work_orders']) ?></div>
                                <div><strong><?= t('customers.total_after') ?>:</strong> <?= count($destinationCustomer['work_orders']) + count($sourceCustomer['work_orders']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800"><?= t('customers.what_happens') ?></h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li><?= t('customers.merge_move', ['count' => count($sourceCustomer['work_orders']), 'source' => htmlspecialchars($sourceCustomer['name']), 'dest' => htmlspecialchars($destinationCustomer['name'])]) ?></li>
                                        <li><?= t('customers.merge_delete', ['name' => htmlspecialchars($sourceCustomer['name'])]) ?></li>
                                        <li><?= t('customers.merge_keep', ['name' => htmlspecialchars($destinationCustomer['name'])]) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-between">
                        <a href="<?= BASE_URL ?>/customers/merge?step=2&source=<?= $sourceId ?>&destination=<?= $destinationId ?>" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                            <?= t('customers.back_dest') ?>
                        </a>
                        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-md hover:bg-red-700">
                            <?= t('customers.confirm_merge') ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function mergeCustomerLine(text, className = '') {
    const line = document.createElement('div');
    line.className = className;
    line.textContent = text || '';
    return line;
}

function renderMergeCustomerResults(resultsDiv, customers, hiddenInputId, searchInputId) {
    if (customers.length === 0) {
        const emptyResult = mergeCustomerLine(<?= json_encode(t('js.no_customers')) ?>, 'p-2 text-gray-500');
        resultsDiv.replaceChildren(emptyResult);
        return;
    }

    const options = customers.map(customer => {
        const option = document.createElement('div');
        option.className = 'p-2 hover:bg-gray-100 cursor-pointer customer-option';
        option.dataset.customerId = String(customer.id);
        option.appendChild(mergeCustomerLine(customer.name, 'font-medium'));
        if (customer.company) {
            option.appendChild(mergeCustomerLine(customer.company, 'text-sm text-gray-600'));
        }
        option.appendChild(mergeCustomerLine(customer.phone, 'text-sm text-gray-600'));
        option.addEventListener('click', () => {
            document.getElementById(hiddenInputId).value = String(customer.id);
            document.getElementById(searchInputId).value = customer.name || '';
            resultsDiv.classList.add('hidden');
        });
        return option;
    });
    resultsDiv.replaceChildren(...options);
}

// Source customer search functionality
document.getElementById('source_customer_search')?.addEventListener('input', function() {
    const query = this.value.trim();
    const resultsDiv = document.getElementById('source_customer_results');
    
    if (query.length < 2) {
        resultsDiv.classList.add('hidden');
        return;
    }
    
    fetch(`<?= BASE_URL ?>/api/search-customers?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(customers => {
            renderMergeCustomerResults(
                resultsDiv,
                customers,
                'selected_source_customer_id',
                'source_customer_search'
            );
            resultsDiv.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error searching customers:', error);
        });
});

// Destination customer search functionality
document.getElementById('destination_customer_search')?.addEventListener('input', function() {
    const query = this.value.trim();
    const resultsDiv = document.getElementById('destination_customer_results');
    const sourceCustomerId = document.getElementById('selected_source_customer_id')?.value || '<?= $sourceId ?>';
    
    if (query.length < 2) {
        resultsDiv.classList.add('hidden');
        return;
    }
    
    fetch(`<?= BASE_URL ?>/api/search-customers?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(customers => {
            // Filter out the source customer
            const filteredCustomers = customers.filter(customer => customer.id != sourceCustomerId);
            
            renderMergeCustomerResults(
                resultsDiv,
                filteredCustomers,
                'selected_destination_customer_id',
                'destination_customer_search'
            );
            resultsDiv.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error searching customers:', error);
        });
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    const sourceSearchInput = document.getElementById('source_customer_search');
    const sourceResultsDiv = document.getElementById('source_customer_results');
    const destSearchInput = document.getElementById('destination_customer_search');
    const destResultsDiv = document.getElementById('destination_customer_results');
    
    // Hide source results
    if (sourceSearchInput && sourceResultsDiv && 
        !sourceSearchInput.contains(e.target) && !sourceResultsDiv.contains(e.target)) {
        sourceResultsDiv.classList.add('hidden');
    }
    
    // Hide destination results
    if (destSearchInput && destResultsDiv && 
        !destSearchInput.contains(e.target) && !destResultsDiv.contains(e.target)) {
        destResultsDiv.classList.add('hidden');
    }
});
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
