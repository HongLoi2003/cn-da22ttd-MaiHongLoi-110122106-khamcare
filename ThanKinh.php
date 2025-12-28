<?php
session_start();
$patient_id = isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '') === 'patient') ? (int)$_SESSION['user_id'] : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Khoa Thần Kinh - KhamCare</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="shared-styles.css">
  <style>
    * {
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
      margin: 0;
      padding: 0;
    }
    body {
      background: #f7f9fc;
      color: #007bff;
      padding: 20px;
    }
    .header {
      text-align: center;
      margin-bottom: 25px;
    }
    .header h1 {
      color: #007bff;
      font-size: 28px;
    }
    .header p {
      color: #2e88ff;
      margin-top: 5px;
    }
    .doctor-list {
      max-width: 900px;
      margin: auto;
    }
    .doctor-card {
      display: flex;
      background: #fff;
      border: 1px solid #e3e3e3;
      border-radius: 12px;
      padding: 15px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: 0.2s;
      align-items: center;
      gap: 18px;
    }
    .doctor-card:hover {
      transform: translateY(-3px);
    }
    .doctor-card img {
      width: 90px;
      height: 90px;
      border-radius: 12px;
      object-fit: cover;
      margin-right: 15px;
    }
    .doctor-info h3 {
      font-size: 18px;
      margin-bottom: 5px;
      color: #007bff;
    }
    .specialty {
      color: #2e88ff;
      font-weight: 600;
      margin-bottom: 5px;
    }
    .schedule {
      margin-top: 8px;
    }
    .time {
      display: inline-block;
      border: 1px solid #2e88ff;
      border-radius: 8px;
      padding: 4px 10px;
      margin: 4px 6px 0 0;
      font-size: 14px;
      color: #2e88ff;
      background: #fff;
      cursor: pointer;
      transition: 0.2s;
    }
    .time:hover, .time.active {
      background: #2e88ff;
      color: white;
    }
    .book-btn {
      display: inline-block;
      margin-top: 10px;
      background: #28c76f;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      transition: 0.2s;
      font-weight: bold;
      border: none;
    }
    .book-btn:hover {
      background: #20b263;
    }
    .footer {
      text-align: center;
      margin-top: 20px;
    }
    .back-home {
      text-decoration: none;
      color: #007bff;
      font-weight: 500;
    }
    .back-home:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <header class="header">
    <h1>Khoa Thần Kinh</h1>
    <p>Danh sách bác sĩ và lịch khám hiện có</p>
  </header>

  <div class="doctor-list" id="doctorList"></div>

  <footer class="footer">
    <a href="TrangChu.php" class="back-home" id="btnBackHome">← Quay lại Trang chủ</a>
  </footer>
  <script>
    const API_BASE = 'api_simple.php';
    const PATIENT_ID = <?php echo (int)$patient_id; ?>;

    document.getElementById('btnBackHome').onclick = function(e){ e.preventDefault(); window.location.href='TrangChu.php'; };

    async function loadNeuroDoctors(){
      let specialtyId = '';
      try { const sp = await fetch(`${API_BASE}?action=get_specialties`).then(r=>r.json()); if (sp.success){ const t = sp.data.find(s=> (s.name||'').toLowerCase().includes('thần') || (s.name||'').toLowerCase().includes('thần kinh')); if (t) specialtyId = t.id; } } catch(e){}
      const url = specialtyId ? `${API_BASE}?action=get_doctors&specialty_id=${specialtyId}` : `${API_BASE}?action=get_doctors&keyword=${encodeURIComponent('Thần Kinh')}`;
      const res = await fetch(url); const data = await res.json();
      const list = document.getElementById('doctorList');
      if (!data.success || !data.data.length){ list.innerHTML = '<div style="text-align:center;color:#666;padding:30px;">Không có bác sĩ Thần Kinh</div>'; return; }
      list.innerHTML = data.data.map(d=>`
        <div class="doctor-card">
          <img src="https://i.imgur.com/ZK1Yk7y.png" alt="${d.full_name}">
          <div class="doctor-info">
            <h3>${d.full_name}</h3>
            <p class="specialty">${d.specialty_name||'Thần Kinh'}</p>
            <p><strong>Kinh nghiệm:</strong> ${d.experience_years||0} năm</p>
            <div class="schedule">
              <p><strong>Lịch khám:</strong></p>
              <div class="time-slots" data-doctor-id="${d.id}"><div style="color:#666;font-style:italic;">Đang tải...</div></div>
            </div>
            <button class="book-btn" data-doctor-id="${d.id}" data-doctor-name="${d.full_name}">Đặt lịch khám</button>
          </div>
        </div>`).join('');

      data.data.forEach(d=>loadSlots(d.id));
      setupBooking();
    }

    async function loadSlots(doctorId){
      const date = new Date().toISOString().slice(0,10);
      try{
        const r = await fetch(`${API_BASE}?action=get_available_slots&doctor_id=${doctorId}&date=${date}`).then(r=>r.json());
        const wrap = document.querySelector(`.time-slots[data-doctor-id="${doctorId}"]`);
        if (r.success && r.data.length){
          wrap.innerHTML = r.data.map(s=>`<div class=\"time\" data-time=\"${s.time}\">${s.display_time}</div>`).join('');
          wrap.querySelectorAll('.time').forEach(el=>{ el.onclick = ()=>{ wrap.querySelectorAll('.time').forEach(x=>x.classList.remove('active')); el.classList.add('active'); }; });
        } else { wrap.innerHTML = '<div style="color:#e74c3c;">Không có lịch trống</div>'; }
      }catch(e){
        const wrap = document.querySelector(`.time-slots[data-doctor-id="${doctorId}"]`);
        if (wrap) wrap.innerHTML = '<div style="color:#e74c3c;">Lỗi tải lịch</div>';
      }
    }

    function setupBooking(){
      document.querySelectorAll('.book-btn').forEach(btn=>{
        btn.onclick = async (e)=>{
          e.preventDefault();
          const docId = btn.getAttribute('data-doctor-id');
          const docName = btn.getAttribute('data-doctor-name');
          const sel = document.querySelector(`.time-slots[data-doctor-id="${docId}"] .time.active`);
          if (!sel){ alert('Vui lòng chọn khung giờ trước khi đặt!'); return; }
          if (!PATIENT_ID){ window.location.href='TaiKhoan.php?redirect=ThanKinh.php'; return; }
          const payload = { patient_id: PATIENT_ID, doctor_id: parseInt(docId), appointment_date: new Date().toISOString().slice(0,10), appointment_time: sel.getAttribute('data-time'), consultation_type: 'offline' };
          try{
            const r = await fetch(`${API_BASE}?action=book_appointment`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) }).then(r=>r.json());
            if (r.success){ alert('Đặt lịch thành công!'); loadSlots(docId); }
            else { alert(r.message||'Đặt lịch thất bại'); }
          }catch(err){ alert('Lỗi kết nối'); }
        };
      });
    }

    loadNeuroDoctors();
  </script>
</body>
</html>