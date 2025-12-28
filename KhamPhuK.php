<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Khám Phụ Khoa - KhamCare</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="shared-styles.css">
</head>
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

.time:hover {
  background: #2e88ff;
  color: white;
}

.time.active {
  background: #2e88ff;
  color: #fff;
  border: 1px solid #2e88ff;
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
/* Suggestions */
.suggest-box {
  position: relative;
}
.suggest-panel {
  position: absolute;
  top: 48px;
  left: 0; right: 0;
  background: #fff;
  border: 1px solid #e3e3e3;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.10);
  padding: 10px 12px;
  z-index: 50;
  display: none;
}
.suggest-title {
  font-weight: 700;
  color: #007bff;
  margin: 6px 0 8px 2px;
}
.suggest-list { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 8px; }
.suggest-item { padding: 8px 10px; border: 1px solid #e9eef5; border-radius: 8px; cursor: pointer; }
.suggest-item:hover { background: #f3f7ff; border-color: #2e88ff; }
</style>
<body>
  <header class="header">
    <h1>Chuyên Khoa Phụ Khoa</h1>
    <p>Danh sách bác sĩ và lịch khám hiện có</p>
  </header>

  <!-- Bộ lọc -->
  <div class="filter-section" style="max-width: 900px; margin: 0 auto 20px auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <h3 style="margin-bottom: 15px; color: #007bff;">🔍 Tìm kiếm bác sĩ</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; position:relative;">
      <div class="suggest-box" style="flex:1; min-width: 240px;">
        <input type="text" id="searchKeyword" placeholder="Tên bác sĩ, chuyên khoa..." style="width:100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;">
        <div id="suggestPanel" class="suggest-panel">
          <div class="suggest-title">Tất cả bác sĩ</div>
          <div id="suggestDoctors" class="suggest-list"></div>
          <!-- Đã loại bỏ phần "Tất cả chuyên khoa" và các chip tại đây -->
        </div>
      </div>
      <select id="specialtyFilter" style="padding: 10px; border: 1px solid #ddd; border-radius: 8px; min-width: 150px;">
        <option value="">Tất cả chuyên khoa</option>
      </select>
      <button id="searchBtn" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">Tìm kiếm</button>
    </div>
    </div>

  <!-- Loading -->
  <div id="loading" style="text-align: center; padding: 20px; display: none;">
    <div style="color: #007bff;">🔄 Đang tải dữ liệu...</div>
    </div>

  <!-- Danh sách bác sĩ -->
  <div class="doctor-list" id="doctorList">
    <!-- Sẽ được load từ database -->
  </div>

  <!-- Gợi ý tên bác sĩ khi tìm kiếm -->
  <div id="searchNameSuggestPanel" class="suggest-panel" style="top: 48px; left: 0; right: 0; display: none;">
    <div class="suggest-title">Tên bác sĩ phù hợp</div>
    <div id="searchNameSuggestList" class="suggest-list"></div>
  </div>

  <footer class="footer">
    <!-- Đổi thành liên kết thực sự về TrangChu.php -->
    <a href="TrangChu.php" class="back-home" id="btnBackHome">← Quay lại Trang chủ</a>
  </footer>
<script>
// API Base URL
const API_BASE = 'api_simple.php';
const PATIENT_ID = (function(){ try { return <?php session_start(); echo isset($_SESSION['user_id']) && (($_SESSION['role'] ?? '')==='patient') ? (int)$_SESSION['user_id'] : 0; ?>; } catch(e){ return 0; } })();

// Global variables
let currentDoctors = [];
let currentSpecialties = [];

// DOM elements
const doctorList = document.getElementById('doctorList');
const loading = document.getElementById('loading');
const searchKeyword = document.getElementById('searchKeyword');
const specialtyFilter = document.getElementById('specialtyFilter');
const searchBtn = document.getElementById('searchBtn');
const suggestPanel = document.getElementById('suggestPanel');
const suggestDoctors = document.getElementById('suggestDoctors');
// Gợi ý tên bác sĩ khi tìm kiếm
const searchNameSuggestPanel = document.getElementById('searchNameSuggestPanel');
const searchNameSuggestList = document.getElementById('searchNameSuggestList');

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
  loadSpecialties();
  loadDoctors();
  setupEventListeners();
  document.addEventListener('click', function(e){ 
    if (!suggestPanel.contains(e.target) && e.target !== searchKeyword) suggestPanel.style.display='none'; 
    if (!searchNameSuggestPanel.contains(e.target) && e.target !== searchKeyword) searchNameSuggestPanel.style.display='none';
  });
});

// Setup event listeners
function setupEventListeners() {
  // Back home button
  // Đã chuyển thành liên kết thực sự, không cần JS xử lý nữa
  // document.getElementById('btnBackHome').onclick = function(e) {
  //   e.preventDefault();
  //   window.location.href = "TrangChu.php";
  // };

  // Search button
  searchBtn.onclick = function() {
    loadDoctors();
    showNameSuggestions(); // Hiện gợi ý tên bác sĩ khi bấm tìm kiếm
  };

  // Enter key in search input
  searchKeyword.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      loadDoctors();
      showNameSuggestions(); // Hiện gợi ý tên bác sĩ khi nhấn Enter
    }
  });

  // Specialty filter change
  specialtyFilter.addEventListener('change', function() {
    // Khi chọn chuyên khoa từ dropdown, xóa từ khóa tìm kiếm
    searchKeyword.value = ''; 
    loadDoctors();
    hideNameSuggestions();
  });

  // Show suggestions when focus or typing
  searchKeyword.addEventListener('focus', showSuggestions);
  searchKeyword.addEventListener('input', showSuggestions);

  // Khi nhập vào ô tìm kiếm, cập nhật gợi ý tên bác sĩ
  searchKeyword.addEventListener('input', function() {
    hideNameSuggestions();
  });

  // Thêm: Khi click vào nút "Tìm kiếm", nếu có từ khóa thì hiện gợi ý tên bác sĩ phù hợp
  searchBtn.addEventListener('click', function() {
    showNameSuggestions();
  });

  // Add click handler for specialty names
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('clickable-specialty')) {
      const specialtyId = e.target.getAttribute('data-specialty-id');
      const specialtyName = e.target.textContent;
      
      // Set the specialty filter to the clicked specialty
      specialtyFilter.value = specialtyId;
      
      // Update the search keyword to show the specialty name
      searchKeyword.value = specialtyName;
      
      // Filter doctors by this specialty
      filterDoctors();
      
      // Scroll to the doctor list
      document.getElementById('doctorList').scrollIntoView({ behavior: 'smooth' });
    }
  });
}

