<?php
require_once "header.php";

// get categories list with optional query parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : null;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : '';
$low_stock_threshold = isset($_GET['low_stock_threshold']) ? intval($_GET['low_stock_threshold']) : null;

// GET /api/products/?page=1 & per_page=10 & category_id=1 &
//                    search=laptop & stock_status=low_stock & low_stock_threshold=10
// Build query parameters
$params = [];

if ($page) {
    $params['page'] = $page;
}
if ($per_page) {
    $params['per_page'] = $per_page;
}
if ($category_id) {
    $params['category_id'] = $category_id;
}
if ($search) {
    $params['search'] = $search;
}
if ($stock_status) {
    $params['stock_status'] = $stock_status;
    // Include low_stock_threshold only if stock_status is low_stock
    if ($stock_status === 'low_stock' && $low_stock_threshold !== null) {
        $params['low_stock_threshold'] = $low_stock_threshold;
    }
}

// append as string
$queryString = http_build_query($params);

// append to URL
$url = $queryString ? "$apiBase/products/?$queryString" : "$apiBase/products/";

// get products with or without params
$res = callAPI("GET", $url, null);
$products = $res['json']['products'] ?? [];

// get deleted products list
// search query
$deleted_search = $_GET['deleted_search'] ?? '';

if (!empty($deleted_search)) {
    // get deleted products with search query
    $deletedRes = callAPI("GET", "$apiBase/products/deleted?search=" . urlencode($deleted_search), null);
} else {
    // get all deleted products
    $deletedRes = callAPI("GET", "$apiBase/products/deleted", null);
}
$deletedProducts = $deletedRes['json']['products'] ?? [];

$flash = null;
$flashSearch = null;

// search using query
if (!empty($search)) {
    // check if products name matches in search
    $matchingProducts = array_filter($products, function($product) use ($search) {
        return stripos($product['name'], $search) !== false; // case insensitive
    });

    // no match found
    if (empty($matchingProducts)) {
        $flashSearch = "No products found matching the search query";

        $res = callAPI("GET", "$apiBase/products/", null);
        $products = $res['json']['products'] ?? [];
        
        $deletedRes = callAPI("GET", "$apiBase/products/deleted", null);
        $deletedProducts = $deletedRes['json']['products'] ?? [];

    }
}

// create product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_product'])) {
    $product_name = $_POST['product_name'] ?? '';
    $category_id  = $_POST['category_id'] ?? '';
    $stock        = $_POST['stock'] ?? 0;

    // request body
    $data = [
        "name" => $product_name,
        "category_id" => (int)$category_id,
        "stock" => (int)$stock
    ];

    $createRes = callAPI("POST", "$apiBase/products/", $data);

    if (isset($createRes['json']['product'])) {
        $flash = "Product created successfully!";
        
        // refresh product list
        $res = callAPI("GET", "$apiBase/products/", null);
        $products = $res['json']['products'] ?? [];

        $deletedRes = callAPI("GET", "$apiBase/products/deleted", null);
        $deletedProducts = $deletedRes['json']['products'] ?? [];
        
    } else {
        // handle error
        if (isset($createRes['json']['error'])) {
            $flash = "Failed to create product: " . htmlspecialchars($createRes['json']['error']);
        }
    }
}

// get product by ID
$viewProduct = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_product'])) {
    // get by id (product not category)
    $product_id = $_POST['product_id'] ?? null;
    $productRes = callAPI("GET", "$apiBase/products/$product_id", null);
    
    if (isset($productRes['json']['id'])) {
        $viewProduct = $productRes['json']; // found product
    } else {
        // error
        if (isset($productRes['json']['error'])) {
            $flash = "Failed to find product: " . htmlspecialchars($productRes['json']['error']);
        }
    }
}

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $actual_method = $_POST['_method'] ?? null;

    $product_id = $_POST['product_id'] ?? null;
    $product_name = $_POST['product_name'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $stock = $_POST['stock'] ?? null;

    if ($product_id) {
        $update = [
            "name" => $product_name,
            "category_id" => (int)$category_id,
            "stock" => (int)$stock
        ];

        $updateRes = callAPI("PUT", "$apiBase/products/$product_id", $update);

        if (isset($updateRes['json']['product'])) {
            $flashSearch = $updateRes['json']['message'] ?? "Product updated successfully.";

            // refresh product list
            $res = callAPI("GET", "$apiBase/products/", null);
            $products = $res['json']['products'] ?? [];

            $deletedRes = callAPI("GET", "$apiBase/products/deleted", null);
            $deletedProducts = $deletedRes['json']['products'] ?? [];
        } else {
            $flashSearch = "Update failed: " . ($updateRes['json']['error'] ?? $updateRes['raw'] ?? "Unknown error");
        }
    }
}

