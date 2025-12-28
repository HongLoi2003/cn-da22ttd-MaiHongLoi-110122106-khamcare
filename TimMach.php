<?php
// Kết nối cơ sở dữ liệu
$conn = new mysqli("localhost", "root", "", "khamcare_db");
$conn->set_charset("utf8");

// Tìm kiếm bác sĩ theo tên
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Lấy danh sách bác sĩ tim mạch
$sql = "
    SELECT d.*, u.full_name, u.email, u.phone, s.name AS specialty_name
    FROM doctors d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN specialties s ON d.specialty_id = s.id
    WHERE s.name = 'Tim Mạch' AND u.full_name LIKE ?
";
$stmt = $conn->prepare($sql);
$search_param = '%' . $search . '%';
$stmt->bind_param('s', $search_param);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
if ($result) { while($r = $result->fetch_assoc()) { $rows[] = $r; } }
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Khoa Tim Mạch - KhamCare</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="shared-styles.css">
  <style>
    body {
      background-color: #f4f9ff;
      font-family: "Segoe UI", sans-serif;
      color: #1e293b;
    }
    h2 {
      color: #0077b6;
      font-weight: 700;
    }

    /* ======= Thanh tìm kiếm ======= */
    .form-control {
      border: 2px solid #90e0ef;
      border-radius: 8px;
      box-shadow: none;
    }
    .form-control:focus {
      border-color: #0077b6;
      box-shadow: 0 0 5px rgba(0, 119, 182, 0.3);
    }

    .btn-primary {
      background-color: #0077b6;
      border: none;
    }
    .btn-primary:hover {
      background-color: #023e8a;
    }

    .btn-outline-primary {
      border-color: #0077b6;
      color: #0077b6;
    }
    .btn-outline-primary:hover {
      background-color: #0077b6;
      color: #fff;
    }

    /* ======= Thẻ bác sĩ ======= */
    .doctor-card {
      background: #ffffff;
      border-radius: 14px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      transition: all 0.3s;
      border-top: 4px solid #0077b6;
      display: flex;
      flex-direction: column;
      height: 100%;
      min-height: 480px;
    }
    .doctor-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 14px rgba(0, 119, 182, 0.15);
    }

    .doctor-img {
      border-radius: 12px 12px 0 0;
      height: 180px;
      object-fit: cover;
      width: 100%;
    }
    .doctor-card .p-3 {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      min-height: 260px;
    }
    .doctor-card h5,
    .doctor-card p,
    .doctor-card ul {
      margin-bottom: 0.5rem;
    }
    .doctor-card ul {
      min-height: 110px;
      margin-bottom: 1rem !important;
    }
    .btn-schedule {
      background-color: #0077b6;
      color: white;
      border-radius: 6px;
      border: none;
      transition: all 0.3s;
      margin-top: auto;
    }
    .btn-schedule:hover {
      background-color: #023e8a;
      transform: scale(1.03);
    }
    .star {
      color: #FFD700;
    }
    #suggestList .suggestion-item:hover {
      background-color: #e0f7ff;
    }

    /* ======= Nút quay lại ======= */
    .btn-link.text-primary {
      color: #0077b6 !important;
      text-decoration: none;
    }
    .btn-link.text-primary:hover {
      text-decoration: underline;
    }
    /* Đảm bảo các card đều chiều cao trong hàng */
    .row.equal-height {
      display: flex;
      flex-wrap: wrap;
      margin-right: -12px;
      margin-left: -12px;
    }
    .row.equal-height > [class^="col-"] {
      display: flex;
      flex-direction: column;
      margin-bottom: 24px;
    }
    @media (max-width: 767.98px) {
      .doctor-card {
        min-height: 0;
      }
      .doctor-card .p-3 {
        min-height: 0;
      }
      .doctor-card ul {
        min-height: 0;
      }
    }
  </style>
</head>
<body>