// Load specialties for filter
async function loadSpecialties() {
  try {
    const response = await fetch(`${API_BASE}?action=get_specialties`);
    const data = await response.json();
    
    if (data.success) {
      currentSpecialties = data.data;
      populateSpecialtyFilter();
      // buildSpecialtyChips(); // Đã loại bỏ
    }
  } catch (error) {
    console.error('Error loading specialties:', error);
  }
}

// Populate specialty filter
function populateSpecialtyFilter() {
  specialtyFilter.innerHTML = '<option value="">Tất cả chuyên khoa</option>';
  currentSpecialties.forEach(specialty => {
    const option = document.createElement('option');
    option.value = specialty.id;
    option.textContent = specialty.name;
    specialtyFilter.appendChild(option);
  });
}

// buildSpecialtyChips() function đã loại bỏ

// Load doctors from API
async function loadDoctors() {
  showLoading(true);
  
  try {
    const params = new URLSearchParams({
      action: 'get_doctors',
      keyword: searchKeyword.value,
      specialty_id: specialtyFilter.value
    });
    
    const response = await fetch(`${API_BASE}?${params}`);
    const data = await response.json();
    
    if (data.success) {
      currentDoctors = data.data;
      renderDoctors();
      buildDoctorSuggestions();
    } else {
      showError('Không thể tải danh sách bác sĩ: ' + data.message);
    }
  } catch (error) {
    console.error('Error loading doctors:', error);
    showError('Lỗi kết nối: ' + error.message);
  } finally {
    showLoading(false);
  }
}