// delete product | soft + hard delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && ($_POST['_method'] === 'DELETE')) {

    $actual_method = $_POST['_method'];
    $product_id    = $_POST['product_id'] ?? null; // get the id
    
    // which button was pressed
    if (isset($_POST['delete_products'])) {
        // soft delete
        $deleteUrl = "$apiBase/products/$product_id";
    }
    elseif (isset($_POST['force_delete_products'])) {
        // force delete
        $deleteUrl = "$apiBase/products/$product_id/force";
    }
    else {
        // unknown DELETE request
        $flash = "Failed: Invalid delete request.";
        return;
    }

    // do API delete
    $res = callAPI("DELETE", $deleteUrl, null);

    // get API result
    if (isset($res['json']['error'])) {
        // server return error (400, 404, etc.)
        $flash = "Failed to delete: " . htmlspecialchars($res['json']['error']);
    } else {
        // success message - soft or force delete
        if (isset($_POST['delete_products'])) {
            $flash = "Product soft deleted: ". htmlspecialchars($res['json']['message']);
        }
        elseif (isset($_POST['force_delete_products'])) {
            $flash = "Product permanently deleted! ". htmlspecialchars($res['json']['message']);
        }
    }

    // refresh product list after delete
    $res = callAPI("GET", "$apiBase/products/", null);
    $products = $res['json']['products'] ?? [];

    $deletedRes = callAPI("GET", "$apiBase/products/deleted", null);
    $deletedProducts = $deletedRes['json']['products'] ?? [];
}

// restore product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_product'])) {
    $product_id = $_POST['product_id'] ?? null;

    if ($product_id) {
        $restoreRes = callAPI("PATCH", "$apiBase/products/$product_id/restore", null);

        if (!empty($restoreRes['json']['message'])) {
            $flash = $restoreRes['json']['message'];
        } else {
            $flash = "Restore failed: " . ($restoreRes['json']['error'] ?? $restoreRes['raw'] ?? "Unknown error");
        }

        // refresh product 
        $res = callAPI("GET", "$apiBase/products/", null);
        $products = $res['json']['products'] ?? [];

        $deletedRes = callAPI("GET", "$apiBase/products/deleted", null);
        $deletedProducts = $deletedRes['json']['products'] ?? [];
    }
}

