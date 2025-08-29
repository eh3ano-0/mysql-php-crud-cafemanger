<?php
include ("db.php");

// پیام‌ها
$add_message = "";
$edit_message = "";
$delete_message = "";
$status = "";

// بررسی ارسال فرم برای افزودن شخص
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == "add_person") {
        $id = $_POST['id'];
        $FirstName = $_POST['name'];
        $LastName = $_POST['lastname'];
        $PhoneNumber = $_POST['number'];
        $Addres = $_POST['addres'];
        $date = $_POST['datereg'];

        try {
            $stmt = $conn->prepare("CALL add_person(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $id, $FirstName, $LastName, $PhoneNumber, $Addres, $date);
            if ($stmt->execute()) {
                $add_message = "اطلاعات شخص با موفقیت ذخیره شد.";
                $status = "success";
            } else {
                throw new Exception("خطا در ذخیره اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $add_message = $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "edit_person") {
        $id = $_POST['id'];
        $FirstName = $_POST['name'];
        $LastName = $_POST['lastname'];
        $PhoneNumber = $_POST['number'];
        $Addres = $_POST['addres'];
        $date = $_POST['datereg'];

        try {
            $stmt = $conn->prepare("CALL edit_person(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $id, $FirstName, $LastName, $PhoneNumber, $Addres, $date);
            if ($stmt->execute()) {
                $edit_message = "اطلاعات شخص با موفقیت ویرایش شد.";
                $status = "success";
            } else {
                throw new Exception("خطا در ویرایش اطلاعات: " . $conn->error);
            }
        } catch (Exception $e) {
            $edit_message = $e->getMessage();
            $status = "error";
        }
    } elseif ($_POST['action'] == "delete_person") {
        $id = strval($_POST['id']);

        try {
            $stmt = $conn->prepare("CALL delete_person(?)");
            $stmt->bind_param("s", $id);
            if ($stmt->execute()) {
                $delete_message = "اطلاعات شخص با موفقیت حذف شد.";
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

// دریافت اطلاعات شخص برای نمایش از پروسیجر
$persons = [];
$sql = "CALL get_person()";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $persons[] = $row;
    }
}
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


    <h2 class="text-center mb-4">اطلاعات اشخاص</h2>        
    <!-- فرم اطلاعات شخص-->
    <form method="POST" action="">
        <input type="hidden" name="action" value="add_person">
        <div class="mb-3">
            <label for="id" class="form-label">شناسه</label>
            <input type="text" class="form-control" id="id" name="id" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="name" class="form-label">نام</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="lastname" class="form-label">نام خانوادگی</label>
                <input type="text" class="form-control" id="lastname" name="lastname" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="number" class="form-label">شماره تلفن</label>
                <input type="text" class="form-control" id="number" name="number" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="addres" class="form-label">آدرس</label>
                <input type="text" class="form-control" id="addres" name="addres" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="datereg" class="form-label">تاریخ ثبت نام</label>
                <input type="Date" class="form-control" id="datereg" name="datereg" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">ثبت</button>
    </form>

    <!-- جدول اطلاعات -->
    <h3 class="mt-4">داده‌های اشخاص</h3>
    <?php if (count($persons) > 0): ?>
        <table class="table table-bordered mt-4">
            <thead class="table-dark">
                <tr>
                    <th>شناسه</th>
                    <th>نام</th>
                    <th>نام خانوادگی</th>
                    <th>شماره تلفن</th>
                    <th>آدرس</th>
                    <th>تاریخ ثبت نام</th>
                    <th>ویرایش</th>
                    <th>حذف</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($persons as $person): ?>
                    <tr>
                        <td><?php echo $person['personID']; ?></td>
                        <td><?php echo htmlspecialchars($person['name']); ?></td>
                        <td><?php echo htmlspecialchars($person['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($person['number']); ?></td>
                        <td><?php echo htmlspecialchars($person['addres']); ?></td>
                        <td><?php echo htmlspecialchars($person['datereg']); ?></td>
                        <td>
                            <button class="icon-btn edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($person)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="delete_person">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars(string: $person['personID']); ?>">
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
        <input type="hidden" name="action" value="edit_person">
        <input type="hidden" name="id" id="edit-id">

        <input type="text" id="edit-FirstName" name="name" placeholder="نام" required>
        <input type="text" id="edit-LastName" name="lastname" placeholder="نام خانوادگی" required>
        <input type="text" id="edit-PhoneNumber" name="number" placeholder="شماره تلفن" required>
        <input type="text" id="edit-addres" name="addres" placeholder="آدرس" required>
        <input type="date" id="edit-datereg" name="datereg" placeholder="تاریخ ثبت نام" required>

        <button type="submit">ذخیره تغییرات</button>
        <button type="button" class="cancel-btn" onclick="closeEditModal()">لغو</button>
    </form>
</div>

<script>
    function openEditModal(person) {
            document.getElementById('edit-id').value = person.personID;
            document.getElementById('edit-FirstName').value = person.name;
            document.getElementById('edit-LastName').value = person.lastname;
            document.getElementById('edit-PhoneNumber').value = person.number;
            document.getElementById('edit-addres').value = person.addres;
            document.getElementById('edit-datereg').value = person.datereg;

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
