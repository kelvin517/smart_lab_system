<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main id="main" class="main">
  <div class="pagetitle">
    <h1>Inventory</h1>
    <a href="add_inventory.php" class="btn btn-primary btn-sm">+ Add Item</a>
  </div>

  <section class="section">
    <div class="card">
      <div class="card-body pt-3">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Item</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Unit</th>
              <th>Added On</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $items = mysqli_query($conn, "SELECT * FROM inventory ORDER BY added_on DESC");
            while ($row = mysqli_fetch_assoc($items)) {
              echo "<tr>
                <td>{$row['item_name']}</td>
                <td>{$row['category']}</td>
                <td>{$row['quantity']}</td>
                <td>{$row['unit']}</td>
                <td>{$row['added_on']}</td>
              </tr>";
            }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</main>