// update product stock at once not by id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product_stock'])) {
    $actual_method = $_POST['_method'] ?? null;

    // array of input product id and stock
    $productsInput = $_POST['products'] ?? [];

    // reformat input to match API requirement
    $bulkStock = ["products" => []];

    foreach ($productsInput as $p) {
        if (!empty($p['id']) && isset($p['stock'])) {
            $bulkStock["products"][] = [
                "id" => (int)$p['id'],
                "stock" => (int)$p['stock']
            ];
        }
    }

    // avoid empty input
    if (empty($bulkStock["products"])) {
        $flash = "No valid product rows provided.";
    } else {
        $updateRes = callAPI("PUT", "$apiBase/products/bulk-update-stock", $bulkStock);

        // get json message
        $json = $updateRes['json'] ?? [];
        $updatedCount = $json['updated_count'] ?? 0;
        $errors       = $json['errors'] ?? [];
        $message      = $json['message'] ?? null;

        // if update success
        if ($updatedCount > 0 && empty($errors)) {
            $flash = $message ?? "Stock updated successfully.";
        } elseif ($updatedCount > 0 && !empty($errors)) {
            // if partial update success
            $flash  = ($message ?? "Some products updated.");
            $flash .= "Warnings: ";
            foreach ($errors as $err) {
                $flash .= htmlspecialchars($err);
            }
        } else {
            // if update failed
            $flash  = "Update failed: ";
            if (!empty($errors)) {
                foreach ($errors as $err) {
                    $flash .= htmlspecialchars($err);
                }
            } else {
                $flash .= htmlspecialchars($json['error'] ?? $updateRes['raw'] ?? "Unknown error");
            }
        }

        // refresh if something updated
        if ($updatedCount > 0) {
            $res = callAPI("GET", "$apiBase/products/", null);
            $products = $res['json']['products'] ?? [];

            $deletedRes = callAPI("GET", "$apiBase/products/deleted", null);
            $deletedProducts = $deletedRes['json']['products'] ?? [];
        }

    }
    
}



?>

