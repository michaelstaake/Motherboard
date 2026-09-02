<?php
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/models/WorkOrder.php';
require_once dirname(__DIR__) . '/lib.php';
motherboard_inventory_load_models();

class InventoryController extends Controller {
    private InventoryCategory $categoryModel;
    private InventoryProduct $productModel;
    private InventoryWorkOrderProduct $lineModel;
    private WorkOrder $workOrderModel;

    public function __construct() {
        parent::__construct();
        $this->categoryModel = new InventoryCategory();
        $this->productModel = new InventoryProduct();
        $this->lineModel = new InventoryWorkOrderProduct();
        $this->workOrderModel = new WorkOrder();
    }

    public function index() {
        $this->requireAdmin();

        $search = $_GET['search'] ?? '';
        $categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = PAGINATION_LIMIT;

        $totalCount = $this->productModel->getCount($search ?: null, $categoryId);
        $totalPages = $totalCount > 0 ? (int) ceil($totalCount / $limit) : 1;
        if ($page > $totalPages && $totalCount > 0) {
            $this->redirect('/404');
        }
        $offset = ($page - 1) * $limit;

        $this->viewPath(motherboard_inventory_path() . '/views/index.php', [
            'categories' => $this->categoryModel->getAll(),
            'products' => $this->productModel->getAll($search ?: null, $categoryId, $limit, $offset),
            'search' => $search,
            'categoryId' => $categoryId,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'error' => $_GET['error'] ?? '',
            'message' => $_GET['message'] ?? '',
            'csrf_token' => $this->generateCSRF(),
        ], 'inventory/index');
    }

    public function createCategory() {
        $this->requireAdmin();
        $this->requirePost();
        try {
            $this->validateCSRF();
            $name = $this->sanitizeInput($_POST['name'] ?? '');
            $this->categoryModel->createCategory($name);
            $this->logger->log('inventory_category_created', "Category '{$name}' created", $_SESSION['user_id']);
            $this->redirectInventory('message', t('inventory.category_created'));
        } catch (Exception $e) {
            $this->redirectInventory('error', $e->getMessage());
        }
    }

    public function updateCategory($id) {
        $this->requireAdmin();
        $this->requirePost();
        try {
            $this->validateCSRF();
            $id = (int) $id;
            $category = $this->categoryModel->findById($id);
            if (!$category) {
                throw new Exception(t('inventory.category_not_found'));
            }
            $name = $this->sanitizeInput($_POST['name'] ?? '');
            $this->categoryModel->updateCategory($id, $name);
            $this->logger->log('inventory_category_updated', "Category '{$name}' updated", $_SESSION['user_id']);
            $this->redirectInventory('message', t('inventory.category_updated'));
        } catch (Exception $e) {
            $this->redirectInventory('error', $e->getMessage());
        }
    }

    public function deleteCategory($id) {
        $this->requireAdmin();
        $this->requirePost();
        try {
            $this->validateCSRF();
            $id = (int) $id;
            $category = $this->categoryModel->findById($id);
            if (!$category) {
                throw new Exception(t('inventory.category_not_found'));
            }
            $this->categoryModel->deleteCategory($id);
            $this->logger->log('inventory_category_deleted', "Category '{$category['name']}' deleted", $_SESSION['user_id']);
            $this->redirectInventory('message', t('inventory.category_deleted'));
        } catch (Exception $e) {
            $this->redirectInventory('error', $e->getMessage());
        }
    }

    public function createProduct() {
        $this->requireAdmin();
        $this->requirePost();
        try {
            $this->validateCSRF();
            $data = $this->postedProduct();
            $this->productModel->createProduct($data);
            $this->logger->log('inventory_product_created', "Product '{$data['name']}' created", $_SESSION['user_id']);
            $this->redirectInventory('message', t('inventory.product_created'));
        } catch (Exception $e) {
            $this->redirectInventory('error', $e->getMessage());
        }
    }

