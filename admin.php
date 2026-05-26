<?php
// Author: Tasks 8, 9, 10 - Admin authentication, add/modify/delete products
// admin.php - full admin panel connected to database

session_start();
require 'db.php';

$error   = '';
$success = '';

// --- Handle admin login POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Task 8: Authenticate admin against DB
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$username || !$password) {
            $error = 'Please enter both username and password.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            // Note: passwords in DB are plain text (as per project data); use === directly
            if ($admin && $admin['password'] === $password) {
                $_SESSION['admin_id']       = $admin['Admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                header('Location: admin.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }

    // Security: all actions below require admin session
    if (!isset($_SESSION['admin_id'])) {
        header('Location: admin.php');
        exit;
    }

    // Task 9: Add new product
    if ($action === 'add_product') {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $stock       = (int)($_POST['stock'] ?? 0);
        $color       = trim($_POST['color'] ?? '');
        $category    = $_POST['category'] ?? 'Ring';
        $imageName   = '';

        if (!$name || $price <= 0 || $stock < 0) {
            $error = 'Name, price, and stock are required.';
        } else {
            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (!in_array($ext, $allowed)) {
                    $error = 'Image must be jpg, png, gif, or webp.';
                } else {
                    $imageName = 'Product_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/' . $imageName);
                }
            } else {
                $imageName = trim($_POST['image_name'] ?? 'placeholder.webp');
            }

            if (!$error) {
                $stmt = $pdo->prepare("INSERT INTO product (name, description, price, stock, image, color, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $stock, $imageName, $color, $category]);
                $success = "Product '$name' added successfully.";
            }
        }
    }

    // Task 10: Update product
    if ($action === 'update_product') {
        $pid         = (int)$_POST['product_id'];
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $stock       = (int)($_POST['stock'] ?? 0);
        $color       = trim($_POST['color'] ?? '');
        $category    = $_POST['category'] ?? 'Ring';

        if (!$name || $price <= 0) {
            $error = 'Name and price are required.';
        } else {
            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $imageName = 'Product_' . $pid . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/' . $imageName);
                $stmt = $pdo->prepare("UPDATE product SET name=?, description=?, price=?, stock=?, color=?, category=?, image=? WHERE product_id=?");
                $stmt->execute([$name, $description, $price, $stock, $color, $category, $imageName, $pid]);
            } else {
                $stmt = $pdo->prepare("UPDATE product SET name=?, description=?, price=?, stock=?, color=?, category=? WHERE product_id=?");
                $stmt->execute([$name, $description, $price, $stock, $color, $category, $pid]);
            }
            $success = "Product updated successfully.";
        }
    }

    // Task 10: Delete product
    if ($action === 'delete_product') {
        $pid = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = ?");
        $stmt->execute([$pid]);
        $success = 'Product deleted successfully.';
    }

    // Admin logout
    if ($action === 'logout') {
        unset($_SESSION['admin_id'], $_SESSION['admin_username']);
        header('Location: admin.php');
        exit;
    }
}

// --- Security: redirect if not logged in (for GET requests to admin panel) ---
$isLoggedIn = isset($_SESSION['admin_id']);

// --- Fetch products for admin table ---
$products = [];
$searchTerm = '';
if ($isLoggedIn) {
    $searchTerm = trim($_GET['search'] ?? '');
    if ($searchTerm) {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE name LIKE ? OR category LIKE ? ORDER BY product_id DESC");
        $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM product ORDER BY product_id DESC");
    }
    $products = $stmt->fetchAll();
}

