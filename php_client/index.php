<?php
require_once 'header.php';

// --- Get filters from query ---
$category_id = $_GET['category_id'] ?? '';
$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);
$per_page = 10;

// --- Load categories ---
$categoriesRes = callAPI("GET", "$apiBase/categories/", null);
$categories = $categoriesRes['json']['categories'] ?? [];

// --- Load products ---
$query = http_build_query([
        "category_id" => $category_id,
        "search" => $search,
        "page" => $page,
        "per_page" => $per_page
]);
$productsRes = callAPI("GET", "$apiBase/products/?$query", null);
$products = $productsRes['json']['products'] ?? [];
$total = $productsRes['json']['total'] ?? 0;
$total_pages = ceil($total / $per_page);
$productsToShow = array_slice($products, 0, 5); // Only show top 5 products in the table

// --- Prepare data for charts ---
$categoryLabels = [];
$categoryProductCounts = [];
$categoryStockTotals = [];

foreach ($categories as $cat) {
        $categoryLabels[] = addslashes($cat['name']);
        $productCount = 0;
        $stockTotal = 0;
        foreach ($products as $p) {
                if (($p['category']['id'] ?? null) == $cat['id']) {
                        $productCount++;
                        $stockTotal += $p['stock'];
                }
        }
        $categoryProductCounts[] = $productCount;
        $categoryStockTotals[] = $stockTotal;
}
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div style="display: flex; max-width: 1200px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); font-family: 'Segoe UI', Roboto, Arial, sans-serif; gap: 20px;">

        <!-- Left column: Filters & Categories -->
        <div style="flex:1; padding-right: 20px;">
                <!-- Filter Form -->
                <form method="GET" style="margin-bottom: 20px; background:#f9f9f9; padding:15px; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <h3 style="margin-bottom:15px;">Filter Products</h3>
                        <input type="text" name="search" placeholder="Search product..." value="<?= htmlspecialchars($search) ?>"
                                style="width:100%; padding:10px; margin-bottom:10px; border-radius:6px; border:1px solid #ccc; box-sizing:border-box">
                        <select name="category_id" style="width:100%; padding:10px; margin-bottom:10px; border-radius:6px; border:1px solid #ccc;">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                <?php endforeach; ?>
                        </select>
                        <style>
                                .filter-btn {
                                        background: #0073e6;
                                        color: white;
                                        padding: 10px 20px;
                                        border: none;
                                        border-radius: 6px;
                                        cursor: pointer;
                                        font-weight: normal;
                                        transition: all 0.2s ease;
                                }

                                .filter-btn:hover {
                                        background: #005bb5;
                                }

                                .filter-btn:active {
                                        font-weight: bold;
                                }
                        </style>
                        <button type="submit" class="filter-btn">Filter</button>
                </form>

                <!-- Categories List -->
                <h3 style="margin-bottom:10px;">Categories</h3>
                <ul style="list-style:none; padding:0;">
                        <?php foreach ($categories as $c): ?>
                                <li style="background:#f7f7f7; padding:10px; margin-bottom:8px; border-radius:6px;">
                                        <strong><?= htmlspecialchars($c['name']) ?></strong> (ID: <?= $c['id'] ?>)
                                </li>
                        <?php endforeach; ?>
                </ul>
        </div>

        <!-- Right column: Charts + Products Table -->
        <div style="flex:2; display:flex; flex-direction:column; gap:20px;">
                <!-- Products Table -->
                <table style="width:100%; border-collapse: collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <thead style="background:#0073e6; color:white; text-align:left;">
                                <tr>
                                        <th style="padding:12px;">Name</th>
                                        <th style="padding:12px;">Category</th>
                                        <th style="padding:12px;">Stock</th>
                                        <th style="padding:12px;">Last Update at</th>
                                </tr>
                        </thead>
                        <tbody>
                                <?php if (!empty($productsToShow)): ?>
                                        <?php foreach ($productsToShow as $p): ?>
                                                <tr style="border-bottom:1px solid #eee;">
                                                        <td style="padding:12px;"><?= htmlspecialchars($p['name']) ?></td>
                                                        <td style="padding:12px;"><?= htmlspecialchars($p['category']['name'] ?? '-') ?></td>
                                                        <td style="padding:12px;"><?= $p['stock'] ?></td>
                                                        <td style="padding:12px;"><?= isset($p['updated_at']) ? date('Y-m-d H:i', strtotime($p['updated_at'])) : '-' ?></td>
                                                </tr>
                                        <?php endforeach; ?>
                                <?php else: ?>
                                        <tr>
                                                <td colspan="3" style="padding:20px; text-align:center; color:#888;">No products found</td>
                                        </tr>
                                <?php endif; ?>
                        </tbody>
                </table>

                <!-- Pagination -->
                <div style="margin-top:20px; text-align:center;">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                        style="display:inline-block; padding:8px 12px; margin:0 3px; border-radius:6px;
                        background: <?= $i == $page ? '#0073e6' : '#f0f0f0' ?>;
                        color: <?= $i == $page ? '#fff' : '#333' ?>; text-decoration:none;">
                                        <?= $i ?>
                                </a>
                        <?php endfor; ?>
                </div>

                <!-- Charts -->
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                        <!-- Bar chart -->
                        <div style="flex:1; min-width:250px; background:#fff; padding:15px; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <h4 style="text-align:center; margin-bottom:10px;">Categories Distribution</h4>
                                <canvas id="productsChart" height="200"></canvas>
                        </div>

                        <!-- Pie chart -->
                        <div style="flex:1; min-width:250px; background:#fff; padding:15px; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <h4 style="text-align:center; margin-bottom:10px;">Product Distribution by Categories</h4>
                                <canvas id="categoriesChart" height="200"></canvas>
                        </div>
                </div>
        </div>
</div>

<!-- Chart.js Scripts -->
<script>
        // Bar chart: Number of products per category
        const ctxCategoriesBar = document.getElementById('productsChart').getContext('2d');
        new Chart(ctxCategoriesBar, {
                type: 'bar',
                data: {
                        labels: [<?= implode(',', array_map(fn($n) => "'$n'", $categoryLabels)) ?>],
                        datasets: [{
                                label: 'Number of Products',
                                data: [<?= implode(',', $categoryProductCounts) ?>],
                                backgroundColor: 'rgba(0,115,230,0.6)',
                                borderColor: 'rgba(0,115,230,1)',
                                borderWidth: 1
                        }]
                },
                options: {
                        responsive: true,
                        plugins: {
                                legend: {
                                        display: false
                                }
                        },
                        scales: {
                                y: {
                                        beginAtZero: true,
                                        precision: 0
                                }
                        }
                }
        });

        // Pie chart: Stock distribution per category
        const ctxCategoriesPie = document.getElementById('categoriesChart').getContext('2d');
        new Chart(ctxCategoriesPie, {
                type: 'pie',
                data: {
                        labels: [<?= implode(',', array_map(fn($n) => "'$n'", $categoryLabels)) ?>],
                        datasets: [{
                                data: [<?= implode(',', $categoryStockTotals) ?>],
                                backgroundColor: ['#0073e6', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#20c997', '#6610f2']
                        }]
                },
                options: {
                        responsive: true,
                        plugins: {
                                legend: {
                                        position: 'bottom'
                                }
                        }
                }
        });
</script>

<?php require_once "footer.php"; ?>