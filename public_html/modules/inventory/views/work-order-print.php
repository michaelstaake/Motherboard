<?php
$assigned = $assigned ?? [];
$totals = motherboard_inventory_work_order_totals($assigned);
?>
<div class="border border-gray-300 rounded-lg p-3 mb-4 print-avoid-break">
    <h3 class="text-sm font-semibold text-gray-900 mb-2"><?= t('inventory.wo_section') ?></h3>
    <table class="w-full text-xs text-gray-700">
        <thead>
            <tr>
                <th class="text-left py-1"><?= t('inventory.product_name') ?></th>
                <th class="text-left py-1"><?= t('inventory.quantity') ?></th>
                <th class="text-left py-1"><?= t('inventory.price') ?></th>
                <th class="text-right py-1"><?= t('inventory.line_total') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assigned as $line): ?>
                <tr>
                    <td class="py-1">
                        <div class="font-medium text-gray-900"><?= htmlspecialchars($line['product_name']) ?></div>
                        <?php if (!empty($line['item_number'])): ?>
                            <div class="text-gray-500"><?= htmlspecialchars($line['item_number']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($line['is_custom']) && !empty($line['description'])): ?>
                            <div class="text-gray-500"><?= nl2br(htmlspecialchars($line['description'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="py-1"><?= (int) $line['quantity'] ?></td>
                    <td class="py-1">
                        <?= htmlspecialchars(motherboard_inventory_format_price($line['unit_price'])) ?>
                        <?php if (!empty($line['taxable'])): ?>
                            <span title="<?= htmlspecialchars(t('inventory.taxable')) ?>"><?= t('inventory.taxable_mark') ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="py-1 text-right"><?= htmlspecialchars(motherboard_inventory_format_price($line['line_total'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="mt-2 text-xs text-gray-700 text-right space-y-0.5">
        <div><?= t('inventory.taxable_total') ?>: <?= htmlspecialchars(motherboard_inventory_format_price($totals['taxable'])) ?></div>
        <div><?= t('inventory.nontaxable_total') ?>: <?= htmlspecialchars(motherboard_inventory_format_price($totals['nontaxable'])) ?></div>
        <div><?= t('inventory.tax_amount', ['rate' => motherboard_inventory_format_tax_rate($totals['tax_rate'])]) ?>: <?= htmlspecialchars(motherboard_inventory_format_price($totals['tax'])) ?></div>
        <div class="font-semibold"><?= t('inventory.grand_total') ?>: <?= htmlspecialchars(motherboard_inventory_format_price($totals['grand_total'])) ?></div>
    </div>
</div>