// Fetch single product for edit
$editProduct = null;
if ($isLoggedIn && isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM product WHERE product_id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$categories = ['Ring', 'Necklace', 'Earrings', 'Bracelet'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selene Shop Admin</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav id="mainNav">
  <div class="nav-logo">SEL<span>EN</span>E</div>
  <div class="nav-links">
    <a class="nav-link" href="shop.php">Shop</a>
    <?php if ($isLoggedIn): ?>
      <span class="nav-link" style="color:var(--gold)">👤 <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="logout">
        <button class="nav-link auth-nav-btn" type="submit" style="background:none;border:none;cursor:pointer;">Logout</button>
      </form>
    <?php endif; ?>
  </div>
</nav>

<?php if (!$isLoggedIn): ?>
<!-- Task 8: Admin Login Form -->
<div class="auth-wrap">
  <div class="auth-card">
    <h2>Admin Panel</h2>
    <p>Sign in to manage your store</p>
    <?php if ($error): ?>
      <div class="field-error" style="display:block;margin-bottom:12px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form id="adminLoginForm" method="POST" novalidate>
      <input type="hidden" name="action" value="login">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input class="form-input" type="text" name="username" id="adminUser" placeholder="admin username" required>
        <div class="field-error" id="adminUserError"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" type="password" name="password" id="adminPass" placeholder="••••••••" required>
        <div class="field-error" id="adminPassError"></div>
      </div>
      <button class="btn btn-gold" type="submit" style="width:100%;justify-content:center;">Sign In →</button>
    </form>
  </div>
</div>

<script>
  // Task 13: JS validation for admin login
  document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
    let valid = true;
    const user = document.getElementById('adminUser');
    const pass = document.getElementById('adminPass');
    document.getElementById('adminUserError').textContent = '';
    document.getElementById('adminPassError').textContent = '';
    if (!user.value.trim()) { document.getElementById('adminUserError').textContent = 'Username is required.'; valid = false; }
    if (!pass.value.trim()) { document.getElementById('adminPassError').textContent = 'Password is required.'; valid = false; }
    if (!valid) e.preventDefault();
  });
</script>

