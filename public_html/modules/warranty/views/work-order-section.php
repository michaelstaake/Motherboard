<?php
$warranty = $warranty ?? null;
$previousWorkOrders = $previousWorkOrders ?? [];
$canEdit = !empty($canEdit);
$csrf_token = $csrf_token ?? '';
$workOrderId = (int) ($workOrder['id'] ?? 0);
$referenceId = $warranty['reference_work_order_id'] ?? null;

// The card carries the reference work order and nothing else, so a read-only viewer with no
// reference to see would get an empty shell. Skip it entirely for them.
$showReference = $warranty && $referenceId;
?>

<?php if ($canEdit || $showReference): ?>
<div class="mt-6 bg-white shadow rounded-lg">
    <div class="px-6 py-4 flex items-center justify-between<?= $showReference ? ' border-b border-gray-200' : '' ?>">
        <h2 class="text-lg font-medium text-gray-900"><?= t('warranty.section') ?></h2>
        <?php if ($canEdit): ?>
            <button type="button" onclick="openWarrantyModal()" class="inline-flex items-center px-3 py-1.5 border text-sm font-medium rounded-md <?= $warranty ? 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50' : 'border-transparent text-white bg-primary-600 hover:bg-primary-700' ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <?= $warranty ? t('warranty.manage') : t('warranty.set') ?>
            </button>
        <?php endif; ?>
    </div>
    <?php if ($showReference): ?>
        <div class="px-6 py-4">
            <a href="<?= BASE_URL ?>/work-orders/view/<?= (int) $referenceId ?>" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                <?= t('warranty.reference_label', ['number' => (string) (int) $referenceId]) ?>
            </a>
            <?php $referenceDate = ldate($warranty['reference_created_at'] ?? '', 'M j, Y'); ?>
            <?php if ($referenceDate !== ''): ?>
                <div class="text-sm text-gray-600"><?= htmlspecialchars($referenceDate) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<!-- Warranty Modal -->
<div id="warrantyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900"><?= $warranty ? t('warranty.manage') : t('warranty.set') ?></h3>
                <button type="button" onclick="closeWarrantyModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/work-orders/view/<?= $workOrderId ?>/warranty">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="space-y-4">
                    <div class="flex items-start">
                        <input id="warranty_modal_is_warranty"
                               name="warranty_is_warranty"
                               type="checkbox"
                               value="1"
                               <?= $warranty ? 'checked' : '' ?>
                               onchange="warrantyModalToggleReference()"
                               class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <div class="ml-2">
                            <label for="warranty_modal_is_warranty" class="block text-sm font-medium text-gray-700"><?= t('warranty.checkbox') ?></label>
                            <p class="mt-1 text-sm text-gray-500"><?= t('warranty.checkbox_help') ?></p>
                        </div>
                    </div>
                    <div id="warrantyModalReferenceWrap" class="<?= $warranty ? '' : 'hidden' ?>">
                        <?php if (empty($previousWorkOrders)): ?>
                            <p class="text-sm text-gray-500"><?= t('warranty.no_previous') ?></p>
                        <?php else: ?>
                            <label for="warranty_modal_reference" class="block text-sm font-medium text-gray-700"><?= t('warranty.reference') ?></label>
                            <select id="warranty_modal_reference"
                                    name="warranty_reference_work_order_id"
                                    class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                <option value=""><?= t('warranty.reference_none') ?></option>
                                <?php foreach ($previousWorkOrders as $previous): ?>
                                    <option value="<?= (int) $previous['id'] ?>" <?= (int) $referenceId === (int) $previous['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(motherboard_warranty_option_label($previous)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-sm text-gray-500"><?= t('warranty.reference_help') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm">
                        <?= t('common.save') ?>
                    </button>
                    <button type="button" onclick="closeWarrantyModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        <?= t('common.cancel') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openWarrantyModal() {
    document.getElementById('warrantyModal').classList.remove('hidden');
}

function closeWarrantyModal() {
    document.getElementById('warrantyModal').classList.add('hidden');
}

function warrantyModalToggleReference() {
    var checkbox = document.getElementById('warranty_modal_is_warranty');
    var wrap = document.getElementById('warrantyModalReferenceWrap');
    if (!checkbox || !wrap) {
        return;
    }
    wrap.classList.toggle('hidden', !checkbox.checked);
}
</script>
<?php endif; ?>