<!-- UI -->
<div class="card">

    <!-- left column: products list -->
    <div style="flex: 1; padding-right: 20px;">
        <h2>Products</h2>

        <!-- no products found by the search -->
        <?php if ($flashSearch): ?>
            <div style="padding:10px;margin-bottom:12px;border-radius:6px;
            border:1px solid <?= stripos($flash, 'failed') !== false ? 'var(--flash-error-border)' : 'var(--flash-success-border)' ?>;
            background: <?= stripos($flash, 'failed') !== false ? 'var(--flash-error-bg)' : 'var(--flash-success-bg)' ?>;
            color: var(--flash-text);
        ">
                <?= htmlspecialchars($flashSearch) ?>
            </div>
        <?php endif; ?>

        <!-- search, page, items per page, product category id, stock status -->
        <form method="GET" style="margin-bottom: 20px;">
            <!-- search -->
            <div style="margin-bottom: 15px;">
                <h4>Search by products Name</h4>
                <input 
                    type="text" 
                    name="search" placeholder="Search by products name"
                    value="<?= htmlspecialchars($search) ?>"
                    style="width: 95%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
            </div>

            <!-- page  -->
            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <div style="flex: 0.5;">
                    <h4>Page Number</h4>
                    <input
                        type="number"
                        name="page" value="<?= htmlspecialchars($page) ?>"
                        placeholder= "Page number"
                        style="width: 90%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                </div>
                <div style="flex: 0.5;">
                    <h4>Items per Page</h4>
                    <input 
                        type="number"
                        name="per_page" value="<?= htmlspecialchars($per_page) ?>" max="100"
                        placeholder="Items per page"
                        style="width: 90%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                </div>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px;">
                <!-- category ID -->
                <div style="flex: 0.5;">
                    <h4>Category ID</h4>
                    <input 
                        type="number" 
                        name="category_id" 
                        value="<?= htmlspecialchars($category_id ?? '') ?>"
                        placeholder="Category ID"
                        style="width: 90%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                </div>

                <!-- stock status -->
                <div style="flex: 0.5;">
                    <h4>Stock Status</h4>
                    <select name="stock_status" style="width: 95%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                        <option value="" <?= $stock_status === '' ? 'selected' : '' ?>>All</option>
                        <option value="in_stock" <?= $stock_status === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
                        <option value="out_of_stock" <?= $stock_status === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        <option value="low_stock" <?= $stock_status === 'low_stock' ? 'selected' : '' ?>>Low Stock</option>
                    </select>
                </div>

                <!-- low stock threshold -->
                <div style="flex: 0.5;">
                    <h4>Low Stock Threshold</h4>
                    <input 
                        type="number" 
                        name="low_stock_threshold" 
                        value="<?= htmlspecialchars($low_stock_threshold ?? 10) ?>" 
                        placeholder="Low stock threshold"
                        style="width: 85%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" style="background: #0073e6; color: white; padding: 10px; border: 0; border-radius: 6px; cursor: pointer;">
                Search
            </button>
        </form>
    
    <hr style="border: 0; border-top: 1px solid #ddd; margin: 25px 0;">

        <!-- products list -->
        <h3>Existing Products</h3>
        <ul style="list-style-type: none; padding-left: 0;">
        <?php foreach ($products as $p): ?>
            <li style="
                background: #f7f7f7;
                padding: 10px;
                margin-bottom: 8px;
                border-radius: 6px;
                display: block;
                justify-content: space-between;
                align-items: center;
            ">

                <div
                    style="width: 100%; display: flex; padding: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
                            gap: 30px; flex-wrap: wrap;">

                    <!-- product info -->
                    <div style="flex: 1 1 200px; ">
                        <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                        <p style="margin: 6px 0;">
                            <strong>ID:</strong> <?= htmlspecialchars($p['id'] ?? 'N/A') ?><br>
                            <strong>Stock:</strong> <?= htmlspecialchars($p['stock'] ?? 'N/A') ?><br>
                            <strong>Created:</strong> <?= htmlspecialchars($p['created_at'] ?? 'N/A') ?><br>
                            <strong>Updated:</strong> <?= htmlspecialchars($p['updated_at'] ?? 'N/A') ?>
                        </p>

                    </div>

                    <!-- category (of product) info -->
                    <div style="flex: 1 1 100px; border-radius: 6px; padding: 10px; justify-content: top;">
                        <strong>Category</strong><br>
                        <p style="margin: 6px 0;">
                            <strong>ID: <?= htmlspecialchars($p['category']['id'] ?? 'N/A') ?><br>
                                    Name:<?= htmlspecialchars($p['category']['name'] ?? 'N/A') ?> </strong>
                        </p>
                    </div>

                    <!-- delete buttons -->
                    <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; margin-right: 5%;">
                        <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">

                            <!-- soft delete -->
                            <form method="POST">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($p['id']) ?>">
                                <button
                                    type="submit"
                                    name="delete_products"
                                    style="background: #d9534f; color: white; padding: 10px 15px; border: 0;
                                        border-radius: 6px; cursor: pointer;">
                                    Delete
                                </button>
                            </form>

                            <!-- force delete -->
                            <form method="POST">
                                <input type="hidden" name="_method" value="DELETE">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($p['id']) ?>">
                                <input type="hidden" name="force_delete" value="1">
                                <button
                                    type="submit"
                                    name="force_delete_products"
                                    style="background: #b52b27; color: white; padding: 10px 15px; border: 0;
                                        border-radius: 6px; cursor: pointer;">
                                    Force Delete
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div style="margin: 10px 5% 0px 10px; padding-top: 15px; border-top: 1px solid #ddd;">
                <!-- update product -->
                    <label style="margin-left: 1%"> <strong>Update Product:</strong>
                        <form method="POST" 
                            style="margin-top: 12px; display: flex; flex-direction: column; width: 100%;">
                            
                            <input type="hidden" name="_method" value="PUT">
                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($p['id']) ?>">

                            <!-- update name -->
                            <input
                                type="text"
                                name="product_name"
                                id="product_name_<?= $p['id'] ?>"
                                placeholder="Enter new name"
                                style="margin-top: 6px; width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                            

                            <!-- update category ID -->
                            <input
                                type="number"
                                name="category_id"
                                id="category_id_<?= $p['id'] ?>"
                                placeholder="Enter category ID"
                                style="margin-top: 6px; width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">

                            <!-- update stock -->
                            <input
                                type="number"
                                name="stock"
                                id="stock_<?= $p['id'] ?>"
                                placeholder="Enter stock"
                                style="margin-top: 6px; width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">

                            <!-- submit -->
                            <button
                                type="submit"
                                name="update_product"
                                style="margin-top: 12px; background: #0073e6; color: white; padding: 10px 15px; 
                                    border: 0; border-radius: 6px; cursor: pointer; width: fit-content;">
                                Update Product
                            </button>
                        </form>
                    </label>
                </div>

            </li>
        <?php endforeach; ?>
        </ul>

        

    </div>

    <!-- right column: view & add products form -->
    <div style="flex: 0 0 320px; padding-left: 20px; border-left: 1px solid #ccc;">

        <!-- warning message -->
        <?php if ($flash): ?>
            <div style="padding:10px;margin-bottom:12px;border-radius:6px;
                border:1px solid <?= stripos($flash, 'failed') !== false ? 'var(--flash-error-border)' : 'var(--flash-success-border)' ?>;
                background: <?= stripos($flash, 'failed') !== false ? 'var(--flash-error-bg)' : 'var(--flash-success-bg)' ?>;
                color: var(--flash-text);
            ">
                        <?= htmlspecialchars($flash) ?> 
            </div>
        <?php endif; ?>

        <!-- view profuct by id -->
        <h3>Get Product by ID</h3>
        <form method="POST" style="margin-bottom: 20px;">
            
                <input
                    type="number"
                    name="product_id"
                    placeholder="Enter product ID"
                    style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 6px; width: 80%; margin-bottom: 10px;" required>
                <button type="submit"
                        name="view_product"
                        style="background: #0073e6; color: white; padding: 10px; border: 0; border-radius: 6px; cursor: pointer;">
                    Get Product
                </button>

        </form>

        <?php if ($viewProduct): ?>
            <h3>Product Details</h3>
            <div style="background: #f7f7f7; padding: 10px; margin-bottom: 8px; border-radius: 6px;">
                <p><strong>Name:</strong> <?= htmlspecialchars($viewProduct['name']) ?></p>
                <p><strong>Category ID: <?= htmlspecialchars($viewProduct['category']['id']) ?><br>
                            Category Name: <?= htmlspecialchars($viewProduct['category']['name']) ?><br>
                            Created: <?= htmlspecialchars($viewProduct['created_at']) ?><br>
                            Updated: <?= htmlspecialchars($viewProduct['updated_at']) ?> </strong></p>
            </div>
        <?php endif; ?>


        <hr style="border: 0; border-top: 1px solid #ddd; margin: 25px 0;">

        <!-- add new products -->
        <h3>Add New products</h3>
        <form method="POST">
            <input type="text" name="product_name" placeholder="Product Name" 
                    style="width: 80%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 10px;"> 
            
            <input type="number" name="category_id" placeholder="Category ID"
                style="width: 80%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 10px;"> 

            <input type="number" name="stock" placeholder="Stock"
                    style="width: 80%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; margin-bottom: 10px;"> 
            
            <button type="submit" name="create_product"
                    style="background: #0073e6; color: white; padding: 10px; border: 0; border-radius: 6px; cursor: pointer;">
                Create Product</button>
        </form>
        
        <!-- bulk update stock -->
        <h3 style="margin-top: 30px;">Bulk Update Stock</h3>

        <form method="POST" style="margin-bottom: 20px;">
            <input type="hidden" name="_method" value="PUT">

            <!-- container for dynamic rows -->
            <div id="bulk-stock-list" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px;">

                <!-- one row template -->
                <div class="bulk-row" style="display: flex; gap: 10px;">
                    <input 
                        type="number" 
                        name="products[0][id]" 
                        placeholder="Product ID"
                        style="width: 40%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                    
                    <input 
                        type="number" 
                        name="products[0][stock]" 
                        placeholder="New Stock"
                        style="width: 40%; padding: 8px; border-radius: 6px; border: 1px solid #ccc;">

                    <button type="button" class="remove-row"
                        style="background:#d9534f; color:white; border:0; padding:5px 10px; border-radius:6px; cursor:pointer;">
                        X
                    </button>
                </div>

            </div>

            <button type="button" id="add-row"
                style="background:#6c757d; color:white; padding:7px 12px; border-radius:6px; cursor:pointer; margin-bottom:10px;">
                + Add Row
            </button>

            <br>

            <button type="submit" name="update_product_stock"
                style="background:#0073e6; color:white; padding:10px 15px; border:0; border-radius:6px; cursor:pointer;">
                Update Stock
            </button>
        </form>

        <script>
        function reindexRows() {
            const rows = document.querySelectorAll("#bulk-stock-list .bulk-row");

            rows.forEach((row, index) => {
                const idInput = row.querySelector('input[name*="[id]"]');
                const stockInput = row.querySelector('input[name*="[stock]"]');

                idInput.name = `products[${index}][id]`;
                stockInput.name = `products[${index}][stock]`;
            });

            // hide delete button if only 1 row
            const allowDelete = rows.length > 1;
            rows.forEach(row => {
                row.querySelector(".remove-row").style.display = allowDelete ? "inline-block" : "none";
            });
        }

        document.getElementById("add-row").addEventListener("click", function () {
            const container = document.getElementById("bulk-stock-list");
            const firstRow = container.querySelector(".bulk-row");
            const newRow = firstRow.cloneNode(true);

            // clear input values
            newRow.querySelectorAll("input").forEach(input => input.value = "");

            container.appendChild(newRow);
            reindexRows();
        });

        document.addEventListener("click", function (e) {
            if (e.target.classList.contains("remove-row")) {
                e.target.closest(".bulk-row").remove();
                reindexRows();
            }
        });

        // initial setup
        reindexRows();
        </script>



        <hr style="border: 0; border-top: 1px solid #ddd; margin: 25px 0;">
        
        <!-- restore + force delete -->
        <h3>Deleted Products</h3>

        <form method="GET" style="margin-bottom: 10px;">
            <input 
                type="text"
                name="deleted_search"
                placeholder="Search deleted products..."
                value="<?= htmlspecialchars($_GET['deleted_search'] ?? '') ?>"
                style="padding: 8px; border-radius: 6px; border: 1px solid #ccc; width: 250px;">
            <button type="submit" 
                    style="margin-top: 10px; background:#0073e6; color:white; padding:10px 15px; border:0; border-radius:6px; cursor:pointer;">
                    Search</button>
        </form>

        <ul style="list-style-type: none; padding-left: 0;">
        <?php foreach ($deletedProducts as $p): ?>
            <li data-surface 
            style="background:#fff3cd; padding:10px; margin-bottom:8px; border-radius:6px;">

                <strong><?= htmlspecialchars($p['name']) ?></strong> (deleted) <br>
                <strong>ID:  <?= htmlspecialchars($p['id']) ?><br>
                        Category ID:  <?= htmlspecialchars($p['category']['id']) ?><br>
                        Category Name:  <?= htmlspecialchars($p['category']['name']) ?><br>
                        Deleted At:  <?= htmlspecialchars($p['deleted_at']) ?><br> </strong>


                <div style="display: flex; gap: 10px; margin-top: 12px;">
                    <!-- restore -->
                    <form method="POST" style="margin-top:10px;">
                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($p['id']) ?>">
                        <button type="submit" name="restore_product"
                                style="background:#28a745; color:white; padding:6px 12px; border-radius:6px; cursor: pointer;">
                            Restore
                        </button>
                    </form>

                    <!-- force delete -->
                    <form method="POST" style="margin-top:10px;">
                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($p['id']) ?>">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" name="force_delete_products"
                                style="background:#dc3545; color:white; padding:6px 12px; border-radius:6px; cursor: pointer;">
                            Force Delete
                        </button>
                    </form>

                </div>

            </li>
        <?php endforeach; ?>
        </ul>

    </div>

</div>

<script>
document.addEventListener("submit", function (e) {
    // target only product update forms (adjust selector if needed)
    const inputs = e.target.querySelectorAll("input[name='product_name']");

    inputs.forEach(input => {
        if (!input.value) return;

        let v = input.value.trim().toLowerCase();

        // Capitalize first letter + after spaces
        v = v.charAt(0).toUpperCase() + v.slice(1);
        v = v.replace(/\s+\w/g, s => s.toUpperCase());

        input.value = v;
    });
});
</script>

<?php require_once "footer.php"; ?>