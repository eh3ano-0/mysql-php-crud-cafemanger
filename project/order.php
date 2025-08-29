<?php
include ("db.php");

// پیام‌ها
$add_message = "";
$edit_message = "";
$delete_message = "";
$status = "";


$customers = [];
$sql = "CALL get_customer_order()";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}


$employes = [];
$sql = "CALL get_employe_order()";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employes[] = $row;
    }
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}

$products = [];
$sql = "CALL get_product_order()";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}


// بررسی ارسال فرم برای افزودن سفارش
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == "add_order") {
        $datetime = $_POST['datetime'];
        $statuss = $_POST['statuss'];
        $peymentmet = $_POST['peymentmet'];
        $cusid = $_POST['cusid'];
        $empid = $_POST['empid'];
        $selected_products = $_POST['products'] ?? []; // سفارش انتخاب شده


        $conn->begin_transaction(); // شروع تراکنش
        try {
            // افزودن سفارش به جدول order
            $stmt = $conn->prepare("INSERT INTO orderr (datetime, status, peymethod,cusid,empid) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $datetime, $statuss, $peymentmet, $cusid, $empid);
            if ($stmt->execute() === TRUE) {
                $order_id = $conn->insert_id; // دریافت ID سفارش
                
                // افزودن محصولات به جدول include
                foreach ($selected_products as $product_id) {
                    $stmt = $conn->prepare("CALL add_product_to_order(?, ?)");
                    $stmt->bind_param("ii", $order_id, $product_id);
                    if (!$stmt->execute()) {
                        throw new Exception("خطا در افزودن محصول به سفارش: " . $conn->error);
                    }
                }
                
                
                $add_message = "اطلاعات سفارش با موفقیت ذخیره شد.";
                $status = "success";
                $conn->commit(); // تایید تراکنش
            } else {
                throw new Exception("خطا در ذخیره اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback(); // بازگردانی تراکنش در صورت خطا
            $add_message = $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "edit_order") {
        $id = $_POST['id'];
        $datetime = $_POST['datetime'];
        $statuss = $_POST['statuss'];
        $peymentmet = $_POST['peymentmet'];
        $cusid = $_POST['cusid'];
        $empid = $_POST['empid'];
        $selected_products = $_POST['products'] ?? [];


        $conn->begin_transaction(); // شروع تراکنش
        try {
            // ویرایش سفارش
            $stmt = $conn->prepare("CALL edit_order( ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $id, $datetime, $statuss, $peymentmet, $cusid, $empid);
            if ($stmt->execute() === TRUE) {
                // حذف محصولات قبلی از جدول include
                $stmt = $conn->prepare("DELETE FROM order_detail WHERE orderrID = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                // افزودن محصولات جدید به جدول include
                foreach ($selected_products as $product_id) {
                    $stmt = $conn->prepare("CALL add_product_to_order_detail(?, ?)");
                    $stmt->bind_param("ii", $id, $product_id);
                    if (!$stmt->execute()) {
                        throw new Exception("خطا در افزودن محصولات به سفارش: " . $conn->error);
                    }
                }

                $edit_message = "اطلاعات سفارش با موفقیت ویرایش شد.";
                $status = "success";
                $conn->commit(); // تایید تراکنش
            } else {
                throw new Exception("خطا در ویرایش اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback(); // بازگردانی تراکنش در صورت خطا
            $edit_message = $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "delete_order") {
        $id = intval($_POST['id']);

        try {
            // ابتدا رکوردهای مربوطه را از جدول deteil حذف کنید
            $stmt = $conn->prepare("CALL delete_orderdetail_withorderid(?)");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // سپس رکورد سفارش را از جدول order حذف کنید
            $stmt = $conn->prepare("CALL delete_order_byid(?)");
            $stmt->bind_param("i", $id);
            if ($stmt->execute() === TRUE) {
                $delete_message = "اطلاعات سفارش با موفقیت حذف شد.";
                $status = "success";
            } else {
                throw new Exception("خطا در حذف اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $delete_message = $e->getMessage();
            $status = "error";
        }
    }
}
// اجرای پروسیجر برای دریافت سفارش‌ها
$sql = "CALL get_order_withdetail()";
$result = $conn->query($sql);
$orders = [];
if ($result) {
    // پردازش نتایج اول
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    // از next_result برای حرکت به کوئری بعدی استفاده کنید
    while ($conn->next_result()) {;}  // پردازش باقی‌مانده نتایج
}



$conn->close();
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت کافه</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> 
<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Arial', sans-serif;
    direction: rtl;
}

body {
    background: linear-gradient(135deg, #74ebd5, #acb6e5);
    display: flex;
    justify-content: center; /* افقی وسط‌چین */
    align-items: center; /* عمودی وسط‌چین */
    min-height: 100vh;
    margin: 0;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 150px;
    height: 100vh;
    background: #2c3e50;
    color: white;
    padding: 20px;
    transition: 0.3s;
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
}

.sidebar h3 {
    text-align: center;
    margin-bottom: 30px;
}

.sidebar ul {
    list-style: none;
}

.sidebar ul li {
    margin: 10px 0;
}

.sidebar ul li a:hover {
    padding-left: 15px;
    color: #f1c40f;
    transition: 0.3s ease;
}

.sidebar ul li a i {
    transition: transform 0.3s ease;
    margin-left: 10px; /* فاصله بین آیکن و متن */
    font-size: 1.2em; /* اندازه آیکن (اختیاری) */
}

.sidebar ul li a:hover i {
    transform: rotate(15deg);
}

.sidebar ul li a {
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: 0.3s;
}

.sidebar ul li a:hover {
    padding-left: 10px;
    color: #1abc9c;
}

.sidebar ul li a i {
    margin-right: 10px;
}

.container {
    margin-left: 400px;
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    background: linear-gradient(145deg, #6dd5ed, #2193b0);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    max-width: 900px;
    width: 100%;
    animation: fadeIn 1.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.container {
    background: linear-gradient(145deg, #6dd5ed, #2193b0);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2), 0 6px 6px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background: #1abc9c;
    border: none;
}

.btn-primary:hover {
    background: #16a085;
}
.icon-btn {
background: none;
border: none;
cursor: pointer;
padding: 8px;
border-radius: 50%;
transition: all 0.3s ease;
display: inline-flex;
justify-content: center;
align-items: center;
}

.icon-btn i {
    font-size: 1.2em;
    color: white;
    transition: color 0.3s ease;
}

/* دکمه ویرایش */
.edit-btn {
    background-color: #1abc9c; /* سبز */
}

.edit-btn:hover {
    background-color: #16a085;
    transform: scale(1.1);
}

/* دکمه حذف */
.delete-btn {
    background-color: #e74c3c; /* قرمز */
}

.delete-btn:hover {
    background-color: #c0392b;
    transform: scale(1.1);
}
/* استایل‌های بهبود‌یافته برای پاپ‌آپ */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    display: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal-overlay.active {
    display: block;
    opacity: 1;
}

.modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.8);
    background: linear-gradient(135deg, #74ebd5, #acb6e5);
    background-color:#6dd5ed;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.3);
    z-index: 1001;
    display: none;
    max-width: 400px;
    width: 90%;
    animation: popIn 0.4s ease forwards;
}

.modal.active {
    display: block;
}

@keyframes popIn {
    0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 0;
    }
    100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
}

.modal h2 {
    margin-bottom: 20px;
    font-size: 20px;
    color: #333;
    text-align: center;
}

.modal form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.modal form input, 
.modal form button {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    outline: none;
}

.modal form button {
    background-color: #1abc9c;
    color: #fff;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.modal form button:hover {
    background-color: #16a085;
}

.modal form .cancel-btn {
    background-color: #e74c3c;
    color: white;
}

.modal form .cancel-btn:hover {
    background-color: #c0392b;
}

</style>
</head>
<body>

<div class="sidebar">
    <h3>فرم‌ها</h3>
    <ul>
        <li><a href="person.php"><i class="fas fa-user"></i> شخص</a></li>
        <li><a href="employe.php"><i class="fas fa-briefcase"></i> کارمند</a></li>
        <li><a href="customer.php"><i class="fas fa-user-tie"></i> مشتری</a></li>
        <li><a href="order.php"><i class="fas fa-shopping-cart"></i> سفارشات</a></li>
        <li><a href="product.php"><i class="fas fa-window-maximize"></i> محصول</a></li>
        <li><a href="main.html"><i class="fas fa-home"></i> خانه</a></li>
    </ul>
</div>


<div class="container mt-5">
    <!-- نمایش پیام‌ها -->
    <?php if (!empty($add_message)): ?>
        <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-danger'; ?>">
            <span class="alert-close" onclick="this.parentElement.style.display='none';">&times;</span>
            <?php echo $add_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($edit_message)): ?>
        <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-danger'; ?>">
            <span class="alert-close" onclick="this.parentElement.style.display='none';">&times;</span>
            <?php echo $edit_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($delete_message)): ?>
        <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-danger'; ?>">
            <span class="alert-close" onclick="this.parentElement.style.display='none';">&times;</span>
            <?php echo $delete_message; ?>
        </div>
    <?php endif; ?>


    <h2 class="text-center mb-4">اطلاعات سفارش</h2>        
    <!-- فرم اطلاعات سفارش-->
    <form method="POST" action="">
        <input type="hidden" name="action" value="add_order">
        <div class="mb-3">
            <label for="datetime" class="form-label">تاریخ</label>
            <input type="Date" class="form-control" id="datetime" name="datetime" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="statuss" class="form-label">وضعیت</label>
                <select class="form-control" name="statuss" id="statuss" required>
                    <option value="" disabled selected>وضعیت</option>
                    <option value="موفق">موفق</option>
                    <option value="ناموفق">ناموفق</option>
                    <option value="نامعلوم">نامعلوم</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label for="peymentmet" class="form-label">نحوه پرداخت</label>
                <select class="form-control" name="peymentmet" id="peymentmet" required>
                    <option value="" disabled selected>نحوه پرداخت</option>
                    <option value="اینترنتی">اینترنتی</option>
                    <option value="نقد">نقد</option>
                    <option value="درمحل">درمحل</option>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label for="cusid" class="form-label">شناسه مشتری</label>
            <select name="cusid" class="form-select" required>
                <option value="" disabled selected>انتخاب مشتری</option>
                <?php foreach ($customers as $customer): ?>
                <option value="<?php echo $customer['cusID']; ?>">
                    <?php echo "شناسه: ".$customer['cusID']. " - ".$customer['credit'] . " - " . $customer['datepur']." - ".$customer['purcount'] ; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="empid" class="form-label">شناسه کارمند</label>
            <select name="empid" class="form-select" required>
                <option value="" disabled selected>انتخاب کارمند</option>
                <?php foreach ($employes as $employe): ?>
                <option value="<?php echo $employe['empID']; ?>">
                    <?php echo "شناسه: ".$employe['empID']. " - ".$employe['position'] . " - " . $employe['salary']." - ".$employe['datehir'] . " - " . $employe['accnumber']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <label>محصولات:</label>
        <div>
            <?php foreach ($products as $product): ?>
                <label>
                    <input type="checkbox" name="products[]" value="<?php echo $product['productID']; ?>">
                    <?php echo $product['name'] . " (قیمت: " . $product['price'] . ")"; ?>
                </label>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-primary">ثبت</button>
    </form>

    <!-- جدول اطلاعات -->
    <h3 class="mt-4">داده‌های سفارش</h3>
    <?php if (count($orders) > 0): ?>
        <table class="table table-bordered mt-4">
            <thead class="table-dark">
                <tr>
                    <th>شناسه</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                    <th>روش پرداخت</th>
                    <th>شناسه مشتری</th>
                    <th>شناسه کارمند</th>
                    <th>محصولات</th>
                    <th>قیمت ها</th>
                    <th>ویرایش</th>
                    <th>حذف</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['orderID']; ?></td>
                        <td><?php echo htmlspecialchars($order['datetime']); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td><?php echo htmlspecialchars($order['peymethod']); ?></td>
                        <td><?php echo htmlspecialchars($order['cusid']); ?></td>
                        <td><?php echo htmlspecialchars($order['empid']); ?></td>
                        <td><?php echo htmlspecialchars($order['product_names']); ?></td>
                        <td><?php echo htmlspecialchars($order['product_prices']); ?></td>
                        
                        <td>
                            <button class="icon-btn edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="delete_order">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars(string: $order['orderID']); ?>">
                                <button class="icon-btn delete-btn" onclick="return confirm('آیا مطمئن هستید؟')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-danger">هیچ داده‌ای موجود نیست.</div>
    <?php endif; ?>
</div>

<!-- پاپ‌آپ ویرایش -->
<div class="modal-overlay" id="modal-overlay" onclick="closeEditModal()"></div>
<div class="modal" id="edit-modal">
    <h2>ویرایش اطلاعات</h2>
    <form method="POST" action="">
        <input type="hidden" name="action" value="edit_order">
        <input type="hidden" name="id" id="edit-id">

        <input type="date" id="edit-datetime" name="datetime" placeholder="تاریخ" required>
        
        <select class="form-control" name="statuss" id="edit-statuss" required>
            <option value="" disabled selected>وضعیت</option>
            <option value="موفق">موفق</option>
            <option value="ناموفق">ناموفق</option>
            <option value="نامعلوم">نامعلوم</option>
        </select>
    
        <select class="form-control" name="peymentmet" id="edit-peymentmet" required>
            <option value="" disabled selected>نحوه پرداخت</option>
            <option value="اینترنتی">اینترنتی</option>
            <option value="نقد">نقد</option>
            <option value="درمحل">درمحل</option>
        </select>

        <div class="mb-3">
            <select name="cusid" id="edit-cusid"  class="form-select" required>
                <option value="" disabled selected>انتخاب مشتری</option>
                <?php foreach ($customers as $customer): ?>
                <option value="<?php echo $customer['cusID']; ?>">
                    <?php echo "شناسه: ".$customer['cusID']. " - ".$customer['credit'] . " - " . $customer['datepur']." - ".$customer['purcount'] ; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <select name="empid" id="edit-empid" class="form-select" required>
                <option value="" disabled selected>انتخاب کارمند</option>
                <?php foreach ($employes as $employe): ?>
                <option value="<?php echo $employe['empID']; ?>">
                    <?php echo "شناسه: ".$employe['empID']. " - ".$employe['position'] . " - " . $employe['salary']." - ".$employe['datehir'] . " - " . $employe['accnumber']; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <label>محصولات:</label>
        <div>
            <?php foreach ($products as $product): ?>
                <label>
                    <input type="checkbox" class="edit-product" name="products[]" value="<?php echo $product['productID']; ?>">
                    <?php echo $product['name'] . " (قیمت: " . $product['price'] . ")"; ?>
                </label>
            <?php endforeach; ?>
        </div>

        <button type="submit">ذخیره تغییرات</button>
        <button type="button" class="cancel-btn" onclick="closeEditModal()">لغو</button>
    </form>
</div>

<script>
    function openEditModal(order) {
            document.getElementById('edit-id').value = order.orderID;
            document.getElementById('edit-datetime').value = order.datetime;
            document.getElementById('edit-statuss').value = order.statuss;
            document.getElementById('edit-peymentmet').value = order.peymentmet;
            document.getElementById('edit-cusid').value = order.cusid;
            document.getElementById('edit-empid').value = order.empid;

            // انتخاب محصولات مربوطه
            const selectedProducts = order.product_names.split(', ');
            document.querySelectorAll('.edit-product').forEach(input => {
                input.checked = selectedProducts.includes(input.nextSibling.nodeValue.trim());
            });


            document.getElementById('edit-modal').classList.add('active');
            document.getElementById('modal-overlay').classList.add('active');
        }
        function closeEditModal() {
            document.getElementById('edit-modal').classList.remove('active');
            document.getElementById('modal-overlay').classList.remove('active');
        }
</script>
</body>
</html>
