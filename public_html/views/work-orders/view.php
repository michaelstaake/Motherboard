<?php 
$title = t('wo.work_order_label', ['id' => $workOrder['id']]) . ' - ' . ($companyName ?? APP_NAME);
ob_start(); 
?>

<div class="py-8">
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900"><?= t('wo.work_order_label', ['id' => $workOrder['id']]) ?></h1>
                    
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        <?php 
                        switch($workOrder['status']) {
                            case 'Open': echo 'bg-orange-100 text-orange-800'; break;
                            case 'In Progress': echo 'bg-yellow-100 text-yellow-800'; break;
                            case 'Awaiting Parts': echo 'bg-purple-100 text-purple-800'; break;
                            case 'Closed': echo 'bg-green-100 text-green-800'; break;
                            case 'Picked Up': echo 'bg-gray-100 text-gray-800'; break;
                            default: echo 'bg-gray-100 text-gray-800';
                        }
                        ?>">
                        <?= htmlspecialchars(tlabel('status', $workOrder['status'])) ?>
                    </span>
                    
                    <!-- Priority Badge (only show if Priority) -->
                    <?php if ($workOrder['priority'] === 'Priority'): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        <?= t('priority.Priority') ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="<?= BASE_URL ?>/work-orders/print/<?= $workOrder['id'] ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <?= t('common.print') ?>
                </a>
                <?php if ($_SESSION['user_group'] === 'Admin'): ?>
                <button type="button" onclick="openDeleteModal()" class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <?= t('common.delete') ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (isset($error) && $error): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
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
        <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
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

    <?php
    $presetAccessories = ['Power Adapter', 'External Storage', 'Keyboard', 'Mouse', 'Monitor', 'Printer'];
    $storedAccessories = json_decode($workOrder['accessories'] ?? '[]', true);
    if (!is_array($storedAccessories)) {
        $storedAccessories = [];
    }
    $storedAccessories = array_values(array_filter(array_map('trim', $storedAccessories)));
    $selectedAccessories = array_values(array_filter($storedAccessories, function ($item) use ($presetAccessories) {
        return in_array($item, $presetAccessories, true);
    }));
    $customAccessory = '';
    foreach ($storedAccessories as $accessory) {
        if (!in_array($accessory, $presetAccessories, true)) {
            $customAccessory = $accessory;
            break;
        }
    }
    $hasAccessories = !empty($storedAccessories);
    $showDeviceEdit = !empty($editDevice);
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Work Order Details -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900"><?= t('wo.details') ?></h2>
                    <?php if ($canEdit): ?>
                    <button type="button"
                            id="deviceEditButton"
                            onclick="openDeviceEdit()"
                            class="<?= $showDeviceEdit ? 'hidden ' : '' ?>inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="-ml-0.5 mr-2 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <?= t('wo.edit_device') ?>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('wo.date_opened') ?></dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= ldate($workOrder['created_at'], 'M j, Y g:i A') ?></dd>
                        </div>
                        <?php if (($workOrder['status'] === 'Closed' || $workOrder['status'] === 'Picked Up') && !empty($workOrder['closed_at'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('wo.date_closed') ?></dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= ldate($workOrder['closed_at'], 'M j, Y g:i A') ?></dd>
                        </div>
                        <?php endif; ?>
                    </dl>

                    <div id="deviceDetailsView" class="<?= $showDeviceEdit ? 'hidden ' : '' ?>mt-4 border-t border-gray-100 pt-4">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.computer') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($workOrder['computer'] ?? '') ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.device_model') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= $workOrder['model'] ? htmlspecialchars($workOrder['model']) : '—' ?></dd>
                            </div>
                            <?php if (!empty($workOrder['serial_number'])): ?>
                            <div>
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.serial') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($workOrder['serial_number']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($workOrder['imei'])): ?>
                            <div>
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.imei') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($workOrder['imei']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($workOrder['remarks'])): ?>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.remarks') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($workOrder['remarks'])) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if ($hasAccessories): ?>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.accessories') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <div class="flex flex-wrap gap-2">
                                    <?php foreach ($storedAccessories as $accessory): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><?= htmlspecialchars(tlabel('accessory', trim($accessory))) ?></span>
                                    <?php endforeach; ?>
                                    </div>
                                </dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <?php if ($canEdit): ?>
                    <div id="deviceDetailsEdit" class="<?= $showDeviceEdit ? '' : 'hidden ' ?>mt-4 border-t border-gray-100 pt-4">
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="update_section" value="device">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="device_computer" class="block text-sm font-medium text-gray-700"><?= t('wo.computer') ?> *</label>
                                    <input type="text"
                                           name="computer"
                                           id="device_computer"
                                           required
                                           value="<?= htmlspecialchars($workOrder['computer'] ?? '') ?>"
                                           class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
                                           placeholder="<?= htmlspecialchars(t('wo.computer_ph')) ?>">
                                </div>
                                <div>
                                    <label for="device_model" class="block text-sm font-medium text-gray-700"><?= t('wo.model') ?></label>
                                    <input type="text"
                                           name="model"
                                           id="device_model"
                                           value="<?= htmlspecialchars($workOrder['model'] ?? '') ?>"
                                           class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="device_serial_number" class="block text-sm font-medium text-gray-700"><?= t('wo.serial') ?></label>
                                    <input type="text"
                                           name="serial_number"
                                           id="device_serial_number"
                                           value="<?= htmlspecialchars($workOrder['serial_number'] ?? '') ?>"
                                           class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                </div>
                                <div>
                                    <label for="device_imei" class="block text-sm font-medium text-gray-700"><?= t('wo.imei') ?></label>
                                    <input type="text"
                                           name="imei"
                                           id="device_imei"
                                           value="<?= htmlspecialchars($workOrder['imei'] ?? '') ?>"
                                           class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                </div>
                            </div>

                            <div>
                                <label for="device_remarks" class="block text-sm font-medium text-gray-700"><?= t('wo.remarks') ?></label>
                                <textarea name="remarks"
                                          id="device_remarks"
                                          rows="3"
                                          class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
                                          placeholder="<?= htmlspecialchars(t('wo.remarks_ph')) ?>"><?= htmlspecialchars($workOrder['remarks'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2"><?= t('wo.accessories') ?></label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                    <?php foreach ($presetAccessories as $accessory): ?>
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               name="accessories[]"
                                               value="<?= $accessory ?>"
                                               <?= in_array($accessory, $selectedAccessories, true) ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-sm text-gray-700"><?= tlabel('accessory', $accessory) ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-2">
                                    <input type="text"
                                           name="accessories[]"
                                           value="<?= htmlspecialchars($customAccessory) ?>"
                                           placeholder="<?= htmlspecialchars(t('wo.other')) ?>"
                                           class="block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="device_username" class="block text-sm font-medium text-gray-700"><?= t('wo.username') ?></label>
                                    <input type="text"
                                           name="username"
                                           id="device_username"
                                           value="<?= htmlspecialchars($workOrder['username'] ?? '') ?>"
                                           class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                </div>
                                <div>
                                    <label for="device_password" class="block text-sm font-medium text-gray-700"><?= t('wo.password') ?></label>
                                    <input type="text"
                                           name="password"
                                           id="device_password"
                                           value="<?= htmlspecialchars($workOrder['password'] ?? '') ?>"
                                           class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                </div>
                            </div>

                            <div class="flex items-center gap-3 pt-2">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <?= t('wo.save_device') ?>
                                </button>
                                <button type="button" onclick="closeDeviceEdit()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    <?= t('common.cancel') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.computer') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($workOrder['computer'] ?? '') ?></dd>
                            </div>
                            <?php if (!empty($workOrder['model'])): ?>
                            <div>
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.device_model') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($workOrder['model']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if ($workOrder['serial_number']): ?>
                            <div>
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.serial') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($workOrder['serial_number']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($workOrder['imei'])): ?>
                            <div>
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.imei') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($workOrder['imei']) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($workOrder['remarks'])): ?>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.remarks') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($workOrder['remarks'])) ?></dd>
                            </div>
                            <?php endif; ?>
                            <?php if ($hasAccessories): ?>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500"><?= t('wo.accessories') ?></dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <div class="flex flex-wrap gap-2">
                                    <?php foreach ($storedAccessories as $accessory): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><?= htmlspecialchars(tlabel('accessory', trim($accessory))) ?></span>
                                    <?php endforeach; ?>
                                    </div>
                                </dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <?php endif; ?>

                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 mt-4 border-t border-gray-100 pt-4">
                        <?php if ($workOrder['technician_display_name']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('wo.assigned_to') ?></dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($workOrder['technician_display_name']) ?></dd>
                        </div>
                        <?php endif; ?>
                        <?php if (($_SESSION['user_group'] !== 'Limited') && ($workOrder['username'] || $workOrder['password'])): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500"><?= t('wo.login_info') ?></dt>
                            <dd class="mt-1">
                                <button type="button" onclick="openLoginModal()" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <?= t('wo.view_login') ?>
                                </button>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
        <!-- Customer Information -->
        <div>
            <a href="<?= BASE_URL ?>/customers/view/<?= $workOrder['customer_id'] ?>" class="block bg-white shadow rounded-lg hover:bg-gray-50 transition-colors duration-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900"><?= t('wo.customer_info') ?></h2>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-4">
                        <!-- Name and Company -->
                        <div>
                            <div class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($workOrder['customer_name']) ?></div>
                            <?php if ($workOrder['customer_company']): ?>
                            <div class="text-sm text-gray-600"><?= htmlspecialchars($workOrder['customer_company']) ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Phone and Email -->
                        <?php if ($workOrder['customer_phone'] || $workOrder['customer_email']): ?>
                        <div>
                            <?php if ($workOrder['customer_phone']): ?>
                            <div class="text-base font-medium text-gray-900">
                                <?= htmlspecialchars($workOrder['customer_phone']) ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($workOrder['customer_email']): ?>
                            <div class="text-sm text-gray-600">
                                <?= htmlspecialchars($workOrder['customer_email']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    </div>
        
            
    <!-- Work Order Contents -->
    <?php if ($_SESSION['user_group'] !== 'Limited'): ?>
        <div class="grid grid-cols-1 gap-6">
            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-4 py-5">
                    <h3 class="text-lg font-medium text-gray-900 mb-4"><?= t('wo.contents') ?></h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700"><?= t('common.status') ?></label>
                                <select id="status" name="status" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                    <option value="Open" <?= $workOrder['status'] === 'Open' ? 'selected' : '' ?>><?= t('status.Open') ?></option>
                                    <option value="In Progress" <?= $workOrder['status'] === 'In Progress' ? 'selected' : '' ?>><?= t('status.In Progress') ?></option>
                                    <option value="Awaiting Parts" <?= $workOrder['status'] === 'Awaiting Parts' ? 'selected' : '' ?>><?= t('status.Awaiting Parts') ?></option>
                                    <option value="Closed" <?= $workOrder['status'] === 'Closed' ? 'selected' : '' ?>><?= t('status.Closed') ?></option>
                                    <option value="Picked Up" <?= $workOrder['status'] === 'Picked Up' ? 'selected' : '' ?>><?= t('status.Picked Up') ?></option>
                                </select>
                            </div>

                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700"><?= t('common.priority') ?></label>
                                <select id="priority" name="priority" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                    <option value="Standard" <?= $workOrder['priority'] === 'Standard' ? 'selected' : '' ?>><?= t('priority.Standard') ?></option>
                                    <option value="Priority" <?= $workOrder['priority'] === 'Priority' ? 'selected' : '' ?>><?= t('priority.Priority') ?></option>
                                </select>
                            </div>

                            <div>
                                <label for="assigned_to" class="block text-sm font-medium text-gray-700"><?= t('wo.assigned_to') ?></label>
                                <select id="assigned_to" name="assigned_to" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                                    <option value=""><?= t('common.unassigned') ?></option>
                                    <?php foreach ($technicians as $tech): ?>
                                        <option value="<?= $tech['id'] ?>" <?= $workOrder['assigned_to'] == $tech['id'] ? 'selected' : '' ?>>
                                            <?= !empty($tech['name']) ? htmlspecialchars($tech['name']) : htmlspecialchars($tech['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700"><?= t('wo.description') ?></label>
                                <textarea id="description" name="description" rows="5" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"><?= htmlspecialchars($workOrder['description']) ?></textarea>
                            </div>

                            <div>
                                <label for="resolution" class="block text-sm font-medium text-gray-700"><?= t('wo.resolution') ?></label>
                                <textarea id="resolution" name="resolution" rows="5" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"><?= htmlspecialchars($workOrder['resolution'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700"><?= t('wo.notes') ?></label>
                                <textarea id="notes" name="notes" rows="5" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"><?= htmlspecialchars($workOrder['notes'] ?? '') ?></textarea>
                            </div>

                            <div class="pt-4">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <?= t('wo.update') ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-6">
            <div class="mt-6 bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900"><?= t('wo.contents') ?></h2>
                </div>
                <div class="px-6 py-4">
                    <div class="sm:col-span-2 mb-4">
                        <dt class="text-sm font-medium text-gray-500"><?= t('wo.description') ?></dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= nl2br(htmlspecialchars($workOrder['description'])) ?></dd>
                    </div>
                    <?php if ($workOrder['resolution']): ?>
                    <div class="sm:col-span-2 mb-4">
                        <dt class="text-sm font-medium text-gray-500"><?= t('wo.resolution') ?></dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= nl2br(htmlspecialchars($workOrder['resolution'])) ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if ($workOrder['notes']): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500"><?= t('wo.notes') ?></dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= nl2br(htmlspecialchars($workOrder['notes'])) ?></dd>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php Hooks::doAction('work_order.view.before_attachments', $workOrder ?? [], [
        'canEdit' => $canEdit ?? false,
        'csrf_token' => $csrf_token ?? '',
    ]); ?>

    <!-- Attachments -->
    <div class="grid grid-cols-1 gap-6">
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900"><?= t('wo.attachments') ?></h2>
                <?php if ($canEdit): ?>
                    <button type="button" onclick="openAddAttachmentModal()" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <?= t('wo.add_attachment') ?>
                    </button>
                <?php endif; ?>
            </div>
            <div class="px-6 py-4">
                <?php if (!empty($attachments)): ?>
                    <div class="space-y-4">
                        <?php foreach ($attachments as $attachment): ?>
                            <div class="border border-gray-200 rounded-md p-4">
                                <div class="flex items-start gap-4">
                                    <?php if ($attachmentModel->isDisplayableImage($attachment)): ?>
                                        <button type="button" onclick="openAttachmentLightbox('<?= BASE_URL ?>/work-orders/attachments/<?= $attachment['id'] ?>/download', <?= htmlspecialchars(json_encode($attachment['original_filename']), ENT_QUOTES) ?>)" class="flex-shrink-0">
                                            <img src="<?= BASE_URL ?>/work-orders/attachments/<?= $attachment['id'] ?>/download" alt="<?= htmlspecialchars($attachment['original_filename']) ?>" class="h-16 w-16 object-cover rounded border border-gray-200">
                                        </button>
                                    <?php else: ?>
                                        <div class="flex-shrink-0 h-16 w-16 rounded border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($attachment['original_filename']) ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($attachmentModel->formatSize($attachment['file_size'] ?? 0)) ?> · <?= htmlspecialchars($attachmentModel->locationLabel($attachment)) ?></p>
                                        <?php if (!empty($attachment['description'])): ?>
                                            <p class="text-sm text-gray-700 mt-2 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($attachment['description'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="relative flex-shrink-0" data-attachment-menu>
                                        <button type="button" onclick="toggleAttachmentMenu(event, this)" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" aria-haspopup="true" aria-expanded="false">
                                            <?= t('common.actions') ?>
                                            <svg class="-mr-1 ml-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <div class="hidden origin-top-right absolute right-0 mt-2 w-52 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20" onclick="event.stopPropagation()">
                                            <div class="py-1">
                                                <a href="<?= BASE_URL ?>/work-orders/attachments/<?= $attachment['id'] ?>/download" download="<?= htmlspecialchars($attachment['original_filename']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <?= t('wo.download_attachment') ?>
                                                </a>
                                                <?php if ($canEdit): ?>
                                                    <button type="button" onclick="openEditAttachmentModal(<?= (int) $attachment['id'] ?>, <?= htmlspecialchars(json_encode($attachment['original_filename']), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($attachment['description'] ?? ''), ENT_QUOTES) ?>)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <?= t('wo.edit_description') ?>
                                                    </button>
                                                    <button type="button" onclick="openRemoveAttachmentModal(<?= (int) $attachment['id'] ?>, <?= htmlspecialchars(json_encode($attachment['original_filename']), ENT_QUOTES) ?>)" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                                        <?= t('wo.delete_attachment') ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500"><?= t('wo.no_attachments') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <!-- Activity Log -->
        <?php if (!empty($workOrderLogs)): ?>
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900"><?= t('wo.activity') ?></h2>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-3">
                    <?php foreach ($workOrderLogs as $log): ?>
                    <div class="flex items-start space-x-3 py-2 border-b border-gray-100 last:border-b-0">
                        <div class="flex-shrink-0">
                            <span class="h-6 w-6 rounded-full bg-gray-400 flex items-center justify-center">
                                <svg class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <?php 
                                    $details = $log['details'] ?? '';
                                    $hasExtraData = strpos($details, '|||OLD:') !== false;
                                    
                                    if ($hasExtraData) {
                                        // Extract the main message (everything before |||OLD:)
                                        $mainDetails = explode('|||OLD:', $details)[0];
                                        echo '<p class="text-sm text-gray-900">' . htmlspecialchars($mainDetails) . '</p>';
                                        
                                        // Add "View Details" button for description/resolution/notes changes
                                        echo '<button type="button" data-change-details="' . htmlspecialchars(base64_encode($details), ENT_QUOTES, 'UTF-8') . '" class="mt-1 text-xs text-primary-600 hover:text-primary-500">' . htmlspecialchars(t('wo.view_details')) . '</button>';
                                    } else {
                                        echo '<p class="text-sm text-gray-900">' . htmlspecialchars($details) . '</p>';
                                    }
                                    ?>
                                    <p class="text-xs text-gray-500 mt-1"><?= t('common.by', ['name' => htmlspecialchars($log['user_display_name'] ?? $log['username'] ?? t('common.unknown'))]) ?></p>
                                </div>
                                <div class="flex-shrink-0 ml-4">
                                    <span class="text-xs text-gray-500"><?= ldate($log['created_at'], 'M j, g:i A') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

<!-- Change Details Modal -->
<div id="changeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900"><?= t('wo.change_details') ?></h3>
                <button type="button" onclick="closeChangeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2"><?= t('wo.before') ?></h4>
                    <div class="bg-red-50 border border-red-200 rounded-md p-3">
                        <pre id="beforeContent" class="text-sm text-gray-900 whitespace-pre-wrap font-mono"></pre>
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2"><?= t('wo.after') ?></h4>
                    <div class="bg-green-50 border border-green-200 rounded-md p-3">
                        <pre id="afterContent" class="text-sm text-gray-900 whitespace-pre-wrap font-mono"></pre>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="closeChangeModal()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    <?= t('common.close') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Login Information Modal -->
<div id="loginModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900"><?= t('wo.login_info') ?></h3>
                <button type="button" onclick="closeLoginModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-4">
                <?php if ($_SESSION['user_group'] !== 'Limited' && $workOrder['username']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?= t('wo.username') ?></label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="text" id="usernameField" value="<?= htmlspecialchars($workOrder['username']) ?>" readonly class="block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm bg-gray-50 text-sm">
                        <button type="button" onclick="copyToClipboard('usernameField')" class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($_SESSION['user_group'] !== 'Limited' && $workOrder['password']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?= t('wo.password') ?></label>
                    <div class="mt-1 flex items-center space-x-2">
                        <input type="password" id="passwordField" value="<?= htmlspecialchars($workOrder['password']) ?>" readonly class="block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm bg-gray-50 text-sm">
                        <button type="button" onclick="togglePasswordVisibility()" class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                            <svg id="eyeIcon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                        <button type="button" onclick="copyToClipboard('passwordField')" class="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (($_SESSION['user_group'] === 'Limited') || (!$workOrder['username'] && !$workOrder['password'])): ?>
                <div class="text-center text-gray-500 py-4">
                    <?php if ($_SESSION['user_group'] === 'Limited'): ?>
                        <?= t('wo.login_no_permission') ?>
                    <?php else: ?>
                        <?= t('wo.login_none') ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="button" onclick="closeLoginModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    <?= t('common.close') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<?php if ($_SESSION['user_group'] === 'Admin'): ?>
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        <?= t('wo.delete_title') ?>
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            <?= t('wo.delete_confirm', ['id' => $workOrder['id']]) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <form method="POST" action="<?= BASE_URL ?>/work-orders/delete/<?= $workOrder['id'] ?>" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <?= t('wo.delete_title') ?>
                    </button>
                </form>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    <?= t('common.cancel') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add Attachment Modal -->
<?php if ($canEdit): ?>
<div id="addAttachmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900"><?= t('wo.add_attachment') ?></h3>
                <button type="button" onclick="closeAddAttachmentModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/work-orders/view/<?= $workOrder['id'] ?>/attachments" enctype="multipart/form-data" data-attachment-upload>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) $attachmentMaxBytes ?>">
                <div class="space-y-4">
                    <div>
                        <label for="attachment" class="block text-sm font-medium text-gray-700"><?= t('wo.attachment_file') ?></label>
                        <input type="file" name="attachment" id="attachment" required class="mt-1 block w-full text-sm text-gray-700" data-max-bytes="<?= (int) $attachmentMaxBytes ?>" data-allowed="<?= htmlspecialchars(implode(',', $attachmentModel->allowedExtensions())) ?>"<?php if (!$attachmentModel->allowsAllTypes()): ?> accept="<?= htmlspecialchars(implode(',', array_map(function ($ext) { return '.' . $ext; }, $attachmentModel->allowedExtensions()))) ?>"<?php endif; ?>>
                        <p class="mt-1 text-xs text-gray-500">
                            <?= t('wo.attachment_limits', ['size' => htmlspecialchars($attachmentModel->formatSize($attachmentMaxBytes)), 'types' => htmlspecialchars($attachmentAllowedLabel)]) ?>
                        </p>
                        <p class="mt-2 text-sm text-red-600 hidden" data-attachment-error></p>
                    </div>
                    <div>
                        <label for="attachment_description" class="block text-sm font-medium text-gray-700"><?= t('wo.attachment_description') ?></label>
                        <textarea id="attachment_description" name="attachment_description" rows="3" placeholder="<?= htmlspecialchars(t('wo.attachment_description_ph')) ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"></textarea>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm">
                        <?= t('wo.add_attachment') ?>
                    </button>
                    <button type="button" onclick="closeAddAttachmentModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        <?= t('common.cancel') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Attachment Description Modal -->
<?php if ($canEdit): ?>
<div id="editAttachmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900"><?= t('wo.edit_description') ?></h3>
                <button type="button" onclick="closeEditAttachmentModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p id="editAttachmentFilename" class="text-sm text-gray-500 mb-4"></p>
            <form id="editAttachmentForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <label for="edit_attachment_description" class="block text-sm font-medium text-gray-700"><?= t('wo.attachment_description') ?></label>
                <textarea id="edit_attachment_description" name="attachment_description" rows="4" placeholder="<?= htmlspecialchars(t('wo.attachment_description_ph')) ?>" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"></textarea>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 sm:ml-3 sm:w-auto sm:text-sm">
                        <?= t('wo.save_description') ?>
                    </button>
                    <button type="button" onclick="closeEditAttachmentModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        <?= t('common.cancel') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Attachment Modal -->
<?php if ($canEdit): ?>
<div id="removeAttachmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900"><?= t('wo.delete_attachment') ?></h3>
                    <p class="mt-2 text-sm text-gray-500" id="removeAttachmentMessage"></p>
                </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <form id="removeAttachmentForm" method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                        <?= t('wo.delete_attachment') ?>
                    </button>
                </form>
                <button type="button" onclick="closeRemoveAttachmentModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                    <?= t('common.cancel') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Attachment Image Lightbox -->
<div id="attachmentLightbox" class="fixed inset-0 bg-black bg-opacity-80 hidden z-50 flex items-center justify-center p-4" onclick="closeAttachmentLightbox()">
    <button type="button" onclick="closeAttachmentLightbox()" class="absolute top-4 right-4 text-white hover:text-gray-300">
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
    <img id="attachmentLightboxImage" src="" alt="" class="max-h-full max-w-full object-contain" onclick="event.stopPropagation()">
</div>

<script>
function openDeviceEdit() {
    document.getElementById('deviceDetailsView')?.classList.add('hidden');
    document.getElementById('deviceDetailsEdit')?.classList.remove('hidden');
    document.getElementById('deviceEditButton')?.classList.add('hidden');
}

function closeDeviceEdit() {
    document.getElementById('deviceDetailsEdit')?.classList.add('hidden');
    document.getElementById('deviceDetailsView')?.classList.remove('hidden');
    document.getElementById('deviceEditButton')?.classList.remove('hidden');
}

function openLoginModal() {
    document.getElementById('loginModal').classList.remove('hidden');
}

function closeLoginModal() {
    document.getElementById('loginModal').classList.add('hidden');
}

function togglePasswordVisibility() {
    const passwordField = document.getElementById('passwordField');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
        `;
    } else {
        passwordField.type = 'password';
        eyeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
    }
}

function copyToClipboard(fieldId) {
    const field = document.getElementById(fieldId);
    field.select();
    field.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        // Show temporary success message
        const button = event.target.closest('button');
        const originalHTML = button.innerHTML;
        button.innerHTML = '<svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
        setTimeout(() => {
            button.innerHTML = originalHTML;
        }, 1000);
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
}

// Close modal when clicking outside
document.getElementById('loginModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLoginModal();
    }
});

// Change modal functions
function openChangeModal(detailsData) {
    try {
        const details = atob(detailsData);
        const parts = details.split('|||');
        
        if (parts.length >= 3) {
            const oldData = parts[1].replace('OLD:', '');
            const newData = parts[2].replace('NEW:', '');
            
            // Decode base64 content
            const beforeContent = atob(oldData);
            const afterContent = atob(newData);
            
            document.getElementById('beforeContent').textContent = beforeContent || <?= json_encode(t('common.empty')) ?>;
            document.getElementById('afterContent').textContent = afterContent || <?= json_encode(t('common.empty')) ?>;
            
            document.getElementById('changeModal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error parsing change details:', error);
        alert(<?= json_encode(t('js.change_details_error')) ?>);
    }
}

document.querySelectorAll('[data-change-details]').forEach(function(button) {
    button.addEventListener('click', function() {
        openChangeModal(this.dataset.changeDetails);
    });
});

function closeChangeModal() {
    document.getElementById('changeModal').classList.add('hidden');
}

// Close change modal when clicking outside
document.getElementById('changeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeChangeModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLoginModal();
        closeChangeModal();
        if (document.getElementById('deleteModal')) {
            closeDeleteModal();
        }
        closeRemoveAttachmentModal();
        closeEditAttachmentModal();
        closeAddAttachmentModal();
        closeAttachmentMenus();
        closeAttachmentLightbox();
    }
});

function openAttachmentLightbox(url, filename) {
    const modal = document.getElementById('attachmentLightbox');
    const image = document.getElementById('attachmentLightboxImage');
    image.src = url;
    image.alt = filename;
    modal.classList.remove('hidden');
}

function closeAttachmentLightbox() {
    const modal = document.getElementById('attachmentLightbox');
    modal.classList.add('hidden');
    document.getElementById('attachmentLightboxImage').src = '';
}

function closeAttachmentMenus() {
    document.querySelectorAll('[data-attachment-menu] > div:last-child').forEach(function(menu) {
        menu.classList.add('hidden');
    });
    document.querySelectorAll('[data-attachment-menu] > button').forEach(function(button) {
        button.setAttribute('aria-expanded', 'false');
    });
}

function toggleAttachmentMenu(event, button) {
    event.stopPropagation();
    const menu = button.nextElementSibling;
    const wasHidden = menu.classList.contains('hidden');
    closeAttachmentMenus();
    if (wasHidden) {
        menu.classList.remove('hidden');
        button.setAttribute('aria-expanded', 'true');
    }
}

document.addEventListener('click', function() {
    closeAttachmentMenus();
});

function openAddAttachmentModal() {
    const modal = document.getElementById('addAttachmentModal');
    if (!modal) {
        return;
    }
    modal.classList.remove('hidden');
}

function closeAddAttachmentModal() {
    const modal = document.getElementById('addAttachmentModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

document.getElementById('addAttachmentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddAttachmentModal();
    }
});

function openEditAttachmentModal(id, name, description) {
    closeAttachmentMenus();
    const modal = document.getElementById('editAttachmentModal');
    const form = document.getElementById('editAttachmentForm');
    const filename = document.getElementById('editAttachmentFilename');
    const field = document.getElementById('edit_attachment_description');
    if (!modal || !form) {
        return;
    }
    form.action = <?= json_encode(BASE_URL . '/work-orders/attachments/') ?> + id + '/update';
    filename.textContent = name;
    field.value = description || '';
    modal.classList.remove('hidden');
    field.focus();
}

function closeEditAttachmentModal() {
    const modal = document.getElementById('editAttachmentModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

document.getElementById('editAttachmentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditAttachmentModal();
    }
});

function openRemoveAttachmentModal(id, name) {
    closeAttachmentMenus();

    const modal = document.getElementById('removeAttachmentModal');
    const form = document.getElementById('removeAttachmentForm');
    const message = document.getElementById('removeAttachmentMessage');
    if (!modal || !form) {
        return;
    }
    form.action = <?= json_encode(BASE_URL . '/work-orders/attachments/') ?> + id + '/delete';
    message.textContent = <?= json_encode(t('wo.delete_attachment_confirm')) ?>.replace('{name}', name);
    modal.classList.remove('hidden');
}

function closeRemoveAttachmentModal() {
    const modal = document.getElementById('removeAttachmentModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

document.getElementById('removeAttachmentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRemoveAttachmentModal();
    }
});

// Delete modal functions
function openDeleteModal() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close delete modal when clicking outside
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>