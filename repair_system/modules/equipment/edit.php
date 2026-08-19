<?php
/**
 * Edit Equipment
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';

check_role(['admin', 'inventory']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM equipments WHERE id = ?");
$stmt->execute([$id]);
$equipment = $stmt->fetch();

if (!$equipment) {
    set_flash('error', 'ไม่พบข้อมูลครุภัณฑ์ที่ต้องการแก้ไข');
    redirect('modules/equipment/index.php');
}

$page_title = 'แก้ไขข้อมูลครุภัณฑ์: ' . $equipment['code'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $serial_number = trim($_POST['serial_number'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $purchase_price = !empty($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : 0.00;
    $warranty_expire = !empty($_POST['warranty_expire']) ? $_POST['warranty_expire'] : null;
    $status = $_POST['status'] ?? 'available';
    $note = trim($_POST['note'] ?? '');

    if (empty($code) || empty($name)) {
        $error = 'กรุณาระบุรหัสครุภัณฑ์และชื่อครุภัณฑ์';
    } else {
        // Check duplicate code on other records
        $stmt = $pdo->prepare("SELECT id FROM equipments WHERE code = ? AND id != ?");
        $stmt->execute([$code, $id]);
        if ($stmt->fetch()) {
            $error = 'รหัสครุภัณฑ์นี้ซ้ำกับรายการอื่นในระบบ';
        } else {
            $imageName = $equipment['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $targetDir = __DIR__ . '/../../uploads/equipments/';
                $uploaded = upload_file($_FILES['image'], $targetDir);
                if ($uploaded) {
                    $imageName = $uploaded;
                }
            }

            $stmt = $pdo->prepare("UPDATE equipments SET code = ?, name = ?, category_id = ?, serial_number = ?, location = ?, department = ?, purchase_date = ?, purchase_price = ?, warranty_expire = ?, status = ?, image = ?, note = ? WHERE id = ?");
            if ($stmt->execute([$code, $name, $category_id, $serial_number, $location, $department, $purchase_date, $purchase_price, $warranty_expire, $status, $imageName, $note, $id])) {
                set_flash('success', 'อัปเดตข้อมูลครุภัณฑ์เรียบร้อยแล้ว');
                redirect('modules/equipment/view.php?id=' . $id);
            } else {
                $error = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล';
            }
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="content-body">
        <div class="mb-4">
            <a href="<?= base_url('modules/equipment/view.php?id=' . $equipment['id']) ?>" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="fas fa-arrow-left me-1"></i> กลับหน้ารายละเอียดครุภัณฑ์
            </a>
            <h4 class="fw-bold mb-1 text-dark">แก้ไขข้อมูลครุภัณฑ์: <?= htmlspecialchars($equipment['code']) ?></h4>
            <p class="text-muted mb-0"><?= htmlspecialchars($equipment['name']) ?></p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card-modern p-4">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark">รหัสครุภัณฑ์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="code" value="<?= htmlspecialchars($equipment['code']) ?>" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-medium text-dark">ชื่อครุภัณฑ์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($equipment['name']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-medium text-dark mb-0">หมวดหมู่ครุภัณฑ์</label>
                            <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'inventory'])): ?>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus-circle me-1"></i>เพิ่มหมวดหมู่
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="input-group">
                            <select class="form-select" name="category_id" id="categorySelect">
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($equipment['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'inventory'])): ?>
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal" title="เพิ่มหมวดหมู่ใหม่">
                                <i class="fas fa-plus"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark">Serial Number (หมายเลขเครื่อง)</label>
                        <input type="text" class="form-control" name="serial_number" value="<?= htmlspecialchars($equipment['serial_number'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark">สถานะครุภัณฑ์</label>
                        <select class="form-select" name="status">
                            <option value="available" <?= ($equipment['status'] === 'available') ? 'selected' : '' ?>>พร้อมใช้งาน</option>
                            <option value="repairing" <?= ($equipment['status'] === 'repairing') ? 'selected' : '' ?>>กำลังส่งซ่อม</option>
                            <option value="damaged" <?= ($equipment['status'] === 'damaged') ? 'selected' : '' ?>>ชำรุด</option>
                            <option value="disposed" <?= ($equipment['status'] === 'disposed') ? 'selected' : '' ?>>แทงจำหน่าย</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark">สถานที่ตั้ง / ห้องติดตั้ง</label>
                        <input type="text" class="form-control" name="location" value="<?= htmlspecialchars($equipment['location'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark">แผนก / หน่วยงานที่รับผิดชอบ</label>
                        <input type="text" class="form-control" name="department" value="<?= htmlspecialchars($equipment['department'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark">วันที่ตรวจรับ / จัดซื้อ</label>
                        <input type="date" class="form-control" name="purchase_date" value="<?= htmlspecialchars($equipment['purchase_date'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark">ราคาจัดซื้อ (บาท)</label>
                        <input type="number" step="0.01" class="form-control" name="purchase_price" value="<?= htmlspecialchars($equipment['purchase_price'] ?? '0.00') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-medium text-dark">วันหมดอายุประกัน</label>
                        <input type="date" class="form-control" name="warranty_expire" value="<?= htmlspecialchars($equipment['warranty_expire'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark">เปลี่ยนรูปภาพครุภัณฑ์</label>
                        <input type="file" class="form-control image-preview-input" name="image" accept="image/*" data-preview-target="previewEquipmentImg">
                        <div class="mt-2 text-center">
                            <?php if (!empty($equipment['image'])): ?>
                                <img id="previewEquipmentImg" src="<?= base_url('uploads/equipments/' . $equipment['image']) ?>" alt="รูปครุภัณฑ์" class="img-thumbnail rounded" style="max-height: 140px;">
                            <?php else: ?>
                                <img id="previewEquipmentImg" src="#" alt="ตัวอย่างภาพ" class="d-none img-thumbnail rounded" style="max-height: 140px;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-medium text-dark">หมายเหตุ / ข้อมูลเพิ่มเติม</label>
                        <textarea class="form-control" name="note" rows="4"><?= htmlspecialchars($equipment['note'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-end">
                    <a href="<?= base_url('modules/equipment/view.php?id=' . $equipment['id']) ?>" class="btn btn-secondary px-4 me-2">ยกเลิก</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Manage & Add Category (Admin & Inventory) -->
<?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'inventory'])): ?>
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-folder-tree text-primary me-2"></i>จัดการหมวดหมู่ครุภัณฑ์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-fill bg-light px-3 pt-2" id="categoryTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="add-tab" data-bs-toggle="tab" data-bs-target="#tabAddCat" type="button" role="tab">
                            <i class="fas fa-plus-circle me-1 text-primary"></i> เพิ่มหมวดหมู่ใหม่
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="list-tab" data-bs-toggle="tab" data-bs-target="#tabListCat" type="button" role="tab" onclick="loadCategoryList()">
                            <i class="fas fa-list me-1 text-secondary"></i> รายการหมวดหมู่ทั้งหมด
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4" id="categoryTabContent">
                    <!-- Tab 1: Add Category -->
                    <div class="tab-pane fade show active" id="tabAddCat" role="tabpanel">
                        <form id="addCategoryForm">
                            <div id="categoryModalAlert" class="alert alert-danger d-none py-2 px-3 small"></div>
                            <div class="mb-3">
                                <label class="form-label fw-medium text-dark">ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="newCategoryName" name="name" placeholder="เช่น อุปกรณ์ไอทีและเน็ตเวิร์ก" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium text-dark">คำอธิบายเพิ่มเติม</label>
                                <textarea class="form-control" id="newCategoryDescription" name="description" rows="3" placeholder="ระบุรายละเอียดหรือลักษณะของหมวดหมู่นี้ (ถ้ามี)"></textarea>
                            </div>
                            <div class="text-end pt-2 border-top">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">ปิด</button>
                                <button type="submit" class="btn btn-primary" id="btnSaveCategory"><i class="fas fa-save me-1"></i> บันทึกหมวดหมู่</button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Category List & Delete (Admin Only for deletion) -->
                    <div class="tab-pane fade" id="tabListCat" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small">
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <i class="fas fa-info-circle text-primary me-1"></i>คุณสามารถลบหมวดหมู่ที่ไม่ต้องการได้ (สิทธิ์ Admin)
                                <?php else: ?>
                                    <i class="fas fa-lock text-warning me-1"></i>เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถลบหมวดหมู่ได้
                                <?php endif; ?>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadCategoryList()" title="โหลดข้อมูลใหม่">
                                <i class="fas fa-sync-alt"></i> รีเฟรช
                            </button>
                        </div>
                        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" id="categoryTable">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>ชื่อหมวดหมู่</th>
                                        <th>คำอธิบาย</th>
                                        <th class="text-center">จำนวนครุภัณฑ์</th>
                                        <th class="text-end">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody id="categoryListTbody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="fas fa-spinner fa-spin me-1"></i> กำลังโหลดข้อมูล...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const AJAX_CAT_URL = '<?= base_url('modules/equipment/ajax_category.php') ?>';
const IS_ADMIN = <?= ($_SESSION['user_role'] === 'admin') ? 'true' : 'false' ?>;

// Add Category Form Handler
document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const nameInput = document.getElementById('newCategoryName');
    const descInput = document.getElementById('newCategoryDescription');
    const alertBox = document.getElementById('categoryModalAlert');
    const saveBtn = document.getElementById('btnSaveCategory');
    const select = document.getElementById('categorySelect');

    const nameVal = nameInput.value.trim();
    const descVal = descInput.value.trim();

    if (!nameVal) {
        alertBox.textContent = 'กรุณากรอกชื่อหมวดหมู่';
        alertBox.classList.remove('d-none');
        return;
    }

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> กำลังบันทึก...';
    alertBox.classList.add('d-none');

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('name', nameVal);
    formData.append('description', descVal);

    fetch(AJAX_CAT_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> บันทึกหมวดหมู่';

        if (data.success) {
            let optionExists = false;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].value == data.id) {
                    optionExists = true;
                    select.selectedIndex = i;
                    break;
                }
            }

            if (!optionExists) {
                const opt = new Option(data.name, data.id, true, true);
                select.add(opt);
            }

            const modalElem = document.getElementById('addCategoryModal');
            const modalInstance = bootstrap.Modal.getInstance(modalElem) || new bootstrap.Modal(modalElem);
            modalInstance.hide();

            document.getElementById('addCategoryForm').reset();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }
        } else {
            alertBox.textContent = data.message || 'เกิดข้อผิดพลาดในการบันทึก';
            alertBox.classList.remove('d-none');
        }
    })
    .catch(err => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> บันทึกหมวดหมู่';
        alertBox.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์';
        alertBox.classList.remove('d-none');
    });
});

// Load Category List for Management / Deletion
function loadCategoryList() {
    const tbody = document.getElementById('categoryListTbody');
    if (!tbody) return;
    
    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin me-1"></i> กำลังโหลดรายการ...</td></tr>`;

    fetch(`${AJAX_CAT_URL}?action=list`)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.categories) {
            if (data.categories.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">ยังไม่มีหมวดหมู่ในระบบ</td></tr>`;
                return;
            }

            let html = '';
            data.categories.forEach(cat => {
                const desc = cat.description ? `<small class="text-muted">${escapeHtml(cat.description)}</small>` : '<span class="text-muted small">-</span>';
                const countBadge = `<span class="badge bg-light text-dark border">${cat.eq_count} รายการ</span>`;
                
                let actionBtn = '<span class="text-muted small"><i class="fas fa-lock me-1"></i>Admin</span>';
                if (IS_ADMIN) {
                    actionBtn = `<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${cat.id}, '${escapeHtml(cat.name).replace(/'/g, "\\'")}', ${cat.eq_count})" title="ลบหมวดหมู่นี้">
                                    <i class="fas fa-trash-alt me-1"></i> ลบ
                                 </button>`;
                }

                html += `<tr id="cat-row-${cat.id}">
                            <td class="fw-semibold text-dark">${escapeHtml(cat.name)}</td>
                            <td>${desc}</td>
                            <td class="text-center">${countBadge}</td>
                            <td class="text-end">${actionBtn}</td>
                         </tr>`;
            });
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">${data.message || 'ไม่สามารถโหลดรายการได้'}</td></tr>`;
        }
    })
    .catch(() => {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-3">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
    });
}

// Delete Category (Admin Only)
function deleteCategory(catId, catName, eqCount) {
    if (!IS_ADMIN) {
        alert('สิทธิ์เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถลบหมวดหมู่ได้');
        return;
    }

    let warningText = `คุณต้องการลบหมวดหมู่ "${catName}" หรือไม่?`;
    if (eqCount > 0) {
        warningText = `หมวดหมู่นี้มีครุภัณฑ์ใช้งานอยู่ ${eqCount} รายการ\nหากลบ หมวดหมู่ของครุภัณฑ์เหล่านั้นจะถูกปรับเป็น "ทั่วไป/ไม่ระบุ"`;
    }

    const performDelete = () => {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', catId);

        fetch(AJAX_CAT_URL, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Remove row from table
                const row = document.getElementById(`cat-row-${catId}`);
                if (row) row.remove();

                // Remove from select dropdown
                const select = document.getElementById('categorySelect');
                if (select) {
                    for (let i = 0; i < select.options.length; i++) {
                        if (select.options[i].value == catId) {
                            if (select.selectedIndex === i) {
                                select.selectedIndex = 0;
                            }
                            select.remove(i);
                            break;
                        }
                    }
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบสำเร็จ!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
                } else {
                    alert(data.message);
                }
            }
        })
        .catch(() => {
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
        });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'ยืนยันการลบหมวดหมู่?',
            text: warningText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'ใช่, ลบเลย',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                performDelete();
            }
        });
    } else {
        if (confirm(warningText)) {
            performDelete();
        }
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