// Build doctors suggestion (hiển thị tất cả bác sĩ khi click vào ô tìm kiếm)
function buildDoctorSuggestions() {
  // Lấy tất cả bác sĩ trong danh sách hiện có
  const keyword = searchKeyword.value.toLowerCase();
  let filteredSuggestions = currentDoctors;

  // Nếu người dùng gõ gì đó, chỉ lọc theo từ khóa
  if (keyword) {
    filteredSuggestions = currentDoctors.filter(d => 
      d.full_name.toLowerCase().includes(keyword)
    );
  }

  // Nếu chưa có danh sách bác sĩ (vì API chưa load), không hiển thị
  if (!filteredSuggestions || filteredSuggestions.length === 0) {
    suggestDoctors.innerHTML = '<div style="color:#888; padding:8px;">Chưa có dữ liệu bác sĩ</div>';
    suggestPanel.style.display = 'block';
    return;
  }

  // Giới hạn hiển thị tối đa 8 bác sĩ đầu tiên
  const top = filteredSuggestions.slice(0, 8);

  // Tạo HTML cho các gợi ý
  suggestDoctors.innerHTML = top.map(d => `
    <div class="suggest-item" data-doctor-id="${d.id}">
      <div style="font-weight:600; color:#222;">${d.full_name}</div>
      <div style="font-size:13px; color:#007bff; text-decoration: underline; cursor: pointer;" class="clickable-specialty" data-specialty-id="${d.specialty_id}">${d.specialty_name}</div>
    </div>
  `).join('');

  // Gắn sự kiện click cho từng bác sĩ trong danh sách gợi ý
  suggestDoctors.querySelectorAll('.suggest-item').forEach(el => {
    el.onclick = function() {
      const name = this.querySelector('div').textContent.trim();
      searchKeyword.value = name;
      suggestPanel.style.display = 'none';
    };
  });

  suggestPanel.style.display = 'block';
}

// Khi click vào ô tìm kiếm → hiện danh sách gợi ý tất cả bác sĩ
function showSuggestions() {
  // Nếu danh sách bác sĩ chưa có, tải lại
  if (currentDoctors.length === 0) {
    loadDoctors().then(() => buildDoctorSuggestions());
  } else {
    buildDoctorSuggestions();
  }
}

// Gợi ý tên bác sĩ khi bấm tìm kiếm
function showNameSuggestions() {
  // Lấy danh sách tên bác sĩ phù hợp với từ khóa tìm kiếm
  const keyword = searchKeyword.value.trim().toLowerCase();
  if (!keyword) {
    searchNameSuggestPanel.style.display = 'none';
    return;
  }
  // Lấy tất cả bác sĩ từ currentDoctors hoặc fetch lại nếu cần
  let allDoctors = currentDoctors;
  // Nếu currentDoctors rỗng, không hiển thị gợi ý
  if (!allDoctors || allDoctors.length === 0) {
    searchNameSuggestPanel.style.display = 'none';
    return;
  }
  // Lọc các tên bác sĩ phù hợp
  const matchedDoctors = allDoctors.filter(d => d.full_name.toLowerCase().includes(keyword));
  if (matchedDoctors.length === 0) {
    searchNameSuggestList.innerHTML = '<div style="color:#888; padding:8px;">Không tìm thấy tên bác sĩ phù hợp</div>';
  } else {
    searchNameSuggestList.innerHTML = matchedDoctors.slice(0, 8).map(d => `
      <div class="suggest-item" data-doctor-id="${d.id}">
        <div style="font-weight:600; color:#222;">${d.full_name}</div>
      </div>
    `).join('');
  }
  // Hiện panel gợi ý tên bác sĩ ngay dưới ô tìm kiếm
  const inputRect = searchKeyword.getBoundingClientRect();
  searchNameSuggestPanel.style.display = 'block';
  // Đặt vị trí panel (nếu cần, ở đây dùng absolute và top:48px)
  // Gắn sự kiện click cho từng tên bác sĩ
  searchNameSuggestList.querySelectorAll('.suggest-item').forEach(el => {
    el.onclick = function() {
      searchKeyword.value = this.querySelector('div').textContent;
      searchNameSuggestPanel.style.display = 'none';
      // Có thể tự động tìm kiếm lại nếu muốn:
      // loadDoctors();
    };
  });
}

// Ẩn panel gợi ý tên bác sĩ
function hideNameSuggestions() {
  searchNameSuggestPanel.style.display = 'none';
}

// Render doctors list
function renderDoctors() {
  if (currentDoctors.length === 0) {
    doctorList.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">Không tìm thấy bác sĩ nào</div>';
    return;
  }

  doctorList.innerHTML = currentDoctors.map(doctor => `
    <div class="doctor-card" data-doctor-id="${doctor.id}">
      <img src="https://i.imgur.com/ZK1Yk7y.png" alt="${doctor.full_name}" onerror="this.src='https://via.placeholder.com/90x90/007bff/ffffff?text=BS'">
      <div class="doctor-info">
        <h3>${doctor.full_name}</h3>
        <p class="specialty clickable-specialty" data-specialty-id="${doctor.specialty_id}" style="cursor: pointer; color: #007bff; text-decoration: underline;">${doctor.specialty_name}</p>
        <p><strong>Kinh nghiệm:</strong> ${doctor.experience_years} năm</p>
        <p><strong>Nơi công tác:</strong> ${doctor.hospital_affiliation || 'Chưa cập nhật'}</p>
        <p><strong>Phí khám:</strong> ${formatCurrency(doctor.consultation_fee)}</p>
        <p><strong>Đánh giá:</strong> ${doctor.rating}/5 (${doctor.total_reviews} đánh giá)</p>
        <div class="schedule">
          <p><strong>Lịch khám:</strong></p>
          <div class="time-slots" data-doctor-id="${doctor.id}">
            <div style="color: #666; font-style: italic;">Đang tải lịch khám...</div>
          </div>
        </div>
        <button class="book-btn" data-doctor-id="${doctor.id}" data-doctor-name="${doctor.full_name}">Đặt lịch khám</button>
      </div>
    </div>
  `).join('');

  // Load schedules for each doctor
  currentDoctors.forEach(doctor => {
    loadDoctorSchedule(doctor.id);
  });

  // Setup booking buttons
  setupBookingButtons();
}

