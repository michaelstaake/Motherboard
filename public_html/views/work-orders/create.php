<?php 
$title = t('wo.create') . ' - ' . ($companyName ?? APP_NAME);
ob_start(); 
?>

<div>
    <div class="py-6">
        <h1 class="text-3xl font-bold text-gray-900"><?= t('wo.create') ?></h1>
        
        <!-- Progress Indicator -->
        <div class="mt-6">
            <div class="flex items-center">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="flex items-center <?= $i < 5 ? 'flex-1' : '' ?>">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full 
                            <?= $step >= $i ? 'bg-primary-600 text-white' : 'bg-gray-300 text-gray-600' ?>">
                            <?= $i ?>
                        </div>
                        <div class="ml-2 text-sm font-medium 
                            <?= $step >= $i ? 'text-primary-600' : 'text-gray-500' ?>">
                            <?= ['', t('wo.step_customer'), t('wo.step_computer'), t('wo.step_description'), t('wo.step_attachments'), t('wo.step_confirm')][$i] ?>
                        </div>
                        <?php if ($i < 5): ?>
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

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4">
            <?php if ($step === 1): ?>
                <!-- Step 1: Customer -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4"><?= t('wo.step1') ?></h2>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <!-- Customer Search -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2"><?= t('wo.search_customer') ?></label>
                        <div class="relative">
                            <input type="text" 
                                   id="customer_search" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white"
                                   placeholder="<?= htmlspecialchars(t('wo.search_customer_ph')) ?>">
                            <div id="customer_results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 hidden max-h-60 overflow-auto"></div>
                        </div>
                        <input type="hidden" name="customer_id" id="selected_customer_id">
                    </div>

                    <!-- Selected Customer Display -->
                    <div id="selected_customer_display" class="mb-6 hidden">
                        <div class="bg-green-50 border border-green-200 rounded-md p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-green-900"><?= t('wo.selected_customer') ?></h3>
                                    <div id="selected_customer_info" class="mt-2 text-sm text-green-700">
                                        <!-- Customer info will be populated here -->
                                    </div>
                                </div>
                                <button type="button" id="clear_customer_selection" class="ml-4 px-3 py-1 text-sm bg-white border border-green-300 rounded-md text-green-700 hover:bg-green-50">
                                    <?= t('wo.change_customer') ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="customer_selection_section">
                        <div class="text-center text-gray-500 mb-6"><?= t('common.or') ?></div>

                        <!-- New Customer Form -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900"><?= t('wo.add_customer') ?></h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="customer_name" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.name') ?> *
                                </label>
                                <input type="text" 
                                       name="customer_name" 
                                       id="customer_name"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                            
                            <div>
                                <label for="customer_phone" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.phone') ?> *
                                </label>
                                <input type="tel" 
                                       name="customer_phone" 
                                       id="customer_phone"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                            
                            <div>
                                <label for="customer_company" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.company') ?>
                                </label>
                                <input type="text" 
                                       name="customer_company" 
                                       id="customer_company"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                            
                            <div>
                                <label for="customer_email" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.email') ?>
                                </label>
                                <input type="email" 
                                       name="customer_email" 
                                       id="customer_email"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700">
                            <?= t('wo.next_computer') ?>
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 2): ?>
                <!-- Step 2: Device -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4"><?= t('wo.step2') ?></h2>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="computer" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.computer') ?> *
                                </label>
                                <input type="text" 
                                       name="computer" 
                                       id="computer"
                                       required
                                       value="<?= htmlspecialchars($workOrderData['computer'] ?? '') ?>"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white"
                                       placeholder="<?= htmlspecialchars(t('wo.computer_ph')) ?>">
                            </div>
                            
                            <div>
                                <label for="model" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.model') ?>
                                </label>
                                <input type="text" 
                                       name="model" 
                                       id="model"
                                       value="<?= htmlspecialchars($workOrderData['model'] ?? '') ?>"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="serial_number" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.serial') ?>
                                </label>
                                <input type="text" 
                                       name="serial_number" 
                                       id="serial_number"
                                       value="<?= htmlspecialchars($workOrderData['serial_number'] ?? '') ?>"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                            
                            <div>
                                <label for="imei" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.imei') ?>
                                </label>
                                <input type="text" 
                                       name="imei" 
                                       id="imei"
                                       value="<?= htmlspecialchars($workOrderData['imei'] ?? '') ?>"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                        </div>

                        <div>
                            <label for="remarks" class="block text-sm font-medium text-gray-700">
                                <?= t('wo.remarks') ?>
                            </label>
                            <textarea name="remarks" 
                                      id="remarks" 
                                      rows="3"
                                      class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white"
                                      placeholder="<?= htmlspecialchars(t('wo.remarks_ph')) ?>"><?= htmlspecialchars($workOrderData['remarks'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?= t('wo.accessories') ?>
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <?php 
                                $accessories = ['Power Adapter', 'External Storage', 'Keyboard', 'Mouse', 'Monitor', 'Printer'];
                                $selectedAccessories = json_decode($workOrderData['accessories'] ?? '[]', true);
                                foreach ($accessories as $accessory): 
                                ?>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="accessories[]" 
                                               value="<?= $accessory ?>"
                                               <?= in_array($accessory, $selectedAccessories) ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="ml-2 text-sm text-gray-700"><?= tlabel('accessory', $accessory) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2">
                                <input type="text" 
                                       name="accessories[]" 
                                       placeholder="<?= htmlspecialchars(t('wo.other')) ?>"
                                       class="block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                        </div>

                        <?php if ($_SESSION['user_group'] !== 'Limited'): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.username') ?>
                                </label>
                                <input type="text" 
                                       name="username" 
                                       id="username"
                                       value="<?= htmlspecialchars($workOrderData['username'] ?? '') ?>"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">
                                    <?= t('wo.password') ?>
                                </label>
                                <input type="text" 
                                       name="password" 
                                       id="password"
                                       value="<?= htmlspecialchars($workOrderData['password'] ?? '') ?>"
                                       class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="<?= BASE_URL ?>/work-orders/create?step=1" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                            <?= t('common.previous') ?>
                        </a>
                        <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700">
                            <?= t('wo.next_desc') ?>
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 3): ?>
                <!-- Step 3: Description -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4"><?= t('wo.step3') ?></h2>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            <?= t('wo.describe') ?> *
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="6" 
                                  required
                                  class="block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white"
                                  placeholder="<?= htmlspecialchars(t('wo.describe_ph')) ?>"><?= htmlspecialchars($workOrderData['description'] ?? '') ?></textarea>
                    </div>

                    <div class="flex justify-between mt-6">
                        <a href="<?= BASE_URL ?>/work-orders/create?step=2" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                            <?= t('common.previous') ?>
                        </a>
                        <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700">
                            <?= t('wo.next_attachments') ?>
                        </button>
                    </div>
                </form>

            <?php elseif ($step === 4): ?>
                <!-- Step 4: Attachments -->
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900"><?= t('wo.step4') ?></h2>
                    <button type="button" onclick="openAddAttachmentModal()" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <?= t('wo.add_attachment') ?>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-4"><?= t('wo.attachments_help') ?></p>

                <?php if (!empty($pendingAttachments)): ?>
                    <div class="space-y-4">
                        <?php foreach ($pendingAttachments as $pending): ?>
                            <?php $pendingUrl = BASE_URL . '/work-orders/create/attachments/' . rawurlencode($pending['token'] ?? ''); ?>
                            <div class="border border-gray-200 rounded-md p-4">
                                <div class="flex items-start gap-4">
                                    <?php if ($attachmentModel->isDisplayableImage($pending)): ?>
                                        <a href="<?= htmlspecialchars($pendingUrl) ?>" target="_blank" class="flex-shrink-0">
                                            <img src="<?= htmlspecialchars($pendingUrl) ?>" alt="<?= htmlspecialchars($pending['original_filename'] ?? '') ?>" class="h-16 w-16 object-cover rounded border border-gray-200">
                                        </a>
                                    <?php else: ?>
                                        <div class="flex-shrink-0 h-16 w-16 rounded border border-gray-200 bg-gray-50 flex items-center justify-center text-gray-400">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($pending['original_filename'] ?? t('wo.attachments')) ?></p>
                                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($attachmentModel->formatSize($pending['file_size'] ?? 0)) ?></p>
                                        <?php if (!empty($pending['description'])): ?>
                                            <p class="text-sm text-gray-700 mt-2 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($pending['description'])) ?></p>
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
                                                <a href="<?= htmlspecialchars($pendingUrl) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <?= t('wo.download_attachment') ?>
                                                </a>
                                                <button type="button" onclick="openEditAttachmentModal(<?= htmlspecialchars(json_encode($pending['token'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($pending['original_filename'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($pending['description'] ?? ''), ENT_QUOTES) ?>)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <?= t('wo.edit_description') ?>
                                                </button>
                                                <button type="button" onclick="openRemoveAttachmentModal(<?= htmlspecialchars(json_encode($pending['token'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($pending['original_filename'] ?? ''), ENT_QUOTES) ?>)" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                                    <?= t('wo.delete_attachment') ?>
                                                </button>
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

                <div class="flex justify-between mt-6">
                    <a href="<?= BASE_URL ?>/work-orders/create?step=3" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                        <?= t('common.previous') ?>
                    </a>
                    <form method="POST" action="<?= BASE_URL ?>/work-orders/create?step=4">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="attachment_action" value="next">
                        <button type="submit" class="bg-primary-600 text-white px-6 py-2 rounded-md hover:bg-primary-700">
                            <?= t('wo.next_confirm') ?>
                        </button>
                    </form>
                </div>

                <!-- Add Attachment Modal -->
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
                            <form method="POST" action="<?= BASE_URL ?>/work-orders/create?step=4" enctype="multipart/form-data" data-attachment-upload>
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) $attachmentMaxBytes ?>">
                                <input type="hidden" name="attachment_action" value="add">
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

                <!-- Edit Attachment Description Modal -->
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
                            <form method="POST" action="<?= BASE_URL ?>/work-orders/create?step=4">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="attachment_action" value="update">
                                <input type="hidden" name="attachment_token" id="editAttachmentToken">
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

                <!-- Delete Attachment Modal -->
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
                                <form id="removeAttachmentForm" method="POST" action="<?= BASE_URL ?>/work-orders/create?step=4" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <input type="hidden" name="attachment_action" value="remove">
                                    <input type="hidden" name="attachment_token" id="removeAttachmentToken">
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

            <?php elseif ($step === 5): ?>
                <!-- Step 5: Confirm -->
                <h2 class="text-xl font-semibold text-gray-900 mb-4"><?= t('wo.step5') ?></h2>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <!-- Summary -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4"><?= t('wo.summary') ?></h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-700"><?= t('wo.customer') ?></h4>
                                <p class="text-gray-900"><?= htmlspecialchars($customer['name']) ?></p>
                                <?php if ($customer['company']): ?>
                                    <p class="text-gray-600"><?= htmlspecialchars($customer['company']) ?></p>
                                <?php endif; ?>
                                <p class="text-gray-600"><?= htmlspecialchars($customer['phone']) ?></p>
                                <?php if ($customer['email']): ?>
                                    <p class="text-gray-600"><?= htmlspecialchars($customer['email']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <h4 class="font-medium text-gray-700"><?= t('wo.computer') ?></h4>
                                <p class="text-gray-900"><?= htmlspecialchars($workOrderData['computer']) ?></p>
                                <?php if ($workOrderData['model']): ?>
                                    <p class="text-gray-600"><?= t('wo.model_short', ['model' => htmlspecialchars($workOrderData['model'])]) ?></p>
                                <?php endif; ?>
                                <?php if ($workOrderData['serial_number']): ?>
                                    <p class="text-gray-600"><?= t('wo.sn_short', ['sn' => htmlspecialchars($workOrderData['serial_number'])]) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($workOrderData['imei'])): ?>
                                    <p class="text-gray-600"><?= t('wo.imei_short', ['imei' => htmlspecialchars($workOrderData['imei'])]) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($workOrderData['remarks'])): ?>
                                    <p class="text-gray-600 whitespace-pre-wrap"><?= t('wo.remarks') ?>: <?= nl2br(htmlspecialchars($workOrderData['remarks'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h4 class="font-medium text-gray-700"><?= t('wo.problem') ?></h4>
                            <p class="text-gray-900 mt-1"><?= nl2br(htmlspecialchars($workOrderData['description'])) ?></p>
                        </div>

                        <div class="mt-4">
                            <h4 class="font-medium text-gray-700"><?= t('wo.attachments') ?></h4>
                            <?php if (!empty($pendingAttachments)): ?>
                                <ul class="mt-1 space-y-1">
                                    <?php foreach ($pendingAttachments as $pending): ?>
                                        <li class="text-gray-900">
                                            <?= htmlspecialchars($pending['original_filename'] ?? '') ?>
                                            <?php if (!empty($pending['description'])): ?>
                                                <span class="text-gray-600"> — <?= htmlspecialchars($pending['description']) ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-gray-600 mt-1"><?= t('wo.none_attached') ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Assignment and Priority -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="assigned_to" class="block text-sm font-medium text-gray-700">
                                <?= t('wo.assign') ?>
                            </label>
                            <select name="assigned_to" 
                                    id="assigned_to"
                                    class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                                <option value=""><?= t('common.unassigned') ?></option>
                                <?php foreach ($technicians as $technician): ?>
                                    <option value="<?= $technician['id'] ?>">
                                        <?= !empty($technician['name']) ? htmlspecialchars($technician['name']) : htmlspecialchars($technician['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="priority" class="block text-sm font-medium text-gray-700">
                                <?= t('common.priority') ?>
                            </label>
                            <select name="priority" 
                                    id="priority"
                                    class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 bg-white">
                                <option value="Standard"><?= t('priority.Standard') ?></option>
                                <option value="Priority"><?= t('priority.Priority') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <a href="<?= BASE_URL ?>/work-orders/create?step=4" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                            <?= t('common.previous') ?>
                        </a>
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                            <?= t('wo.create') ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Customer search functionality
document.getElementById('customer_search')?.addEventListener('input', function() {
    const query = this.value.trim();
    const resultsDiv = document.getElementById('customer_results');
    
    if (query.length === 0) {
        resultsDiv.classList.add('hidden');
        return;
    }
    
    // Check if this looks like a phone number search (more than 3 digits)
    const digitCount = (query.match(/\d/g) || []).length;
    let searchQueries = [query];
    
    if (digitCount > 3) {
        // Generate multiple phone number format variations
        const digitsOnly = query.replace(/\D/g, '');
        
        if (digitsOnly.length >= 10) {
            // Generate common phone number formats
            const phoneFormats = [];
            
            // Format: 5555555555
            phoneFormats.push(digitsOnly);
            
            // Format: 555-555-5555
            if (digitsOnly.length === 10) {
                phoneFormats.push(`${digitsOnly.slice(0,3)}-${digitsOnly.slice(3,6)}-${digitsOnly.slice(6,10)}`);
            } else if (digitsOnly.length === 11 && digitsOnly.startsWith('1')) {
                phoneFormats.push(`${digitsOnly.slice(1,4)}-${digitsOnly.slice(4,7)}-${digitsOnly.slice(7,11)}`);
                phoneFormats.push(`1-${digitsOnly.slice(1,4)}-${digitsOnly.slice(4,7)}-${digitsOnly.slice(7,11)}`);
            }
            
            // Format: (555) 555-5555
            if (digitsOnly.length === 10) {
                phoneFormats.push(`(${digitsOnly.slice(0,3)}) ${digitsOnly.slice(3,6)}-${digitsOnly.slice(6,10)}`);
            } else if (digitsOnly.length === 11 && digitsOnly.startsWith('1')) {
                phoneFormats.push(`1 (${digitsOnly.slice(1,4)}) ${digitsOnly.slice(4,7)}-${digitsOnly.slice(7,11)}`);
                phoneFormats.push(`(${digitsOnly.slice(1,4)}) ${digitsOnly.slice(4,7)}-${digitsOnly.slice(7,11)}`);
            }
            
            // Format: 555.555.5555
            if (digitsOnly.length === 10) {
                phoneFormats.push(`${digitsOnly.slice(0,3)}.${digitsOnly.slice(3,6)}.${digitsOnly.slice(6,10)}`);
            }
            
            searchQueries = phoneFormats;
        } else if (digitsOnly.length >= 4) {
            // For partial phone numbers, search for the digits
            searchQueries = [digitsOnly, query];
        }
    }
    
    // Perform searches for all query variations
    const searchPromises = searchQueries.map(searchQuery => 
        fetch(`<?= BASE_URL ?>/api/search-customers?q=${encodeURIComponent(searchQuery)}`)
            .then(response => response.json())
    );
    
    Promise.all(searchPromises)
        .then(resultsArrays => {
            // Combine and deduplicate results
            const combinedResults = [];
            const seenIds = new Set();
            
            resultsArrays.forEach(customers => {
                customers.forEach(customer => {
                    if (!seenIds.has(customer.id)) {
                        seenIds.add(customer.id);
                        combinedResults.push(customer);
                    }
                });
            });
            
            // Sort results by name
            combinedResults.sort((a, b) => a.name.localeCompare(b.name));
            
            if (combinedResults.length === 0) {
                const emptyResult = document.createElement('div');
                emptyResult.className = 'p-2 text-gray-500';
                emptyResult.textContent = <?= json_encode(t('js.no_customers')) ?>;
                resultsDiv.replaceChildren(emptyResult);
            } else {
                resultsDiv.replaceChildren(...combinedResults.map(buildCustomerOption));
            }
            resultsDiv.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error searching customers:', error);
        });
});

function customerTextLine(text, className = '') {
    const line = document.createElement('div');
    line.className = className;
    line.textContent = text || '';
    return line;
}

function buildCustomerOption(customer) {
    const option = document.createElement('div');
    option.className = 'p-2 hover:bg-gray-100 cursor-pointer customer-option';
    option.dataset.customerId = String(customer.id);
    option.appendChild(customerTextLine(customer.name, 'font-medium'));
    if (customer.company) {
        option.appendChild(customerTextLine(customer.company, 'text-sm text-gray-600'));
    }
    option.appendChild(customerTextLine(customer.phone, 'text-sm text-gray-600'));
    option.addEventListener('click', () => selectCustomer(customer));
    return option;
}

// Function to select a customer
function selectCustomer(customer) {
    // Set the hidden field
    document.getElementById('selected_customer_id').value = customer.id;
    
    // Update the selected customer display
    const customerInfo = document.getElementById('selected_customer_info');
    const customerLines = [customerTextLine(customer.name, 'font-medium')];
    if (customer.company) customerLines.push(customerTextLine(customer.company));
    customerLines.push(customerTextLine(customer.phone));
    if (customer.email) customerLines.push(customerTextLine(customer.email));
    customerInfo.replaceChildren(...customerLines);
    
    // Show selected customer section and hide search/new customer sections
    document.getElementById('selected_customer_display').classList.remove('hidden');
    document.getElementById('customer_selection_section').classList.add('hidden');
    document.getElementById('customer_results').classList.add('hidden');
    
    // Clear search input
    document.getElementById('customer_search').value = '';
    
    // Clear new customer form and disable validation for hidden fields
    clearNewCustomerForm();
    disableNewCustomerValidation();
}

// Function to clear customer selection
function clearCustomerSelection() {
    // Clear hidden field
    document.getElementById('selected_customer_id').value = '';
    
    // Hide selected customer section and show search/new customer sections
    document.getElementById('selected_customer_display').classList.add('hidden');
    document.getElementById('customer_selection_section').classList.remove('hidden');
    
    // Clear search input
    document.getElementById('customer_search').value = '';
    
    // Clear new customer form and re-enable validation
    clearNewCustomerForm();
    enableNewCustomerValidation();
}

// Function to clear new customer form
function clearNewCustomerForm() {
    document.getElementById('customer_name').value = '';
    document.getElementById('customer_phone').value = '';
    document.getElementById('customer_company').value = '';
    document.getElementById('customer_email').value = '';
}

// Function to disable validation for new customer fields when existing customer is selected
function disableNewCustomerValidation() {
    const phoneField = document.getElementById('customer_phone');
    const nameField = document.getElementById('customer_name');
    
    if (phoneField) {
        phoneField.setAttribute('data-no-auto-format', 'true');
        phoneField.removeAttribute('required');
    }
    if (nameField) {
        nameField.removeAttribute('required');
    }
}

// Function to re-enable validation for new customer fields
function enableNewCustomerValidation() {
    const phoneField = document.getElementById('customer_phone');
    const nameField = document.getElementById('customer_name');
    
    if (phoneField) {
        phoneField.removeAttribute('data-no-auto-format');
    }
    // Note: We don't re-add 'required' attributes since the original form doesn't have them
}

// Add event listener for clear customer selection button
document.getElementById('clear_customer_selection')?.addEventListener('click', function() {
    clearCustomerSelection();
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    const searchInput = document.getElementById('customer_search');
    const resultsDiv = document.getElementById('customer_results');
    
    if (searchInput && resultsDiv && !searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
        resultsDiv.classList.add('hidden');
    }
});

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

function openAddAttachmentModal() {
    closeAttachmentMenus();
    document.getElementById('addAttachmentModal')?.classList.remove('hidden');
}

function closeAddAttachmentModal() {
    document.getElementById('addAttachmentModal')?.classList.add('hidden');
}

function openEditAttachmentModal(token, name, description) {
    closeAttachmentMenus();
    const modal = document.getElementById('editAttachmentModal');
    const tokenField = document.getElementById('editAttachmentToken');
    const filename = document.getElementById('editAttachmentFilename');
    const field = document.getElementById('edit_attachment_description');
    if (!modal || !tokenField || !field) {
        return;
    }
    tokenField.value = token;
    filename.textContent = name;
    field.value = description || '';
    modal.classList.remove('hidden');
    field.focus();
}

function closeEditAttachmentModal() {
    document.getElementById('editAttachmentModal')?.classList.add('hidden');
}

function openRemoveAttachmentModal(token, name) {
    closeAttachmentMenus();
    const modal = document.getElementById('removeAttachmentModal');
    const tokenField = document.getElementById('removeAttachmentToken');
    const message = document.getElementById('removeAttachmentMessage');
    if (!modal || !tokenField) {
        return;
    }
    tokenField.value = token;
    message.textContent = <?= json_encode(t('wo.delete_attachment_confirm')) ?>.replace('{name}', name);
    modal.classList.remove('hidden');
}

function closeRemoveAttachmentModal() {
    document.getElementById('removeAttachmentModal')?.classList.add('hidden');
}

document.addEventListener('click', function() {
    closeAttachmentMenus();
});

document.getElementById('addAttachmentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddAttachmentModal();
    }
});
document.getElementById('editAttachmentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditAttachmentModal();
    }
});
document.getElementById('removeAttachmentModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRemoveAttachmentModal();
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddAttachmentModal();
        closeEditAttachmentModal();
        closeRemoveAttachmentModal();
        closeAttachmentMenus();
    }
});
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
