# KhamCare - Hệ Thống Đặt Lịch Khám Bệnh Tích Hợp Tư Vấn Chuyên Khoa

## 🌐 Hướng dẫn truy cập

> **Lưu ý:** Đây là dự án PHP + MySQL, cần chạy trên localhost với XAMPP/WAMP.

### Sau khi cài đặt, truy cập các link sau:

| Trang | Link |
|-------|------|
| 🏠 Trang chủ | `http://localhost/khamcare-do-an/TrangChu.php` |
| 📝 Đăng ký | `http://localhost/khamcare-do-an/TaiKhoan.php?action=register` |
| 🔐 Đăng nhập | `http://localhost/khamcare-do-an/TaiKhoan.php?action=login` |
| 👨‍⚕️ Dành cho Bác sĩ | `http://localhost/khamcare-do-an/doctor_auth.php` |
| ⚙️ Trang quản trị | `http://localhost/khamcare-do-an/admin_khamcare.php` |

---

## Giới thiệu

KhamCare là hệ thống đặt lịch khám bệnh trực tuyến, cho phép bệnh nhân tìm kiếm bác sĩ, đặt lịch hẹn và tư vấn sức khỏe một cách dễ dàng.

## Tính năng chính

- 🏥 **Đặt lịch khám bệnh** - Đặt lịch hẹn với bác sĩ theo chuyên khoa
- 👨‍⚕️ **Quản lý bác sĩ** - Xem thông tin, đánh giá và lịch làm việc của bác sĩ
- 🔍 **Tìm kiếm bác sĩ** - Tìm kiếm theo chuyên khoa, tên hoặc bệnh viện
- 💬 **Tư vấn trực tuyến** - Chat với bác sĩ qua hệ thống
- 🤖 **Chatbot AI** - Hỗ trợ phân tích triệu chứng với Gemini AI
- 📋 **Hồ sơ bệnh án** - Quản lý hồ sơ sức khỏe cá nhân
- 💳 **Thanh toán** - Hỗ trợ thanh toán phí khám
- 🔐 **Xác thực OTP** - Đăng nhập an toàn qua SMS/Email

## Yêu cầu hệ thống

- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx với mod_rewrite
- Composer (tùy chọn)

## Cài đặt

### 1. Clone dự án

```bash
git clone https://github.com/HongLoi2003/khamcare-do-an.git
cd khamcare-do-an
```

### 2. Cấu hình Database

Chỉnh sửa file `db_config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'khamcare_database');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 3. Tạo Database

**Cách 1:** Truy cập trình duyệt:
```
http://localhost/khamcare/setup_database.php
```

**Cách 2:** Import file SQL:
```bash
mysql -u root -p < sql/khamcare_database.sql
```

### 4. Cấu hình Gemini AI (tùy chọn)

Chỉnh sửa file `gemini_config.php` với API key của bạn.

### 5. Cấu hình OTP (tùy chọn)

Chỉnh sửa file `otp_config.php` để cấu hình gửi OTP qua SMS/Email.

## Cấu trúc thư mục

```
khamcare/
├── css/                    # File CSS
├── js/                     # File JavaScript
├── image/                  # Hình ảnh
├── sql/                    # File SQL
├── uploads/                # File upload
├── PHPMailer/              # Thư viện gửi email
├── db_config.php           # Cấu hình database
├── TrangChu.php            # Trang chủ
├── TaiKhoan.php            # Đăng nhập/Đăng ký
├── DatLichK.php            # Đặt lịch khám
├── TimKiemBS.php           # Tìm kiếm bác sĩ
├── ChuyenKhoa.php          # Danh sách chuyên khoa
├── TuVanTrucTuyen.php      # Tư vấn trực tuyến
├── patient_dashboard.php   # Dashboard bệnh nhân
├── doctor_dashboard.php    # Dashboard bác sĩ
├── admin_khamcare.php      # Trang quản trị
└── chatbot_ai.php          # Chatbot AI
```

## Tài khoản mặc định

| Vai trò | Tên đăng nhập | Mật khẩu |
|---------|---------------|----------|
| Admin   | khamcare      | khamcare123 |

## Chuyên khoa

1. Tim Mạch
2. Thần Kinh
3. Sản Phụ Khoa
4. Da Liễu
5. Nhi Khoa
6. Nội Tổng Quát

## API Endpoints

| Endpoint | Mô tả |
|----------|-------|
| `api_create_consultation.php` | Tạo cuộc tư vấn |
| `api_get_messages.php` | Lấy tin nhắn |
| `api_save_message.php` | Gửi tin nhắn |
| `api_gemini_chat.php` | Chat với AI |
| `api_send_otp.php` | Gửi mã OTP |
| `api_verify_otp.php` | Xác thực OTP |

## Công nghệ sử dụng

- **Backend:** PHP, MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **UI Framework:** Font Awesome
- **AI:** Google Gemini API
- **Email:** PHPMailer

## Đóng góp

Mọi đóng góp đều được hoan nghênh. Vui lòng tạo Pull Request hoặc Issue.

## Giấy phép

MIT License

---

## 8. Tài liệu liên quan

- 📄 Báo cáo đồ án: `document/BaoCao_KhamCare.docx`
- 📊 UML: Use Case Diagram, Class Diagram
- 📈 User Flow hệ thống

---

## 9. Hạn chế của hệ thống

- Chưa tích hợp thanh toán trực tuyến
- Chưa có thông báo qua email/SMS
- Giao diện chưa tối ưu trên thiết bị di động

---

## 10. Hướng phát triển

- Tích hợp thanh toán online
- Gửi thông báo lịch khám qua email/SMS
- Nâng cấp giao diện responsive
- Phát triển API cho mobile app

---

## 11. Thông tin sinh viên thực hiện

- **Tên sinh viên**: Mai Hồng Lợi  
- **Lớp**: DA22TTD 
- **MSSV**: 110122106  
- **Môn học / Đề tài**: Phát triển ứng dụng hỗ trợ đặt lịch khám tích hợp tư vấn chuyên khoa  

---

## 12. Giảng viên hướng dẫn

- **Tên giảng viên**: Thạch Kọng Saoane 

---

© 2025 – KHAMCARE Project
