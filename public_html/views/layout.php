<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale ?? I18n::getInstance()->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? ($companyName ?? APP_NAME), ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        /**
         * Phone Number Validation and Formatting
         * Handles different phone number formats based on system settings
         */
        class PhoneValidator {
            constructor() {
                this.format = 'default'; // Will be set by the application
                this.patterns = {
                    default: /^\d{7,}$/, // At least 7 digits
                    usa_format_a: /^\(\d{3}\) \d{3}-\d{4}$/, // (555) 555-5555
                    usa_format_b: /^\d{3}-\d{3}-\d{4}$/ // 555-555-5555
                };
                this.formatters = {
                    usa_format_a: this.formatUSAA.bind(this),
                    usa_format_b: this.formatUSAB.bind(this)
                };
            }

            setFormat(format) {
                this.format = format;
            }

            /**
             * Format phone number for USA Format A: (555) 555-5555
             */
            formatUSAA(value) {
                // Remove all non-digit characters
                const digits = value.replace(/\D/g, '');
                
                if (digits.length === 0) return '';
                if (digits.length <= 3) return `(${digits}`;
                if (digits.length <= 6) return `(${digits.slice(0, 3)}) ${digits.slice(3)}`;
                return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
            }

            /**
             * Format phone number for USA Format B: 555-555-5555
             */
            formatUSAB(value) {
                // Remove all non-digit characters
                const digits = value.replace(/\D/g, '');
                
                if (digits.length === 0) return '';
                if (digits.length <= 3) return digits;
                if (digits.length <= 6) return `${digits.slice(0, 3)}-${digits.slice(3)}`;
                return `${digits.slice(0, 3)}-${digits.slice(3, 6)}-${digits.slice(6, 10)}`;
            }

            /**
             * Format input as user types
             */
            formatInput(value) {
                if (this.format === 'default') {
                    return value; // No formatting for default
                }
                
                if (this.formatters[this.format]) {
                    return this.formatters[this.format](value);
                }
                
                return value;
            }

            /**
             * Format existing phone number to current format
             * This is used to reformat existing data when the page loads
             */
            formatExistingNumber(value) {
                if (!value || this.format === 'default') {
                    return value; // No formatting for default or empty values
                }
                
                // Extract digits only
                const digits = value.replace(/\D/g, '');
                
                // Only format if we have exactly 10 digits (US phone number)
                if (digits.length === 10) {
                    if (this.formatters[this.format]) {
                        return this.formatters[this.format](digits);
                    }
                }
                
                // Return original value if we can't format it properly
                return value;
            }

            /**
             * Validate phone number against current format
             */
            validate(value) {
                if (!value) return { valid: false, message: <?= json_encode(t('js.phone_required')) ?> };
                
                const pattern = this.patterns[this.format];
                if (!pattern) return { valid: false, message: <?= json_encode(t('js.phone_required')) ?> };

                if (this.format === 'default') {
                    // For default format, just check if there are at least 7 digits
                    const digits = value.replace(/\D/g, '');
                    if (digits.length < 7) {
                        return { valid: false, message: <?= json_encode(t('js.phone_digits')) ?> };
                    }
                    return { valid: true };
                }

                if (!pattern.test(value)) {
                    const formatMessages = {
                        usa_format_a: <?= json_encode(t('js.phone_a')) ?>,
                        usa_format_b: <?= json_encode(t('js.phone_b')) ?>
                    };
                    return { 
                        valid: false, 
                        message: formatMessages[this.format] || <?= json_encode(t('js.phone_required')) ?> 
                    };
                }

                return { valid: true };
            }

            /**
             * Validate phone number with optional requirement check
             */
            validateOptional(value, isRequired = true) {
                // If not required and empty, it's valid
                if (!isRequired && (!value || value.trim() === '')) {
                    return { valid: true };
                }
                
                // Use regular validation if required or has value
                return this.validate(value);
            }

            /**
             * Get placeholder text for input field
             */
            getPlaceholder() {
                const placeholders = {
                    default: <?= json_encode(t('js.phone_ph_default')) ?>,
                    usa_format_a: '(555) 555-5555',
                    usa_format_b: '555-555-5555'
                };
                return placeholders[this.format] || '';
            }

            /**
             * Add real-time validation to a phone input field
             */
            attachToInput(inputElement, errorElement = null) {
                if (!inputElement) return;

                // Check if this field should be treated as optional
                const isOptional = inputElement.hasAttribute('data-no-auto-format');

                // Set placeholder
                inputElement.placeholder = this.getPlaceholder();

                // Format as user types (for USA formats, but not for optional fields)
                if (!isOptional) {
                    inputElement.addEventListener('input', (e) => {
                        const cursorPosition = e.target.selectionStart;
                        const oldValue = e.target.value;
                        const newValue = this.formatInput(oldValue);
                        
                        if (newValue !== oldValue) {
                            e.target.value = newValue;
                            // Adjust cursor position to handle formatting
                            const offset = newValue.length - oldValue.length;
                            e.target.setSelectionRange(cursorPosition + offset, cursorPosition + offset);
                        }
                    });
                }

                // Validate on blur
                inputElement.addEventListener('blur', (e) => {
                    const result = isOptional ? 
                        this.validateOptional(e.target.value, false) : 
                        this.validate(e.target.value);
                    this.showValidationResult(inputElement, errorElement, result);
                });

                // Clear validation on focus
                inputElement.addEventListener('focus', (e) => {
                    this.clearValidationResult(inputElement, errorElement);
                });
            }

            /**
             * Show validation result
             */
            showValidationResult(inputElement, errorElement, result) {
                if (result.valid) {
                    inputElement.classList.remove('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                    inputElement.classList.add('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                    if (errorElement) {
                        errorElement.textContent = '';
                        errorElement.style.display = 'none';
                    }
                } else {
                    inputElement.classList.remove('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                    inputElement.classList.add('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                    if (errorElement) {
                        errorElement.textContent = result.message;
                        errorElement.style.display = 'block';
                    }
                }
            }

            /**
             * Clear validation styling
             */
            clearValidationResult(inputElement, errorElement) {
                inputElement.classList.remove(
                    'border-red-300', 'focus:ring-red-500', 'focus:border-red-500',
                    'border-green-300', 'focus:ring-green-500', 'focus:border-green-500'
                );
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        }

        // Global phone validator instance
        window.phoneValidator = new PhoneValidator();

        /**
         * Initialize phone validation for all phone inputs on the page
         */
        function initializePhoneValidation(format = 'default') {
            window.phoneValidator.setFormat(format);
            
            // Find all phone inputs and attach validation
            const phoneInputs = document.querySelectorAll('input[type="tel"], input[name*="phone"]');
            phoneInputs.forEach(input => {
                // Skip auto-formatting for excluded fields (like settings)
                const shouldAutoFormat = !input.hasAttribute('data-no-auto-format');
                
                // Format existing value to current format (only if not excluded)
                if (shouldAutoFormat) {
                    const currentValue = input.value;
                    if (currentValue) {
                        const formattedValue = window.phoneValidator.formatExistingNumber(currentValue);
                        if (formattedValue !== currentValue) {
                            input.value = formattedValue;
                        }
                    }
                }
                
                // Create or find error element
                let errorElement = input.parentElement.querySelector('.phone-error');
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'phone-error text-sm text-red-600 mt-1';
                    errorElement.style.display = 'none';
                    input.parentElement.appendChild(errorElement);
                }
                
                window.phoneValidator.attachToInput(input, errorElement);
            });
        }

        /**
         * Validate all phone fields on form submission
         */
        function validateAllPhoneFields() {
            const phoneInputs = document.querySelectorAll('input[type="tel"], input[name*="phone"]');
            let allValid = true;
            
            phoneInputs.forEach(input => {
                const isOptional = input.hasAttribute('data-no-auto-format');
                const result = isOptional ? 
                    window.phoneValidator.validateOptional(input.value, false) : 
                    window.phoneValidator.validate(input.value);
                const errorElement = input.parentElement.querySelector('.phone-error');
                window.phoneValidator.showValidationResult(input, errorElement, result);
                
                if (!result.valid) {
                    allValid = false;
                }
            });
            
            return allValid;
        }
    </script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php
        $showAppChrome = isset($_SESSION['user_id']) && !isset($hideNavigation);
        $appContainerClass = 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';
    ?>
    <?php if ($showAppChrome): ?>
        <nav class="bg-white shadow-lg">
            <div class="<?= $appContainerClass ?>">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <a href="<?= BASE_URL ?>/" class="flex items-center">
                            <?php if (!empty($companyLogoUrl)): ?>
                                <img src="<?= htmlspecialchars($companyLogoUrl) ?>" alt="<?= htmlspecialchars($companyName ?? APP_NAME) ?>" class="h-10 w-auto">
                            <?php else: ?>
                                <span class="text-xl font-bold text-gray-900"><?= htmlspecialchars($companyName ?? APP_NAME) ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <button id="mobile-menu-button" type="button" class="text-gray-600 hover:text-gray-900 focus:outline-none focus:text-gray-900" aria-expanded="false" aria-controls="mobile-menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                <div
                    id="mobile-menu"
                    class="hidden flex w-full flex-col items-center gap-1 pb-4 md:flex-row md:flex-wrap md:items-center md:justify-center md:gap-x-6 md:gap-y-2 md:pb-0"
                >
                    <?php Hooks::doAction('layout.nav.before'); ?>
                    <a href="<?= BASE_URL ?>/" class="block py-2 text-gray-600 hover:text-gray-900 md:inline-flex md:py-2 md:px-4"><?= t('nav.home') ?></a>
                    <a href="<?= BASE_URL ?>/work-orders" class="block py-2 text-gray-600 hover:text-gray-900 md:inline-flex md:py-2 md:px-4"><?= t('nav.work_orders') ?></a>
                    <?php if ($_SESSION['user_group'] === 'Admin'): ?>
                        <a href="<?= BASE_URL ?>/customers" class="block py-2 text-gray-600 hover:text-gray-900 md:inline-flex md:py-2 md:px-4"><?= t('nav.customers') ?></a>
                        <?php Hooks::doAction('layout.nav.after_customers'); ?>
                        <a href="<?= BASE_URL ?>/settings" class="block py-2 text-gray-600 hover:text-gray-900 md:inline-flex md:py-2 md:px-4"><?= t('nav.settings') ?></a>
                    <?php endif; ?>
                    <?php Hooks::doAction('layout.nav'); ?>
                    <a href="<?= BASE_URL ?>/logout" class="block py-2 text-gray-600 hover:text-gray-900 md:inline-flex md:py-2 md:px-4"><?= t('nav.logout') ?></a>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <main class="<?= $showAppChrome ? 'pt-6' : '' ?>">
        <?php if ($showAppChrome): ?>
            <div class="<?= $appContainerClass ?>">
                <?= $content ?? '' ?>
            </div>
        <?php else: ?>
            <?= $content ?? '' ?>
        <?php endif; ?>
    </main>

    <?php if ($showAppChrome): ?>
        <footer class="bg-white border-t mt-12">
            <div class="<?= $appContainerClass ?> py-6">
                <div class="flex justify-between items-center">
                    <div class="text-gray-600">
                        <span><?= htmlspecialchars(!empty($_SESSION['user_name']) ? $_SESSION['user_name'] : $_SESSION['username']) ?></span>
                    </div>
                    <div class="text-gray-600 flex items-center">
                        <?php Hooks::doAction('layout.footer'); ?>
                        <p><?= t('layout.powered_by') ?> <a href="https://motherboard.cx" target="_blank" class="text-primary-600 hover:text-primary-700"><?= t('app.name') ?></a></p>
                    </div>
                </div>
            </div>
        </footer>
        <?php
            $navShortcuts = [
                ['key' => 'H', 'label' => t('nav.home'), 'url' => BASE_URL . '/'],
                ['key' => 'W', 'label' => t('nav.work_orders'), 'url' => BASE_URL . '/work-orders'],
                ['key' => 'O', 'label' => t('nav.open_work_orders'), 'url' => BASE_URL . '/work-orders?status=Open'],
                ['key' => 'A', 'label' => 'Work Orders Assigned to Me', 'url' => BASE_URL . '/work-orders?assigned_to=' . (int) $_SESSION['user_id']],
            ];
            if ($_SESSION['user_group'] !== 'Limited') {
                $navShortcuts[] = ['key' => 'N', 'label' => t('nav.new_work_order'), 'url' => BASE_URL . '/work-orders/create'];
            }
            if ($_SESSION['user_group'] === 'Admin') {
                $navShortcuts[] = ['key' => 'C', 'label' => t('nav.customers'), 'url' => BASE_URL . '/customers'];
                if (ModuleLoader::instance()?->isEnabled('inventory')) {
                    $navShortcuts[] = ['key' => 'I', 'label' => t('nav.inventory'), 'url' => BASE_URL . '/inventory'];
                }
                $navShortcuts[] = ['key' => 'S', 'label' => t('nav.settings'), 'url' => BASE_URL . '/settings'];
            }
            $navShortcuts[] = ['key' => 'L', 'label' => t('nav.logout'), 'url' => BASE_URL . '/logout'];
        ?>
        <div id="command-palette" class="hidden fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="command-palette-title">
            <div id="command-palette-backdrop" class="absolute inset-0 bg-gray-900/50"></div>
            <div class="relative flex min-h-full items-start justify-center px-4 pt-[15vh]">
                <div class="w-full max-w-md rounded-lg bg-white shadow-xl ring-1 ring-black/5">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h2 id="command-palette-title" class="text-sm font-semibold text-gray-900"><?= t('command_palette.title') ?></h2>
                        <p class="mt-1 text-xs text-gray-500"><?= t('command_palette.hint') ?></p>
                    </div>
                    <ul class="py-2">
                        <?php foreach ($navShortcuts as $shortcut): ?>
                            <li>
                                <button
                                    type="button"
                                    class="command-palette-item flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                                    data-key="<?= htmlspecialchars($shortcut['key']) ?>"
                                    data-url="<?= htmlspecialchars($shortcut['url']) ?>"
                                >
                                    <kbd class="mr-3 inline-flex min-w-[1.75rem] justify-center rounded border border-gray-300 bg-gray-50 px-1.5 py-0.5 text-xs font-medium text-gray-600"><?= htmlspecialchars($shortcut['key']) ?></kbd>
                                    <span><?= htmlspecialchars($shortcut['label']) ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function isEditableField(element) {
            if (!element) {
                return false;
            }
            const tag = element.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                return true;
            }
            return element.isContentEditable;
        }

        function openCommandPalette() {
            const palette = document.getElementById('command-palette');
            if (!palette) {
                return;
            }
            palette.classList.remove('hidden');
        }

        function closeCommandPalette() {
            const palette = document.getElementById('command-palette');
            if (!palette) {
                return;
            }
            palette.classList.add('hidden');
        }

        document.addEventListener('keydown', function(e) {
            const palette = document.getElementById('command-palette');
            const paletteOpen = palette && !palette.classList.contains('hidden');

            if (e.altKey && e.key === '/') {
                if (isEditableField(document.activeElement)) {
                    return;
                }
                e.preventDefault();
                if (paletteOpen) {
                    closeCommandPalette();
                } else {
                    openCommandPalette();
                }
                return;
            }

            if (!paletteOpen) {
                return;
            }

            if (e.key === 'Escape') {
                e.preventDefault();
                closeCommandPalette();
                return;
            }

            if (e.key.length === 1) {
                const item = palette.querySelector('.command-palette-item[data-key="' + e.key.toUpperCase() + '"]');
                if (item) {
                    e.preventDefault();
                    window.location.href = item.getAttribute('data-url');
                }
            }
        });

        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    const isHidden = mobileMenu.classList.toggle('hidden');
                    mobileMenuButton.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
                });
            }

            const commandPalette = document.getElementById('command-palette');
            const commandPaletteBackdrop = document.getElementById('command-palette-backdrop');
            if (commandPalette) {
                commandPalette.querySelectorAll('.command-palette-item').forEach(function(item) {
                    item.addEventListener('click', function() {
                        window.location.href = item.getAttribute('data-url');
                    });
                });
            }
            if (commandPaletteBackdrop) {
                commandPaletteBackdrop.addEventListener('click', closeCommandPalette);
            }

            // Initialize phone validation
            <?php 
            // Get phone format setting
            if (isset($_SESSION['user_id'])) {
                require_once ROOT_PATH . '/models/Settings.php';
                $settingsModel = new Settings();
                $phoneFormat = $settingsModel->getSetting('phone_number_format', 'default');
                echo "initializePhoneValidation('" . htmlspecialchars($phoneFormat) . "');";
            }
            ?>
        });

        // Add form validation before submission
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form && form.hasAttribute('data-attachment-upload')) {
                const attachmentError = validateAttachmentUpload(form);
                if (attachmentError) {
                    e.preventDefault();
                    showAttachmentUploadError(form, attachmentError);
                    return false;
                }
            }

            const phoneInputs = form.querySelectorAll('input[type="tel"], input[name*="phone"]');
            
            if (phoneInputs.length > 0) {
                if (!validateAllPhoneFields()) {
                    e.preventDefault();
                    showAlert(<?= json_encode(t('js.fix_phone')) ?>, 'error');
                    return false;
                }
            }
        });

        document.addEventListener('change', function(e) {
            const input = e.target;
            if (!input || input.type !== 'file' || !input.form || !input.form.hasAttribute('data-attachment-upload')) {
                return;
            }
            if (!input.files || !input.files[0]) {
                showAttachmentUploadError(input.form, '');
                return;
            }
            const attachmentError = validateAttachmentUpload(input.form);
            showAttachmentUploadError(input.form, attachmentError);
        });

        function validateAttachmentUpload(form) {
            const input = form.querySelector('input[type="file"][name="attachment"]');
            if (!input) {
                return <?= json_encode(t('wo.attachment_required')) ?>;
            }
            const file = input.files && input.files[0];
            if (!file) {
                return <?= json_encode(t('wo.attachment_required')) ?>;
            }
            const maxBytes = parseInt(input.getAttribute('data-max-bytes') || '0', 10);
            if (maxBytes > 0 && file.size > maxBytes) {
                return <?= json_encode(t('wo.attachment_too_large')) ?>.replace('{size}', formatAttachmentSize(maxBytes));
            }
            const allowed = (input.getAttribute('data-allowed') || '').split(',').map(function(item) {
                return item.trim().toLowerCase();
            }).filter(Boolean);
            if (allowed.length && allowed.indexOf('%') === -1) {
                const name = file.name || '';
                const dot = name.lastIndexOf('.');
                const ext = dot === -1 ? '' : name.slice(dot + 1).toLowerCase();
                if (!ext || allowed.indexOf(ext) === -1) {
                    return <?= json_encode(t('wo.attachment_type_denied')) ?>.replace('{types}', allowed.join(', '));
                }
            }
            return '';
        }

        function formatAttachmentSize(bytes) {
            bytes = parseInt(bytes, 10) || 0;
            if (bytes < 1024) {
                return bytes + ' B';
            }
            if (bytes < 1048576) {
                return (Math.round(bytes / 102.4) / 10) + ' KB';
            }
            return (Math.round(bytes / 104857.6) / 10) + ' MB';
        }

        function showAttachmentUploadError(form, message) {
            const errorEl = form.querySelector('[data-attachment-error]');
            if (!errorEl) {
                if (message) {
                    showAlert(message, 'error');
                }
                return;
            }
            if (message) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            } else {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }
        }

        // Global functions
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
                type === 'error' ? 'bg-red-100 text-red-700 border border-red-300' :
                type === 'success' ? 'bg-green-100 text-green-700 border border-green-300' :
                'bg-blue-100 text-blue-700 border border-blue-300'
            }`;
            alertDiv.textContent = message;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // CSRF token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                          document.querySelector('input[name="csrf_token"]')?.value;
    </script>
</body>
</html>