    public function updateProduct($id) {
        $this->requireAdmin();
        $this->requirePost();
        try {
            $this->validateCSRF();
            $id = (int) $id;
            $product = $this->productModel->findById($id);
            if (!$product) {
                throw new Exception(t('inventory.product_not_found'));
            }
            $data = $this->postedProduct();
            $this->productModel->updateProduct($id, $data);
            $this->logger->log('inventory_product_updated', "Product '{$data['name']}' updated", $_SESSION['user_id']);
            $this->redirectInventory('message', t('inventory.product_updated'));
        } catch (Exception $e) {
            $this->redirectInventory('error', $e->getMessage());
        }
    }

    public function deleteProduct($id) {
        $this->requireAdmin();
        $this->requirePost();
        try {
            $this->validateCSRF();
            $id = (int) $id;
            $product = $this->productModel->findById($id);
            if (!$product) {
                throw new Exception(t('inventory.product_not_found'));
            }
            $this->productModel->deleteProduct($id);
            $this->logger->log('inventory_product_deleted', "Product '{$product['name']}' deleted", $_SESSION['user_id']);
            $this->redirectInventory('message', t('inventory.product_deleted'));
        } catch (Exception $e) {
            $this->redirectInventory('error', $e->getMessage());
        }
    }

    public function addWorkOrderProduct($id) {
        $this->requireWorkOrderEditor();
        $this->requirePost();
        $workOrder = $this->requireWorkOrder($id);
        try {
            $this->validateCSRF();
            $productId = trim((string) ($_POST['product_id'] ?? ''));
            $quantity = (int) ($_POST['quantity'] ?? 1);
            if ($productId === motherboard_inventory_custom_item_number()) {
                $name = $this->sanitizeInput($_POST['name'] ?? '');
                $this->lineModel->addCustomProduct((int) $workOrder['id'], [
                    'name' => $name,
                    'description' => $this->sanitizeInput($_POST['description'] ?? ''),
                    'price' => trim((string) ($_POST['price'] ?? '0')),
                    'quantity' => $quantity,
                    'taxable' => isset($_POST['taxable']) ? 1 : 0,
                ], $this->productModel);
                $this->workOrderModel->logWorkOrderAction(
                    (int) $workOrder['id'],
                    'inventory_product_added',
                    t('inventory.log_added', ['name' => $name, 'qty' => (string) $quantity])
                );
                $this->logger->log('inventory_product_added', "Added {$quantity} x {$name} to work order #{$workOrder['id']}", $_SESSION['user_id']);
                $this->redirectWorkOrder($workOrder['id'], 'message', t('inventory.wo_added'));
            }

            $productId = (int) $productId;
            if ($productId <= 0) {
                throw new Exception(t('inventory.select_product'));
            }
            $product = $this->productModel->findById($productId);
            if (!$product) {
                throw new Exception(t('inventory.product_not_found'));
            }
            if ($this->productModel->isCustomProduct($product)) {
                throw new Exception(t('inventory.select_product'));
            }
            $this->lineModel->addProduct((int) $workOrder['id'], $productId, $quantity, $this->productModel);
            $this->workOrderModel->logWorkOrderAction(
                (int) $workOrder['id'],
                'inventory_product_added',
                t('inventory.log_added', ['name' => $product['name'], 'qty' => (string) $quantity])
            );
            $this->logger->log('inventory_product_added', "Added {$quantity} x {$product['name']} to work order #{$workOrder['id']}", $_SESSION['user_id']);
            $this->redirectWorkOrder($workOrder['id'], 'message', t('inventory.wo_added'));
        } catch (Exception $e) {
            $this->redirectWorkOrder($workOrder['id'], 'error', $e->getMessage());
        }
    }

