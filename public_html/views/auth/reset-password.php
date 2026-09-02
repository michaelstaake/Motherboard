<?php 
$title = t('auth.reset_title') . ' - ' . ($companyName ?? APP_NAME);
$hideNavigation = true;
ob_start(); 
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                <?= t('auth.set_password') ?>
            </h2>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?= htmlspecialchars($message) ?>
                <div class="mt-4">
                    <a href="<?= BASE_URL ?>/login" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <?= t('auth.go_login') ?>
                    </a>
                </div>
            </div>
        <?php elseif ($valid_token): ?>
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            <?= t('auth.new_password') ?>
                        </label>
                        <input id="password" 
                               name="password" 
                               type="password" 
                               required 
                               minlength="8"
                               class="mt-1 appearance-none relative block w-full px-4 py-3 border-2 border-gray-300 placeholder-gray-500 text-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
                               placeholder="<?= htmlspecialchars(t('auth.min_chars')) ?>">
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                            <?= t('auth.confirm_password') ?>
                        </label>
                        <input id="confirm_password" 
                               name="confirm_password" 
                               type="password" 
                               required 
                               minlength="8"
                               class="mt-1 appearance-none relative block w-full px-4 py-3 border-2 border-gray-300 placeholder-gray-500 text-gray-900 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
                               placeholder="<?= htmlspecialchars(t('auth.confirm_placeholder')) ?>">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <?= t('auth.reset_button') ?>
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="px-4 py-3 rounded">
                <p><?= t('auth.reset_problem') ?></p>
                <div class="mt-4">
                    <a href="<?= BASE_URL ?>/forgot-password" class="text-primary-600 hover:text-primary-500">
                        <?= t('auth.request_new_link') ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Validate password confirmation
    document.getElementById('confirm_password')?.addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (password !== confirmPassword) {
            this.setCustomValidity(<?= json_encode(t('js.passwords_mismatch')) ?>);
        } else {
            this.setCustomValidity('');
        }
    });
</script>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
