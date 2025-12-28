<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lịch xác nhận khám</title>
  <link rel="stylesheet" href="style.css">
  <style>
    * {
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
      margin: 0;
      padding: 0;
    }
    body {
      background: #eaf6fb;
      color: #222;
      padding: 30px;
    }
    .search-container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      border-radius: 18px;
      padding: 32px 28px;
      box-shadow: 0 4px 24px rgba(0,123,255,0.07);
    }
    .search-title {
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 22px;
      color: #007bff;
      text-align: left;
    }
    .doctor-card {
      display: flex;
      background: #f7fbff;
      border: 1.5px solid #b3d7ff;
      border-radius: 14px;
      padding: 18px 18px 18px 12px;
      margin-bottom: 22px;
      transition: box-shadow 0.2s, transform 0.2s;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      box-shadow: 0 2px 12px rgba(0,123,255,0.06);
    }
    .doctor-card:hover {
      transform: translateY(-2px) scale(1.01);
      box-shadow: 0 6px 24px rgba(0,123,255,0.12);
    }
    .doctor-card img {
      width: 82px;
      height: 82px;
      border-radius: 12px;
      object-fit: cover;
      margin-right: 18px;
      background: #eaf6fb;
      border: 2.5px solid #b3d7ff;
    }
    .doctor-info {
      flex: 1;
    }
    .doctor-info h3 {
      font-size: 19px;
      margin-bottom: 4px;
      color: #007bff;
      cursor: pointer;
      text-decoration: underline;
      transition: color 0.2s;
    }
    .doctor-info h3:hover {
      color: #0059c9;
    }
    .specialty {
      color: #007bff;
      font-weight: 500;
      margin-bottom: 4px;
    }
    .schedule {
      margin-top: 10px;
    }
    .time {
      display: inline-block;
      border: 1.5px solid #007bff;
      border-radius: 7px;
      padding: 6px 16px;
      margin: 7px 7px 0 0;
      font-size: 15px;
      color: #007bff;
      cursor: pointer;
      background: #fff;
      transition: background 0.2s, color 0.2s, border 0.2s;
      font-weight: 500;
    }
    .time.active, .time:active {
      background: #007bff;
      color: #fff;
      border: 1.5px solid #007bff;
    }
    .btn-group {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-left: 16px;
      min-width: 150px;
    }
    .confirm-btn {
      background: #28c76f;
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 12px 0;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.2s;
      box-shadow: 0 2px 8px rgba(40,199,111,0.08);
    }
    .confirm-btn:hover {
      background: #20b263;
    }
    .cancel-btn {
      background: #ff4b4b;
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 12px 0;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.2s;
      box-shadow: 0 2px 8px rgba(255,77,77,0.08);
    }
    .cancel-btn:hover {
      background: #d93636;
    }
  </style>
</head>
<body>
  <div class="search-container">
    <h2 class="search-title">Lịch xác nhận khám</h2>
    <div class="doctor-card" id="confirmCard" style="display:none">
      <img id="doctorImg" src="" alt="Bác sĩ">
      <div class="doctor-info">
        <h3 id="doctorName"></h3>
        <p class="specialty" id="doctorSpecialty"></p>
        <p id="doctorExp"></p>
        <p id="doctorPlace"></p>
        <div class="schedule">
          <p><strong>Khung giờ đã chọn:</strong></p>
          <div class="time active" id="doctorTime"></div>
        </div>
      </div>
      <div class="btn-group">
        <button class="confirm-btn" id="btnConfirm">Xác nhận khám</button>
        <button class="cancel-btn" id="btnCancel">Hủy khám</button>
      </div>
    </div>
    <div id="noBooking" style="text-align:center; color:#888; margin-top:40px; display:none;">
      Không có lịch nào cần xác nhận!
    </div>
  </div>
  <script>
    // Lấy thông tin đặt lịch từ localStorage
    const booking = JSON.parse(localStorage.getItem("bookingInfo"));
    if (booking) {
      document.getElementById("confirmCard").style.display = "flex";
      document.getElementById("doctorImg").src = booking.img;
      document.getElementById("doctorName").textContent = "Dr. " + booking.name;
      document.getElementById("doctorSpecialty").textContent = booking.specialty;
      document.getElementById("doctorExp").textContent = booking.exp;
      document.getElementById("doctorPlace").textContent = booking.place;
      document.getElementById("doctorTime").textContent = booking.time;
    } else {
      document.getElementById("noBooking").style.display = "block";
    }

    document.getElementById("btnConfirm").onclick = function() {
      alert("Bạn đã xác nhận khám thành công!");
      localStorage.removeItem("bookingInfo");
      window.location.href = "TrangChu.php";
    };
    document.getElementById("btnCancel").onclick = function() {
      if (confirm("Bạn có chắc muốn hủy lịch khám này?")) {
        localStorage.removeItem("bookingInfo");
        document.getElementById("confirmCard").style.display = "none";
        document.getElementById("noBooking").style.display = "block";
      }
    };
  </script>
</body>
</html>