    public function updateWorkOrderProduct($id, $lineId) {
        $this->requireWorkOrderEditor();
        $this->requirePost();
        $workOrder = $this->requireWorkOrder($id);
        try {
            $this->validateCSRF();
            $line = $this->requireLine((int) $lineId, (int) $workOrder['id']);
            $quantity = (int) ($_POST['quantity'] ?? 0);
            $this->lineModel->updateQuantity((int) $line['id'], $quantity, $this->productModel);
            $this->workOrderModel->logWorkOrderAction(
                (int) $workOrder['id'],
                'inventory_quantity_updated',
                t('inventory.log_qty', [
                    'name' => $line['product_name'],
                    'old' => (string) $line['quantity'],
                    'new' => (string) $quantity,
                ])
            );
            $this->logger->log('inventory_quantity_updated', "Updated quantity of {$line['product_name']} on work order #{$workOrder['id']}", $_SESSION['user_id']);
            $this->redirectWorkOrder($workOrder['id'], 'message', t('inventory.wo_quantity_updated'));
        } catch (Exception $e) {
            $this->redirectWorkOrder($workOrder['id'], 'error', $e->getMessage());
        }
    }

    public function removeWorkOrderProduct($id, $lineId) {
        $this->requireWorkOrderEditor();
        $this->requirePost();
        $workOrder = $this->requireWorkOrder($id);
        try {
            $this->validateCSRF();
            $line = $this->requireLine((int) $lineId, (int) $workOrder['id']);
            $this->lineModel->removeProduct((int) $line['id'], $this->productModel);
            $this->workOrderModel->logWorkOrderAction(
                (int) $workOrder['id'],
                'inventory_product_removed',
                t('inventory.log_removed', ['name' => $line['product_name'], 'qty' => (string) $line['quantity']])
            );
            $this->logger->log('inventory_product_removed', "Removed {$line['product_name']} from work order #{$workOrder['id']}", $_SESSION['user_id']);
            $this->redirectWorkOrder($workOrder['id'], 'message', t('inventory.wo_removed'));
        } catch (Exception $e) {
            $this->redirectWorkOrder($workOrder['id'], 'error', $e->getMessage());
        }
    }

    private function postedProduct(): array {
        return [
            'name' => $this->sanitizeInput($_POST['name'] ?? ''),
            'item_number' => $this->sanitizeInput($_POST['item_number'] ?? ''),
            'description' => $this->sanitizeInput($_POST['description'] ?? ''),
            'category_id' => $_POST['category_id'] ?? null,
            'price' => trim((string) ($_POST['price'] ?? '0')),
            'stock' => trim((string) ($_POST['stock'] ?? '0')),
            'taxable' => isset($_POST['taxable']) ? 1 : 0,
        ];
    }

    private function requirePost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/404');
        }
    }

    private function requireWorkOrderEditor(): void {
        $this->requireAuth();
        if ($_SESSION['user_group'] === 'Limited') {
            $this->redirect('/403');
        }
    }

    private function requireWorkOrder($id): array {
        $workOrder = $this->workOrderModel->getWorkOrderById($id);
        if (!$workOrder) {
            $this->redirect('/404');
        }
        return $workOrder;
    }

    private function requireLine(int $lineId, int $workOrderId): array {
        $line = $this->lineModel->findLine($lineId);
        if (!$line || (int) $line['work_order_id'] !== $workOrderId) {
            throw new Exception(t('inventory.line_not_found'));
        }
        return $line;
    }

    private function redirectInventory(string $type, string $text): void {
        $query = http_build_query(array_filter([
            $type => $text,
            'search' => $_GET['search'] ?? ($_POST['search'] ?? ''),
            'category' => $_GET['category'] ?? ($_POST['category'] ?? ''),
            'page' => $_GET['page'] ?? ($_POST['page'] ?? ''),
        ], fn($value) => $value !== '' && $value !== null));
        $this->redirect('/inventory' . ($query ? '?' . $query : ''));
    }

    private function redirectWorkOrder($id, string $type, string $text): void {
        $this->redirect('/work-orders/view/' . (int) $id . '?' . http_build_query([$type => $text]));
    }
}
