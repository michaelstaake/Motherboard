<?php 
$title = t('auth.reset_title') . ' - ' . ($companyName ?? APP_NAME);
$hideNavigation = true;
ob_start(); 
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                <?= t('auth.reset_title') ?>
            </h2>
            <p class="mt-2 text-center text-gray-600">
                <?= t('auth.reset_help') ?>
            </p>
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
                    <a href="<?= BASE_URL ?>/login" class="text-primary-600 hover:text-primary-500">
                        <?= t('auth.return_login') ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form class="mt-8 space-y-6" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div>
                    <label for="email" class="sr-only"><?= t('auth.email') ?></label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           required 
                           class="appearance-none rounded-md relative block w-full px-4 py-3 border-2 border-gray-300 placeholder-gray-500 text-gray-900 shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
                           placeholder="<?= htmlspecialchars(t('auth.email')) ?>">
                </div>

                <?= $captcha_html ?? '' ?>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <?= t('auth.send_reset') ?>
                    </button>
                </div>

                <div class="text-center">
                    <a href="<?= BASE_URL ?>/login" class="text-primary-600 hover:text-primary-500">
                        <?= t('auth.back_login') ?>
                    </a>
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
