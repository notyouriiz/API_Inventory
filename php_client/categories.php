<?php
require_once "header.php";

// get categories list with optional query parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : null;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query parameters
$params = [];

if ($page) {
    $params['page'] = $page;
}
if ($per_page) {
    $params['per_page'] = $per_page;
}
if ($search) {
    $params['search'] = $search;
}

// append as string
$queryString = http_build_query($params);

// append to URL
$url = $queryString ? "$apiBase/categories/?$queryString" : "$apiBase/categories/";

// get categories with or without params
$res = callAPI("GET", $url, null);
$categories = $res['json']['categories'] ?? [];

// get deleted categories list
$deletedRes = callAPI("GET", "$apiBase/categories/deleted", null);
$deletedCategories = $deletedRes['json']['categories'] ?? [];


$flash = null;
$flashSearch = null;

// search categories by name
if (!empty($search)) {
    // check if category name matches in search
    $matchingCategories = array_filter($categories, function($category) use ($search) {
        return stripos($category['name'], $search) !== false; // case insensitive
    });

    // no match found
    if (empty($matchingCategories)) {
        $flashSearch = "No categories found matching the search query";
        $res = callAPI("GET", "$apiBase/categories/", null);
        $categories = $res['json']['categories'] ?? [];

        $deletedRes = callAPI("GET", "$apiBase/categories/deleted", null);
        $deletedCategories = $deletedRes['json']['categories'] ?? [];
    }
}

// create category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $category_name = $_POST['category_name'] ?? '';
    
    // allow even if empty name
    $data = [
        "name" => $category_name,
    ];
    $createRes = callAPI("POST", "$apiBase/categories/", $data);

    if (isset($createRes['json']['category'])) {
        $flash = "Category created successfully!";
        // refresh after adding
        $res = callAPI("GET", "$apiBase/categories/", null);
        $categories = $res['json']['categories'] ?? [];

        $deletedRes = callAPI("GET", "$apiBase/categories/deleted", null);
        $deletedCategories = $deletedRes['json']['categories'] ?? [];

    } else {
        // error
        if (isset($createRes['json']['error'])) {
            $flash = "Failed to create category: " . htmlspecialchars($createRes['json']['error']);
        }
    }
}

// get category by ID
$viewCategory = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_category'])) {
    // get by id
    $category_id = $_POST['category_id'] ?? null;
    $categoryRes = callAPI("GET", "$apiBase/categories/$category_id", null);
    
    if (isset($categoryRes['json']['id'])) {
        $viewCategory = $categoryRes['json']; // found category
    } else {
        // error
        if (isset($categoryRes['json']['error'])) {
            $flash = "Failed to find category: " . htmlspecialchars($categoryRes['json']['error']);
        }
    }
}

// update category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $actual_method = $_POST['_method'] ?? null;

    $category_id = $_POST['category_id'] ?? null;
    $category_name = $_POST['category_name'] ?? '';

    if ($category_id) {
        $update = [
            "name" => $category_name
        ];

        $updateRes = callAPI("PUT", "$apiBase/categories/$category_id", $update);

        if (isset($updateRes['json']['category'])) {
            $flashSearch = $updateRes['json']['message'] ?? "Category updated successfully.";

            $res = callAPI("GET", "$apiBase/categories/", null);
            $categories = $res['json']['categories'] ?? [];

        } else {
            $flashSearch = "Update failed: " . ($updateRes['json']['error'] ?? $updateRes['raw'] ?? "Unknown error");
        }
    }
}

// delete category | soft + hard delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && ($_POST['_method'] === 'DELETE')) {

    $actual_method = $_POST['_method'];
    $categoryId    = $_POST['category_id'] ?? null; // get the id

    // which button was pressed
    if (isset($_POST['delete_category'])) {
        // soft delete
        $deleteUrl = "$apiBase/categories/$categoryId";
    }
    elseif (isset($_POST['force_delete_category'])) {
        // force delete
        $deleteUrl = "$apiBase/categories/$categoryId/force";
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
        if (isset($_POST['delete_category'])) {
            $flash = "Category soft deleted: ". htmlspecialchars($res['json']['message']);
        }
        elseif (isset($_POST['force_delete_category'])) {
            $flash = "Category permanently deleted! ". htmlspecialchars($res['json']['message']);
        }
    }

    // refresh category list after delete
    $reload = callAPI("GET", "$apiBase/categories/", null);
    $categories = $reload['json']['categories'] ?? [];

    $deletedRes = callAPI("GET", "$apiBase/categories/deleted", null);
    $deletedCategories = $deletedRes['json']['categories'] ?? [];
}


