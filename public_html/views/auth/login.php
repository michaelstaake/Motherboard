<?php 
$title = t('auth.sign_in') . ' - ' . ($companyName ?? APP_NAME);
$hideNavigation = true;
ob_start(); 
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                <?= t('auth.sign_in_to', ['company' => htmlspecialchars($companyName ?? APP_NAME)]) ?>
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
            </div>
        <?php endif; ?>

        <?php if ($requires2FA): ?>
            <!-- Two-Factor Authentication Form -->
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4"><?= t('auth.2fa_title') ?></h3>
                    <p class="text-gray-600 mb-4"><?= t('auth.2fa_help') ?></p>
                    <input type="text" 
                           name="two_factor_code" 
                           inputmode="numeric"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           class="appearance-none rounded-md relative block w-full px-4 py-3 border-2 border-gray-300 placeholder-gray-500 text-gray-900 shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm bg-white"
                           placeholder="<?= t('auth.2fa_placeholder') ?>"
                           required
                           autocomplete="one-time-code">
                </div>
                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <?= t('auth.verify') ?>
                    </button>
                </div>
                <div class="text-center">
                    <a href="<?= BASE_URL ?>/login" class="text-primary-600 hover:text-primary-500">
                        <?= t('auth.back_login') ?>
                    </a>
                </div>
            </form>
        <?php else: ?>
            <!-- Regular Login Form -->
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="rounded-md shadow-sm -space-y-0.5">
                    <div>
                        <label for="username" class="sr-only"><?= t('auth.username') ?></label>
                        <input id="username" 
                               name="username" 
                               type="text" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-4 py-3 border-2 border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm bg-white"
                               placeholder="<?= t('auth.username') ?>">
                    </div>
                    <div>
                        <label for="password" class="sr-only"><?= t('auth.password') ?></label>
                        <input id="password" 
                               name="password" 
                               type="password" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-4 py-3 border-2 border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 focus:z-10 sm:text-sm bg-white"
                               placeholder="<?= t('auth.password') ?>">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-sm">
                        <a href="<?= BASE_URL ?>/forgot-password" class="font-medium text-primary-600 hover:text-primary-500">
                            <?= t('auth.forgot') ?>
                        </a>
                    </div>
                </div>

                <?= $captcha_html ?? '' ?>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <?= t('auth.sign_in') ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?= $captcha_scripts ?? '' ?>

<?php 
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