// Load doctor schedule
async function loadDoctorSchedule(doctorId) {
  try {
    const response = await fetch(`${API_BASE}?action=get_available_slots&doctor_id=${doctorId}&date=${getCurrentDate()}`);
    const data = await response.json();
    
    if (data.success) {
      const timeSlots = document.querySelector(`.time-slots[data-doctor-id="${doctorId}"]`);
      if (timeSlots) {
        if (data.data.length > 0) {
          timeSlots.innerHTML = data.data.map(slot => 
            `<div class="time" data-time="${slot.time}">${slot.display_time}</div>`
          ).join('');
          
          // Setup time selection
          setupTimeSelection(doctorId);
        } else {
          timeSlots.innerHTML = '<div style="color: #e74c3c;">Không có lịch trống hôm nay</div>';
        }
      }
    }
  } catch (error) {
    console.error('Error loading schedule:', error);
    const timeSlots = document.querySelector(`.time-slots[data-doctor-id="${doctorId}"]`);
    if (timeSlots) {
      timeSlots.innerHTML = '<div style="color: #e74c3c;">Lỗi tải lịch khám</div>';
    }
  }
}

// Setup time selection
function setupTimeSelection(doctorId) {
  const timeSlots = document.querySelectorAll(`.time-slots[data-doctor-id="${doctorId}"] .time`);
  timeSlots.forEach(slot => {
    slot.onclick = function() {
      // Remove active from other slots in same doctor
      timeSlots.forEach(s => s.classList.remove('active'));
      this.classList.add('active');
    };
  });
}

// Setup booking buttons
function setupBookingButtons() {
  document.querySelectorAll('.book-btn').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      const doctorId = this.getAttribute('data-doctor-id');
      const doctorName = this.getAttribute('data-doctor-name');
      
      const selectedTime = document.querySelector(`.time-slots[data-doctor-id="${doctorId}"] .time.active`);
      if (!selectedTime) {
        alert("Vui lòng chọn khung giờ trước khi đặt lịch!");
        return;
      }
      
      bookAppointment(doctorId, doctorName, selectedTime.getAttribute('data-time'));
    };
  });
}

// Book appointment
async function bookAppointment(doctorId, doctorName, time) {
  if (!PATIENT_ID) { window.location.href = 'TaiKhoan.php?redirect=KhamPhuK.php'; return; }
  if (!confirm(`Xác nhận đặt lịch với ${doctorName} vào lúc ${time}?`)) {
    return;
  }

  try {
    const appointmentData = {
      patient_id: PATIENT_ID,
      doctor_id: parseInt(doctorId),
      appointment_date: getCurrentDate(),
      appointment_time: time,
      consultation_type: 'offline',
      appointment_type: 'routine',
      chief_complaint: 'Khám phụ khoa định kỳ',
      notes: 'Đặt lịch qua website'
    };

    const response = await fetch(`${API_BASE}?action=book_appointment`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(appointmentData)
    });

    const data = await response.json();
    
    if (data.success) {
      alert(`Đặt lịch thành công! Mã lịch khám: ${data.appointment_id}`);
      // Reload doctors to update availability
      loadDoctors();
    } else {
      alert('Lỗi đặt lịch: ' + data.message);
    }
  } catch (error) {
    console.error('Error booking appointment:', error);
    alert('Lỗi kết nối: ' + error.message);
  }
}

// Utility functions
function showLoading(show) {
  loading.style.display = show ? 'block' : 'none';
}

function showError(message) {
  doctorList.innerHTML = `<div style="text-align: center; padding: 40px; color: #e74c3c;">${message}</div>`;
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND'
  }).format(amount);
}

function getCurrentDate() {
  return new Date().toISOString().split('T')[0];
}
</script>
</body>
</html>