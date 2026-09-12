<?php
$previousWorkOrders = $previousWorkOrders ?? [];
$isWarranty = !empty($isWarranty);
$referenceId = isset($referenceId) && $referenceId !== null ? (int) $referenceId : null;
?>
<div class="mt-6 border-t border-gray-200 pt-4">
    <div class="flex items-start">
        <input id="warranty_is_warranty"
               name="warranty_is_warranty"
               type="checkbox"
               value="1"
               <?= $isWarranty ? 'checked' : '' ?>
               onchange="warrantyToggleReference()"
               class="h-4 w-4 mt-0.5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
        <div class="ml-2">
            <label for="warranty_is_warranty" class="block text-sm font-medium text-gray-700"><?= t('warranty.checkbox') ?></label>
            <p class="mt-1 text-sm text-gray-500"><?= t('warranty.checkbox_help') ?></p>
        </div>
    </div>

    <div id="warrantyReferenceWrap" class="mt-4 <?= $isWarranty ? '' : 'hidden' ?>">
        <?php if (empty($previousWorkOrders)): ?>
            <p class="text-sm text-gray-500"><?= t('warranty.no_previous') ?></p>
        <?php else: ?>
            <label for="warranty_reference_work_order_id" class="block text-sm font-medium text-gray-700"><?= t('warranty.reference') ?></label>
            <select id="warranty_reference_work_order_id"
                    name="warranty_reference_work_order_id"
                    class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                <option value=""><?= t('warranty.reference_none') ?></option>
                <?php foreach ($previousWorkOrders as $previous): ?>
                    <option value="<?= (int) $previous['id'] ?>" <?= $referenceId === (int) $previous['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(motherboard_warranty_option_label($previous)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="mt-1 text-sm text-gray-500"><?= t('warranty.reference_help') ?></p>
        <?php endif; ?>
    </div>
</div>

<script>
function warrantyToggleReference() {
    var checkbox = document.getElementById('warranty_is_warranty');
    var wrap = document.getElementById('warrantyReferenceWrap');
    if (!checkbox || !wrap) {
        return;
    }
    wrap.classList.toggle('hidden', !checkbox.checked);
}
</script>
