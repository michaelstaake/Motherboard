<?php 
$title = t('wo.work_order_label', ['id' => $workOrder['id']]) . ' - ' . ($companyName ?? APP_NAME);
$hideNavigation = true;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(I18n::getInstance()->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { margin: 0; padding: 10px; font-size: 11pt; line-height: 1.3; }
            .no-print { display: none !important; }
            .print-break { page-break-after: always; }
            .print-avoid-break { page-break-inside: avoid; }
            h1, h2, h3 { margin-top: 0; margin-bottom: 0.5rem; }
            .compact { margin-bottom: 0.75rem; }
            .extra-compact { margin-bottom: 0.5rem; }
        }
        body { background: white; }
    </style>
</head>
<body class="bg-white">
    <div class="max-w-full mx-auto p-3">
        <!-- Print Button (hidden when printing) -->
        <div class="no-print mb-6 flex justify-between items-center">
            <a href="<?= BASE_URL ?>/work-orders/view/<?= $workOrder['id'] ?>" class="text-gray-600 hover:text-gray-900">
                <?= t('wo.print_back') ?>
            </a>
            <button onclick="window.print()" class="bg-primary-600 text-white px-4 py-2 rounded hover:bg-primary-700">
                <?= t('wo.print_button') ?>
            </button>
        </div>

        <!-- Row 1: Company Information (Left) | Work Order Number & Date (Right) -->
        <div class="grid grid-cols-2 gap-4 mb-4 print-avoid-break">
            <!-- Company Information -->
            <div class="text-left">
                <?php if (!empty($companyLogoUrl)): ?>
                    <div class="mb-2">
                        <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="<?= htmlspecialchars($companyInfo['company_name'] ?? $companyName ?? APP_NAME) ?>" class="h-12 w-auto">
                    </div>
                <?php else: ?>
                    <h1 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($companyInfo['company_name'] ?? $companyName ?? APP_NAME) ?></h1>
                <?php endif; ?>
                <?php if (!empty($companyInfo['company_address'])): ?>
                    <p class="text-gray-600 text-xs mb-1"><?= nl2br(htmlspecialchars($companyInfo['company_address'])) ?></p>
                <?php endif; ?>
                <div class="text-xs text-gray-600 space-y-0.5">
                    <?php if (!empty($companyInfo['company_phone'])): ?>
                        <div><?= htmlspecialchars($companyInfo['company_phone']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($companyInfo['company_email'])): ?>
                        <div><?= htmlspecialchars($companyInfo['company_email']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($companyInfo['company_website'])): ?>
                        <div><?= htmlspecialchars($companyInfo['company_website']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Work Order Number & Date -->
            <div class="text-right">
                <h2 class="text-2xl font-bold text-gray-900 mb-1"><?= t('wo.work_order_label', ['id' => $workOrder['id']]) ?></h2>
                <p class="text-sm text-gray-600"><?= t('wo.created_at', ['date' => date('M j, Y \\a\\t g:i A', strtotime($workOrder['created_at']))]) ?></p>
            </div>
        </div>

        <!-- Row 2: Customer Information (Left) | Device Information & Accessories (Right) -->
        <div class="grid grid-cols-2 gap-4 mb-4 print-avoid-break">
            <!-- Customer Information -->
            <div class="border border-gray-300 rounded-lg p-3">
                <h3 class="text-sm font-semibold text-gray-900 mb-2"><?= t('wo.customer_info') ?></h3>
                <div class="space-y-1">
                    <p class="text-xs"><strong><?= t('wo.name') ?>:</strong> <?= htmlspecialchars($workOrder['customer_name']) ?></p>
                    <?php if (!empty($workOrder['customer_company'])): ?>
                        <p class="text-xs"><strong><?= t('wo.company') ?>:</strong> <?= htmlspecialchars($workOrder['customer_company']) ?></p>
                    <?php endif; ?>
                    <p class="text-xs"><strong><?= t('wo.phone') ?>:</strong> <?= htmlspecialchars($workOrder['customer_phone']) ?></p>
                    <?php if (!empty($workOrder['customer_email'])): ?>
                        <p class="text-xs"><strong><?= t('wo.email') ?>:</strong> <?= htmlspecialchars($workOrder['customer_email']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Device Information & Accessories -->
            <div class="border border-gray-300 rounded-lg p-3">
                <h3 class="text-sm font-semibold text-gray-900 mb-2"><?= t('wo.device_info') ?></h3>
                <div class="space-y-1">
                    <p class="text-xs"><strong><?= t('wo.computer') ?>:</strong> <?= htmlspecialchars($workOrder['computer'] ?? t('wo.na')) ?></p>
                    <?php if (!empty($workOrder['model'])): ?>
                        <p class="text-xs"><strong><?= t('wo.model') ?>:</strong> <?= htmlspecialchars($workOrder['model']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($workOrder['serial_number'])): ?>
                        <p class="text-xs"><strong><?= t('wo.serial') ?>:</strong> <?= htmlspecialchars($workOrder['serial_number']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($workOrder['imei'])): ?>
                        <p class="text-xs"><strong><?= t('wo.imei') ?>:</strong> <?= htmlspecialchars($workOrder['imei']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($workOrder['remarks'])): ?>
                        <p class="text-xs whitespace-pre-wrap"><strong><?= t('wo.remarks') ?>:</strong> <?= nl2br(htmlspecialchars($workOrder['remarks'])) ?></p>
                    <?php endif; ?>
                    <p class="text-xs"><strong><?= t('wo.accessories') ?>:</strong> 
                        <?php 
                        if (!empty($workOrder['accessories'])) {
                            $accessories = json_decode($workOrder['accessories'], true);
                            if (is_array($accessories)) {
                                // Filter out empty values
                                $accessories = array_filter($accessories, function($item) {
                                    return !empty(trim($item));
                                });
                                
                                if (!empty($accessories)) {
                                    echo htmlspecialchars(implode(', ', array_map(function($item) { return tlabel('accessory', trim($item)); }, $accessories)));
                                } else {
                                    echo t('wo.na');
                                }
                            } else {
                                // If not JSON, treat as plain text
                                echo htmlspecialchars($workOrder['accessories']);
                            }
                        } else {
                            echo t('wo.na');
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Row 3: Problem Description (Full Width) -->
        <div class="border border-gray-300 rounded-lg p-3 mb-4 print-avoid-break">
            <h3 class="text-sm font-semibold text-gray-900 mb-2"><?= t('wo.problem') ?></h3>
            <p class="text-xs text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($workOrder['description']) ?></p>
        </div>

        <?php if (!empty($workOrder['resolution'])): ?>
            <div class="border border-gray-300 rounded-lg p-3 mb-4 print-avoid-break">
                <h3 class="text-sm font-semibold text-gray-900 mb-2"><?= t('wo.resolution') ?></h3>
                <p class="text-xs text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($workOrder['resolution']) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($workOrder['notes'])): ?>
            <div class="border border-gray-300 rounded-lg p-3 mb-4 print-avoid-break">
                <h3 class="text-sm font-semibold text-gray-900 mb-2"><?= t('wo.notes') ?></h3>
                <p class="text-xs text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($workOrder['notes']) ?></p>
            </div>
        <?php endif; ?>

        <?php Hooks::doAction('work_order.print.before_attachments', $workOrder ?? []); ?>

        <?php if (!empty($attachments)): ?>
            <div class="border border-gray-300 rounded-lg p-3 mb-4 print-avoid-break">
                <h3 class="text-sm font-semibold text-gray-900 mb-2"><?= t('wo.attachments') ?></h3>
                <ul class="space-y-1">
                    <?php foreach ($attachments as $attachment): ?>
                        <li class="text-xs text-gray-700">
                            <?= htmlspecialchars($attachment['original_filename']) ?>
                            <?php if (!empty($attachment['description'])): ?>
                                — <?= htmlspecialchars($attachment['description']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Row 4: Disclaimer (Full Width) -->
        <?php if (!empty($companyInfo['work_order_disclaimer'])): ?>
            <div class="border-t border-gray-300 pt-3 mt-4 print-avoid-break">
                <h3 class="text-xs font-semibold text-gray-900 mb-2"><?= t('wo.terms') ?></h3>
                <div class="text-xs text-gray-600 leading-tight">
                    <?= strip_tags($companyInfo['work_order_disclaimer'], '<p><b>') ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
            $showCustomerSignature = !empty($companyInfo['print_customer_signature']);
            $showTechnicianSignature = !empty($companyInfo['print_technician_signature']);
        ?>
        <?php if ($showCustomerSignature || $showTechnicianSignature): ?>
        <div class="border-t border-gray-300 pt-3 mt-4">
            <div class="grid grid-cols-2 gap-8">
                <?php if ($showCustomerSignature): ?>
                <div>
                    <p class="text-xs font-semibold text-gray-900 mb-2"><?= t('wo.signature') ?></p>
                    <div class="border-b border-gray-400 h-8 mb-1"></div>
                </div>
                <?php endif; ?>
                <?php if ($showTechnicianSignature): ?>
                <div>
                    <p class="text-xs font-semibold text-gray-900 mb-2"><?= t('wo.tech_signature') ?></p>
                    <div class="border-b border-gray-400 h-8 mb-1"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Automatically open print dialog when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