// restore category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_category'])) {
    $category_id = $_POST['category_id'] ?? null;

    if ($category_id) {
        $restoreRes = callAPI("PATCH", "$apiBase/categories/$category_id/restore", null);

        if (!empty($restoreRes['json']['message'])) {
            $flash = $restoreRes['json']['message'];
        } else {
            $flash = "Restore failed: " . ($restoreRes['json']['error'] ?? $restoreRes['raw'] ?? "Unknown error");
        }

        // Refresh lists
        $res = callAPI("GET", "$apiBase/categories/", null);
        $categories = $res['json']['categories'] ?? [];

        $deletedRes = callAPI("GET", "$apiBase/categories/deleted", null);
        $deletedCategories = $deletedRes['json']['categories'] ?? [];
    }
}

?>

<!-- UI -->
<div style="display: flex; max-width: 1200px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06); font-family: Segoe UI, Roboto, Arial, sans-serif;">

    <!-- left column: categories list -->
    <div style="flex: 1; padding-right: 20px;">
        <h2>Categories</h2>

        <!-- no category found by the search -->
        <?php if ($flashSearch): ?>
            <div style="padding:10px;margin-bottom:12px;border-radius:6px;
                border:1px solid <?= strpos($flashSearch, 'failed') !== false ? '#f5b7b7' : '#b7f0b7' ?>;
                background: <?= strpos($flashSearch, 'failed') !== false ? '#ffe6e6' : '#e8ffe8' ?>;">
                        <?= htmlspecialchars($flashSearch) ?>
            </div>
        <?php endif; ?>

        <!-- search, page, items per page -->
        <form method="GET" style="margin-bottom: 20px;">
            <!-- search -->
            <div style="margin-bottom: 15px;">
                <h4>Search by Category Name</h4>
                <input 
                    type="text" 
                    name="search" placeholder="Search by category name"
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

            <!-- Submit Button -->
            <button type="submit" style="background: #0073e6; color: white; padding: 10px; border: 0; border-radius: 6px; cursor: pointer;">
                Search
            </button>
        </form>
    
    <hr style="border: 0; border-top: 1px solid #ddd; margin: 25px 0;">

        <!-- categories list -->
        <h3>Existing Categories</h3>
        <ul style="list-style-type: none; padding-left: 0;">
        <?php foreach ($categories as $c): ?>
            <li style="
                background: #f7f7f7;
                padding: 10px;
                margin-bottom: 8px;
                border-radius: 6px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            ">

                <!-- left side -->
                <div>
                    <strong><?= htmlspecialchars($c['name']) ?></strong><br>

                    <p style="margin: 6px 0;">
                        <strong>ID:</strong> <?= htmlspecialchars($c['id'] ?? 'N/A') ?><br>
                        <strong>Created:</strong> <?= htmlspecialchars($c['created_at'] ?? 'N/A') ?><br>
                        <strong>Updated:</strong> <?= htmlspecialchars($c['updated_at'] ?? 'N/A') ?>
                    </p>

                    <form method="POST" style="margin-top: 10px; display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="_method" value="PUT" />
                        <input type="hidden" name="category_id" value="<?= htmlspecialchars($c['id']) ?>" />

                        <div>
                            <strong><label for="category_name_<?= $c['id'] ?>">Update Category Name:</label></strong><br>
                            <input
                                type="text"
                                name="category_name"
                                id="category_name_<?= $c['id'] ?>"
                                style="margin-top: 6px; padding: 8px; border: 1px solid #ccc; border-radius: 6px;">
                            <button
                                type="submit"
                                name="update_category"
                                style="margin-left: 6px; background: #0073e6; color: white;
                                    padding: 8px 15px; border: 0; border-radius: 6px; cursor: pointer;"> Update </button>
                        </div>
                    </form>
                </div>

                <!-- delete + force delete -->
                <div style="margin-right: 20px; display: flex; flex-direction: row; gap: 8px;">

                    <!-- soft delete -->
                    <form method="POST">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="category_id" value="<?= htmlspecialchars($c['id']) ?>">
                        <button
                            type="submit"
                            name="delete_category"
                            style="background: #d9534f; color: white; padding: 10px 15px; border: 0;
                                border-radius: 6px; cursor: pointer;">
                            Delete
                        </button>
                    </form>

                    <!-- force delete option -->
                    <form method="POST">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="category_id" value="<?= htmlspecialchars($c['id']) ?>">
                        <input type="hidden" name="force_delete" value="1">
                        <button
                            type="submit"
                            name="force_delete_category"
                            style="background: #b52b27; color: white; padding: 10px 15px; border: 0;
                                border-radius: 6px; cursor: pointer;">
                            Force Delete
                        </button>
                    </form>

                </div>

            </li>
        <?php endforeach; ?>
        </ul>

    </div>

    <!-- right column: view & add category form -->
    <div style="flex: 0 0 320px; padding-left: 20px; border-left: 1px solid #ccc;">

        <!-- warning message -->
        <?php if ($flash): ?>
            <div style="padding: 10px; margin-bottom: 12px; border-radius: 6px;
                        border:1px solid <?= stripos($flash, 'failed') !== false ? '#f5b7b7' : '#b7f0b7' ?>;
                        background: <?= stripos($flash, 'failed') !== false ? '#ffe6e6' : '#e8ffe8' ?>;">
                        <?= htmlspecialchars($flash) ?> 
            </div>
        <?php endif; ?>
        
        <!-- view category by id -->
        <h3>Get Category by ID</h3>
        <form method="POST" style="margin-bottom: 20px;">
            <div style="display: flex; gap: 10px;">
                <input
                    type="number"
                    name="category_id"
                    placeholder="Enter category ID"
                    style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 6px;" required>
                <button type="submit"
                        name="view_category"
                        style="background: #0073e6; color: white; padding: 10px; border: 0; border-radius: 6px; cursor: pointer;">
                    Get Category
                </button>
            </div>
        </form>

        <?php if ($viewCategory): ?>
            <h3>Category Details</h3>
            <div style="background: #f7f7f7; padding: 10px; margin-bottom: 8px; border-radius: 6px;">
                <p><strong>Name:</strong> <?= htmlspecialchars($viewCategory['name']) ?></p>
                <p><strong>Created:</strong> <?= htmlspecialchars($viewCategory['created_at']) ?><br>
                    <strong>Updated:</strong> <?= htmlspecialchars($viewCategory['updated_at']) ?></p>
            </div>
        <?php endif; ?>

        <hr style="border: 0; border-top: 1px solid #ddd; margin: 25px 0;">

        <!-- add new category form -->
        <h3>Add New Category</h3>
        <form method="POST">
            <div style="display: flex; gap: 10px;">
                <input 
                    type="text" 
                    name="category_name" placeholder="Enter category name" 
                    style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 6px;">
                <button 
                    type="submit" 
                    name="create_category"
                    style="background: #0073e6; color: white; padding: 10px; border: 0; border-radius: 6px; cursor: pointer;">
                    Add Category
                </button>
            </div>
        </form>

        <hr style="border: 0; border-top: 1px solid #ddd; margin: 25px 0;">
        
        <!-- restore + force delete -->
        <h3>Deleted Categories</h3>
        <ul style="list-style-type: none; padding-left: 0;">
        <?php foreach ($deletedCategories as $c): ?>
            <li style="background:#fff3cd; padding:10px; margin-bottom:8px; border-radius:6px;">

                <strong><?= htmlspecialchars($c['name']) ?></strong> (deleted) <br>
                <strong>ID:</strong> <?= htmlspecialchars($c['id']) ?><br>
                <strong>Deleted At:</strong> <?= htmlspecialchars($c['deleted_at']) ?><br>

                <div style="display: flex; gap: 10px; margin-top: 12px;">

                    <!-- restore -->
                    <form method="POST" style="margin-top:10px;">
                        <input type="hidden" name="category_id" value="<?= htmlspecialchars($c['id']) ?>">
                        <button type="submit" name="restore_category"
                                style="background:#28a745; color:white; padding:6px 12px; border-radius:6px; cursor: pointer;">
                            Restore
                        </button>
                    </form>

                    <form method="POST" style="margin-top:10px;">
                        <input type="hidden" name="category_id" value="<?= htmlspecialchars($c['id']) ?>">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" name="force_delete_category"
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

<?php require_once "footer.php"; ?>