<?php
$title = t('inventory.title') . ' - ' . ($companyName ?? APP_NAME);
ob_start();
$categoryId = $categoryId ?? null;
$searchQuery = $search ?? '';
?>

<div class="py-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold text-gray-900"><?= t('inventory.title') ?></h1>
            <p class="mt-2 text-sm text-gray-700"><?= t('inventory.subtitle') ?></p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <button type="button" onclick="openProductModal()" class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                <?= t('inventory.add_product') ?>
            </button>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="mt-6 bg-green-50 border border-green-200 rounded-md p-4">
            <p class="text-sm text-green-600"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
            <p class="text-sm text-red-600"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900"><?= t('inventory.categories') ?></h2>
                    <button type="button" onclick="openCategoryModal()" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                        <?= t('inventory.add_category') ?>
                    </button>
                </div>
                <div class="divide-y divide-gray-200">
                    <a href="<?= BASE_URL ?>/inventory<?= $searchQuery ? '?search=' . urlencode($searchQuery) : '' ?>" class="block px-6 py-3 text-sm <?= $categoryId === null ? 'bg-primary-50 text-primary-700 font-medium' : 'text-gray-700 hover:bg-gray-50' ?>">
                        <?= t('inventory.all_categories') ?>
                    </a>
                    <?php if (empty($categories)): ?>
                        <p class="px-6 py-4 text-sm text-gray-500"><?= t('inventory.no_categories') ?></p>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <div class="px-6 py-3 flex items-center justify-between <?= (int) $categoryId === (int) $category['id'] ? 'bg-primary-50' : '' ?>">
                                <a href="<?= BASE_URL ?>/inventory?category=<?= (int) $category['id'] ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>" class="text-sm <?= (int) $categoryId === (int) $category['id'] ? 'text-primary-700 font-medium' : 'text-gray-700 hover:text-gray-900' ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                    <span class="text-gray-400">(<?= (int) $category['product_count'] ?>)</span>
                                </a>
                                <div class="flex items-center space-x-2">
                                    <button type="button" class="text-sm text-primary-600 hover:text-primary-500" onclick="openCategoryModal(<?= (int) $category['id'] ?>, <?= htmlspecialchars(json_encode($category['name']), ENT_QUOTES) ?>)"><?= t('common.edit') ?></button>
                                    <form method="POST" action="<?= BASE_URL ?>/inventory/categories/<?= (int) $category['id'] ?>/delete" onsubmit="return confirm(<?= json_encode(t('inventory.confirm_delete_category')) ?>)">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-500"><?= t('common.delete') ?></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <form method="GET" action="<?= BASE_URL ?>/inventory" class="flex space-x-4">
                <?php if ($categoryId): ?>
                    <input type="hidden" name="category" value="<?= (int) $categoryId ?>">
                <?php endif; ?>
                <div class="flex-1">
                    <label for="search" class="sr-only"><?= t('common.search') ?></label>
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="<?= htmlspecialchars(t('inventory.search')) ?>" class="block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                    <?= t('common.search') ?>
                </button>
                <?php if ($searchQuery): ?>
                    <a href="<?= BASE_URL ?>/inventory<?= $categoryId ? '?category=' . (int) $categoryId : '' ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <?= t('common.clear') ?>
                    </a>
                <?php endif; ?>
            </form>

            <div class="mt-4 shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('inventory.product_name') ?></th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('inventory.price') ?></th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('inventory.stock') ?></th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= t('inventory.sold') ?></th>
                            <th scope="col" class="relative px-4 py-3"><span class="sr-only"><?= t('common.actions') ?></span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">
                                    <?= $searchQuery ? t('inventory.none_search') : t('inventory.none_yet') ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 relative group <?= !empty($product['description']) ? 'cursor-help' : '' ?>">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></div>
                                        <?php if (!empty($product['item_number'])): ?>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($product['item_number']) ?></div>
                                        <?php endif; ?>
                                        <div class="text-xs text-gray-400"><?= htmlspecialchars($product['category_name'] ?: t('inventory.uncategorized')) ?></div>
                                        <?php if (!empty($product['description'])): ?>
                                            <div role="tooltip" class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-opacity absolute z-30 left-4 top-full mt-1 w-max max-w-xs rounded-md bg-gray-900 px-3 py-2 text-left text-xs font-normal text-white shadow-lg pointer-events-none"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars(motherboard_inventory_format_price($product['price'])) ?>
                                        <?php if (!empty($product['taxable'])): ?>
                                            <span class="ml-1 text-xs font-medium text-gray-500" title="<?= htmlspecialchars(t('inventory.taxable')) ?>"><?= t('inventory.taxable_mark') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars(motherboard_inventory_format_stock($product['stock'])) ?></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= (int) $product['sold_count'] ?></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <button type="button" class="text-primary-600 hover:text-primary-900" onclick='openProductModal(<?= json_encode([
                                            'id' => (int) $product['id'],
                                            'name' => $product['name'],
                                            'item_number' => $product['item_number'] ?? '',
                                            'description' => $product['description'] ?? '',
                                            'category_id' => $product['category_id'] !== null ? (int) $product['category_id'] : '',
                                            'price' => $product['price'],
                                            'stock' => (int) $product['stock'],
                                            'taxable' => !empty($product['taxable']),
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'><?= t('common.edit') ?></button>
                                        <form method="POST" action="<?= BASE_URL ?>/inventory/products/<?= (int) $product['id'] ?>/delete" class="inline" onsubmit="return confirm(<?= json_encode(t('inventory.confirm_delete_product')) ?>)">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900"><?= t('common.delete') ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-4">
                    <p class="text-sm text-gray-700">
                        <?= t('wo.showing_page', ['current' => $currentPage, 'total' => $totalPages]) ?>
                    </p>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?><?= $categoryId ? '&category=' . (int) $categoryId : '' ?>" class="<?= $i === $currentPage ? 'bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 1000;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 id="categoryModalTitle" class="text-lg font-medium text-gray-900 mb-4"><?= t('inventory.add_category') ?></h3>
        <form id="categoryForm" method="POST" action="<?= BASE_URL ?>/inventory/categories">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="mb-4">
                <label for="category_name" class="block text-sm font-medium text-gray-700"><?= t('inventory.category_name') ?> *</label>
                <input type="text" id="category_name" name="name" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeCategoryModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"><?= t('common.cancel') ?></button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700"><?= t('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<div id="productModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 1000;">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <h3 id="productModalTitle" class="text-lg font-medium text-gray-900 mb-4"><?= t('inventory.add_product') ?></h3>
        <form id="productForm" method="POST" action="<?= BASE_URL ?>/inventory/products">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="mb-4">
                <label for="product_category_id" class="block text-sm font-medium text-gray-700"><?= t('inventory.category') ?></label>
                <select id="product_category_id" name="category_id" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <option value=""><?= t('inventory.uncategorized') ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="product_name" class="block text-sm font-medium text-gray-700"><?= t('inventory.product_name') ?> *</label>
                <input type="text" id="product_name" name="name" required class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
            </div>
            <div class="mb-4">
                <label for="product_item_number" class="block text-sm font-medium text-gray-700"><?= t('inventory.item_number') ?> *</label>
                <input type="text" id="product_item_number" name="item_number" required maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                <p class="mt-1 text-xs text-gray-500">
                    <?= t('inventory.item_number_help') ?>
                    <button type="button" id="product_item_number_generate" class="font-medium text-primary-600 hover:text-primary-500"><?= t('inventory.item_number_generate') ?></button>
                </p>
            </div>
            <div class="mb-4">
                <label for="product_description" class="block text-sm font-medium text-gray-700"><?= t('inventory.description') ?></label>
                <textarea id="product_description" name="description" rows="3" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="product_price" class="block text-sm font-medium text-gray-700"><?= t('inventory.price') ?> *</label>
                    <input type="number" step="0.01" min="0" id="product_price" name="price" required value="0.00" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                </div>
                <div>
                    <label for="product_stock" class="block text-sm font-medium text-gray-700"><?= t('inventory.stock') ?> *</label>
                    <input type="number" step="1" id="product_stock" name="stock" required value="0" class="mt-1 block w-full px-4 py-3 border-2 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white">
                    <p class="mt-1 text-xs text-gray-500"><?= t('inventory.stock_help') ?></p>
                </div>
            </div>
            <div class="mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" id="product_taxable" name="taxable" value="1" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="ml-2 text-sm text-gray-700"><?= t('inventory.taxable') ?></span>
                </label>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeProductModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"><?= t('common.cancel') ?></button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700"><?= t('common.save') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openCategoryModal(id, name) {
    const form = document.getElementById('categoryForm');
    const title = document.getElementById('categoryModalTitle');
    document.getElementById('category_name').value = name || '';
    form.action = id ? <?= json_encode(BASE_URL . '/inventory/categories/') ?> + id : <?= json_encode(BASE_URL . '/inventory/categories') ?>;
    title.textContent = id ? <?= json_encode(t('inventory.edit_category')) ?> : <?= json_encode(t('inventory.add_category')) ?>;
    document.getElementById('categoryModal').classList.remove('hidden');
}
function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
    document.getElementById('categoryForm').reset();
}
function openProductModal(product) {
    const form = document.getElementById('productForm');
    const title = document.getElementById('productModalTitle');
    form.reset();
    document.getElementById('product_taxable').checked = true;
    if (product) {
        form.action = <?= json_encode(BASE_URL . '/inventory/products/') ?> + product.id;
        title.textContent = <?= json_encode(t('inventory.edit_product')) ?>;
        document.getElementById('product_name').value = product.name || '';
        document.getElementById('product_item_number').value = product.item_number || '';
        document.getElementById('product_description').value = product.description || '';
        document.getElementById('product_category_id').value = product.category_id || '';
        document.getElementById('product_price').value = product.price;
        document.getElementById('product_stock').value = product.stock;
        document.getElementById('product_taxable').checked = !!product.taxable;
    } else {
        form.action = <?= json_encode(BASE_URL . '/inventory/products') ?>;
        title.textContent = <?= json_encode(t('inventory.add_product')) ?>;
        document.getElementById('product_price').value = '0.00';
        document.getElementById('product_stock').value = '0';
    }
    document.getElementById('productModal').classList.remove('hidden');
}
function closeProductModal() {
    document.getElementById('productModal').classList.add('hidden');
    document.getElementById('productForm').reset();
}
document.getElementById('categoryModal').addEventListener('click', function (e) {
    if (e.target === this) closeCategoryModal();
});
document.getElementById('productModal').addEventListener('click', function (e) {
    if (e.target === this) closeProductModal();
});
(function () {
    const field = document.getElementById('product_item_number');
    const nameField = document.getElementById('product_name');
    function slugifyItemNumber(value) {
        return value
            .toLowerCase()
            .trim()
            .replace(/[\s_]+/g, '-')
            .replace(/[^a-z0-9-]/g, '')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 100)
            .replace(/-+$/g, '');
    }
    field.addEventListener('input', function () {
        const slugged = this.value
            .toLowerCase()
            .replace(/[\s_]+/g, '-')
            .replace(/[^a-z0-9-]/g, '');
        if (this.value !== slugged) {
            this.value = slugged;
        }
    });
    field.addEventListener('blur', function () {
        this.value = slugifyItemNumber(this.value);
    });
    document.getElementById('product_item_number_generate').addEventListener('click', function () {
        const slug = slugifyItemNumber(nameField.value);
        if (!slug) {
            nameField.focus();
            return;
        }
        field.value = slug;
        field.dispatchEvent(new Event('input', { bubbles: true }));
    });
    document.getElementById('productForm').addEventListener('submit', function () {
        field.value = slugifyItemNumber(field.value);
    });
})();
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layout.php';
?>