<?php else: ?>
<!-- Task 9, 10: Admin Dashboard -->
<div class="admin-layout">
  <div class="admin-sidebar">
    <div class="admin-sidebar-title">Management</div>
    <a class="admin-nav-item <?= !isset($_GET['page']) || $_GET['page']==='products' ? 'active' : '' ?>" href="admin.php">📦 Products</a>
    <a class="admin-nav-item <?= isset($_GET['page']) && $_GET['page']==='add' ? 'active' : '' ?>" href="admin.php?page=add">➕ Add Product</a>
    <div class="admin-sidebar-title" style="margin-top:30px">Account</div>
    <form method="POST" style="margin:0">
      <input type="hidden" name="action" value="logout">
      <button class="admin-nav-item" type="submit" style="background:none;border:none;cursor:pointer;width:100%;text-align:left;">🚪 Logout</button>
    </form>
  </div>

  <div class="admin-content">

    <?php if ($success): ?>
      <div style="background:#f0fff4;border:1px solid #c6f6d5;color:#276749;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        ✓ <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div style="background:#fff5f5;border:1px solid #fed7d7;color:#c53030;padding:12px 16px;border-radius:8px;margin-bottom:20px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['page']) && $_GET['page'] === 'add'): ?>
    <!-- Task 9: Add Product Form -->
    <div class="admin-header"><h2>Add Product</h2></div>
    <form method="POST" enctype="multipart/form-data" id="addForm" novalidate style="max-width:580px">
      <input type="hidden" name="action" value="add_product">
      <div class="form-group">
        <label class="form-label">Product Name *</label>
        <input class="form-input" type="text" name="name" id="addName" required>
        <div class="field-error" id="addNameError"></div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-input" name="description" rows="3"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Price (SAR) *</label>
          <input class="form-input" type="number" name="price" id="addPrice" min="0" step="0.01" required>
          <div class="field-error" id="addPriceError"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Stock *</label>
          <input class="form-input" type="number" name="stock" id="addStock" min="0" required>
          <div class="field-error" id="addStockError"></div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Color</label>
          <input class="form-input" type="text" name="color" placeholder="e.g. Gold">
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select class="form-input" name="category">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>"><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Product Image</label>
        <div class="upload-area">
          <input type="file" name="image" id="addImage" accept="image/*" onchange="previewImage(this,'addPreview')">
          <div class="upload-icon">📸</div>
          <div class="upload-text">Click to upload an image</div>
          <img id="addPreview" class="upload-preview" alt="Preview" style="display:none;max-height:120px;margin-top:8px;">
        </div>
        <div style="margin-top:8px;font-size:12px;color:var(--muted)">Or enter filename:</div>
        <input class="form-input" type="text" name="image_name" placeholder="e.g. Product12.webp" style="margin-top:6px;">
      </div>
      <div style="display:flex;gap:12px;">
        <button class="btn btn-gold" type="submit">Add Product</button>
        <a class="btn btn-outline" href="admin.php">Cancel</a>
      </div>
    </form>

    <?php elseif ($editProduct): ?>
    <!-- Task 10: Edit Product Form -->
    <div class="admin-header"><h2>Edit Product</h2></div>
    <form method="POST" enctype="multipart/form-data" id="editForm" novalidate style="max-width:580px">
      <input type="hidden" name="action" value="update_product">
      <input type="hidden" name="product_id" value="<?= $editProduct['product_id'] ?>">
      <div class="form-group">
        <label class="form-label">Product Name *</label>
        <input class="form-input" type="text" name="name" value="<?= htmlspecialchars($editProduct['name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-input" name="description" rows="3"><?= htmlspecialchars($editProduct['description']) ?></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Price (SAR) *</label>
          <input class="form-input" type="number" name="price" value="<?= $editProduct['price'] ?>" min="0" step="0.01" required>
        </div>
        <div class="form-group">
          <label class="form-label">Stock *</label>
          <input class="form-input" type="number" name="stock" value="<?= $editProduct['stock'] ?>" min="0" required>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="form-group">
          <label class="form-label">Color</label>
          <input class="form-input" type="text" name="color" value="<?= htmlspecialchars($editProduct['color'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select class="form-input" name="category">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>" <?= $editProduct['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Current Image</label>
        <img src="<?= htmlspecialchars($editProduct['image']) ?>" alt="current" style="height:80px;border-radius:6px;display:block;margin-bottom:8px;">
        <label class="form-label">Upload New Image (optional)</label>
        <input type="file" name="image" accept="image/*" class="form-input">
      </div>
      <div style="display:flex;gap:12px;">
        <button class="btn btn-gold" type="submit">Save Changes</button>
        <a class="btn btn-outline" href="admin.php">Cancel</a>
      </div>
    </form>

    <?php else: ?>
    <!-- Task 10: Products list with search, edit, delete -->
    <div class="admin-header">
      <h2>Products</h2>
    </div>
    <form method="GET" style="margin-bottom:20px;display:flex;gap:12px;align-items:center;">
      <div class="search-bar" style="flex:1">
        <span class="search-icon">🔍</span>
        <input type="text" name="search" placeholder="Search by name or category…" value="<?= htmlspecialchars($searchTerm) ?>">
      </div>
      <button class="btn btn-outline" type="submit">Search</button>
      <?php if ($searchTerm): ?>
        <a class="btn btn-outline" href="admin.php">Clear</a>
      <?php endif; ?>
    </form>

    <div class="admin-table-wrap">
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="border-bottom:2px solid var(--border);text-align:left;">
            <th style="padding:10px 8px;">Image</th>
            <th style="padding:10px 8px;">Name</th>
            <th style="padding:10px 8px;">Category</th>
            <th style="padding:10px 8px;">Price</th>
            <th style="padding:10px 8px;">Stock</th>
            <th style="padding:10px 8px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:10px 8px;">
              <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                   style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
            </td>
            <td style="padding:10px 8px;"><?= htmlspecialchars($p['name']) ?></td>
            <td style="padding:10px 8px;"><?= htmlspecialchars($p['category'] ?? '') ?></td>
            <td style="padding:10px 8px;"><?= number_format($p['price'], 0) ?> SAR</td>
            <td style="padding:10px 8px;"><?= (int)$p['stock'] ?></td>
            <td style="padding:10px 8px;display:flex;gap:8px;">
              <!-- Task 10: Edit -->
              <a class="btn btn-outline btn-sm" href="admin.php?edit=<?= $p['product_id'] ?>">✏️ Edit</a>
              <!-- Task 10: Delete -->
              <form method="POST" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($p['name'])) ?>?')">
                <input type="hidden" name="action" value="delete_product">
                <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                <button class="btn btn-outline btn-sm" type="submit" style="color:#e53e3e;border-color:#e53e3e;">🗑 Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($products)): ?>
          <tr><td colspan="6" style="padding:24px;text-align:center;color:var(--muted);">No products found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
  // Task 13: JS validation for add product form
  const addForm = document.getElementById('addForm');
  if (addForm) {
    addForm.addEventListener('submit', function(e) {
      let valid = true;
      const name = document.getElementById('addName');
      const price = document.getElementById('addPrice');
      const stock = document.getElementById('addStock');
      document.getElementById('addNameError').textContent = '';
      document.getElementById('addPriceError').textContent = '';
      document.getElementById('addStockError').textContent = '';
      if (!name.value.trim()) { document.getElementById('addNameError').textContent = 'Product name is required.'; valid = false; }
      if (!price.value || parseFloat(price.value) <= 0) { document.getElementById('addPriceError').textContent = 'Enter a valid price.'; valid = false; }
      if (stock.value === '' || parseInt(stock.value) < 0) { document.getElementById('addStockError').textContent = 'Enter a valid stock quantity.'; valid = false; }
      if (!valid) e.preventDefault();
    });
  }

  function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
      reader.readAsDataURL(input.files[0]);
    }
  }
</script>
<?php endif; ?>

</body>
</html>