<div class="container py-5">
  <h2 class="text-center mb-4"><i class="fas fa-heartbeat me-2"></i> Khoa Tim Mạch</h2>

  <!-- Thanh tìm kiếm -->
  <form method="GET" class="mb-4 d-flex justify-content-center position-relative" autocomplete="off">
    <input id="searchDoctor" type="text" name="search" class="form-control w-50" placeholder="Tìm bác sĩ tim mạch..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
    <button type="submit" class="btn btn-primary ms-2"><i class="fas fa-search"></i> Tìm</button>
    <div id="suggestList" class="bg-white border rounded shadow position-absolute" style="top: 48px; left: 50%; transform: translateX(-50%); width: 50%; max-height: 220px; overflow-y:auto; display:none; z-index: 10;"></div>
  </form>

  <div class="row equal-height">
    <?php if (!empty($rows)): ?>
      <?php foreach($rows as $row): ?>
        <div class="col-md-4 d-flex">
          <div class="doctor-card w-100">
            <img src="<?php echo htmlspecialchars($row['image'] ?? 'https://via.placeholder.com/600x180?text=Doctor'); ?>" class="doctor-img" alt="<?php echo htmlspecialchars($row['full_name'] ?? 'Bác sĩ'); ?>">
            <div class="p-3 d-flex flex-column h-100">
              <div>
                <h5 class="text-primary mb-1"><?php echo htmlspecialchars($row['full_name'] ?? 'Bác sĩ'); ?></h5>
                <p class="mb-2"><i class="fas fa-stethoscope me-1"></i> <?php echo htmlspecialchars($row['specialty_name'] ?? 'Tim Mạch'); ?></p>
                <div class="d-flex align-items-center mb-2" title="Đánh giá">
                  <?php
                    $ratingVal = isset($row['rating']) ? (float)$row['rating'] : 0.0;
                    $full = (int)floor($ratingVal);
                    $rest = 5 - $full;
                    for ($i=0; $i<$full; $i++) echo '<i class="fas fa-star star"></i>';
                    for ($i=0; $i<$rest; $i++) echo '<i class="far fa-star star"></i>';
                  ?>
                  <small class="ms-2 text-muted"><?php echo number_format((float)$ratingVal,1); ?> (<?php echo (int)($row['total_reviews'] ?? 0); ?>)</small>
                </div>
                <ul class="list-unstyled small mb-3" style="color:#475569;">
                  <?php if (!empty($row['experience_years'])): ?>
                  <li><i class="fas fa-briefcase me-1"></i> Kinh nghiệm: <?php echo (int)$row['experience_years']; ?> năm</li>
                  <?php endif; ?>
                  <?php if (!empty($row['hospital_affiliation'])): ?>
                  <li><i class="fas fa-hospital me-1"></i> Nơi công tác: <?php echo htmlspecialchars($row['hospital_affiliation']); ?></li>
                  <?php endif; ?>
                  <?php if (!empty($row['consultation_fee'])): ?>
                  <li><i class="fas fa-dollar-sign me-1"></i> Phí khám: <?php echo number_format((float)$row['consultation_fee']); ?> VNĐ</li>
                  <?php endif; ?>
                  <?php if (!empty($row['phone'])): ?>
                  <li><i class="fas fa-phone me-1"></i> Liên hệ: <?php echo htmlspecialchars($row['phone']); ?></li>
                  <?php endif; ?>
                  <?php if (!empty($row['available_time'])): ?>
                  <li><i class="fas fa-clock me-1"></i> Giờ khám: <?php echo htmlspecialchars($row['available_time']); ?></li>
                  <?php endif; ?>
                </ul>
              </div>
              <!-- Liên kết sang trang đặt lịch khám mới (DatLichK.php) -->
              <a href="DatLichK.php?doctor_id=<?php echo $row['id']; ?>" class="btn btn-schedule w-100 mt-auto">
                <i class="fas fa-calendar-check me-1"></i> Đặt lịch khám
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-center text-muted">Không tìm thấy bác sĩ tim mạch nào phù hợp.</p>
    <?php endif; ?>
  </div>

  <div class="text-center mt-4">
    <a href="TrangChu.php" class="btn btn-link text-primary"><i class="fas fa-arrow-left me-1"></i> Quay lại Trang chủ</a>
  </div>

  <script>
    const suggestNames = ['Nguyễn Văn A', 'Trần Thị B', 'Lê Văn C', 'Phạm Minh D', 'Ngô Thị E', 'Nguyễn Văn F'];
    const input = document.getElementById('searchDoctor');
    const list = document.getElementById('suggestList');
    input.addEventListener('input', function(){
      const q = this.value.trim().toLowerCase();
      const items = q ? suggestNames.filter(n => n.toLowerCase().includes(q)) : suggestNames.slice(0,6);
      if (!items.length) { list.style.display='none'; return; }
      list.innerHTML = items.map(n => `<div class="p-2 suggestion-item" style="cursor:pointer">${n}</div>`).join('');
      list.style.display='block';
    });
    list.addEventListener('click', e => {
      if (e.target.classList.contains('suggestion-item')) {
        input.value = e.target.textContent.trim();
        list.style.display='none';
      }
    });
    document.addEventListener('click', e => {
      if (!list.contains(e.target) && e.target !== input) list.style.display='none';
    });
  </script>
</div>
</body>
</html>
