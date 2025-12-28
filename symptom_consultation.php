<?php
/**
 * Trang tư vấn triệu chứng chi tiết
 * Cho phép người dùng mô tả triệu chứng và nhận gợi ý chuyên khoa
 */

session_start();
require_once __DIR__ . '/db_config.php';

$conn = getDBConnection();

// Danh sách triệu chứng và mapping chuyên khoa
$symptomCategories = [
    'head' => [
        'name' => 'Đầu & Não',
        'icon' => '🧠',
        'symptoms' => [
            'headache' => ['name' => 'Đau đầu', 'specialties' => ['Thần kinh', 'Nội tổng quát']],
            'dizziness' => ['name' => 'Chóng mặt', 'specialties' => ['Thần kinh', 'Tai mũi họng']],
            'memory_loss' => ['name' => 'Hay quên', 'specialties' => ['Thần kinh', 'Tâm thần']],
            'migraine' => ['name' => 'Đau nửa đầu', 'specialties' => ['Thần kinh']],
        ]
    ],
    'respiratory' => [
        'name' => 'Hô hấp',
        'icon' => '🫁',
        'symptoms' => [
            'cough' => ['name' => 'Ho', 'specialties' => ['Hô hấp', 'Nội tổng quát']],
            'breathing' => ['name' => 'Khó thở', 'specialties' => ['Hô hấp', 'Tim mạch']],
            'chest_tight' => ['name' => 'Tức ngực', 'specialties' => ['Hô hấp', 'Tim mạch']],
            'runny_nose' => ['name' => 'Sổ mũi', 'specialties' => ['Tai mũi họng', 'Nội tổng quát']],
        ]
    ],
    'heart' => [
        'name' => 'Tim mạch',
        'icon' => '❤️',
        'symptoms' => [
            'chest_pain' => ['name' => 'Đau ngực', 'specialties' => ['Tim mạch', 'Hô hấp']],
            'palpitation' => ['name' => 'Tim đập nhanh', 'specialties' => ['Tim mạch']],
            'high_bp' => ['name' => 'Huyết áp cao', 'specialties' => ['Tim mạch', 'Nội tổng quát']],
            'swelling' => ['name' => 'Phù chân', 'specialties' => ['Tim mạch', 'Thận']],
        ]
    ],
    'digestive' => [
        'name' => 'Tiêu hóa',
        'icon' => '🍽️',
        'symptoms' => [
            'stomach_pain' => ['name' => 'Đau bụng', 'specialties' => ['Tiêu hóa', 'Nội tổng quát']],
            'nausea' => ['name' => 'Buồn nôn', 'specialties' => ['Tiêu hóa', 'Nội tổng quát']],
            'diarrhea' => ['name' => 'Tiêu chảy', 'specialties' => ['Tiêu hóa', 'Nhiễm']],
            'constipation' => ['name' => 'Táo bón', 'specialties' => ['Tiêu hóa']],
            'heartburn' => ['name' => 'Ợ nóng', 'specialties' => ['Tiêu hóa']],
        ]
    ],
    'musculoskeletal' => [
        'name' => 'Cơ xương khớp',
        'icon' => '🦴',
        'symptoms' => [
            'joint_pain' => ['name' => 'Đau khớp', 'specialties' => ['Cơ xương khớp', 'Nội tổng quát']],
            'back_pain' => ['name' => 'Đau lưng', 'specialties' => ['Cơ xương khớp', 'Thần kinh']],
            'muscle_pain' => ['name' => 'Đau cơ', 'specialties' => ['Cơ xương khớp']],
            'stiffness' => ['name' => 'Cứng khớp', 'specialties' => ['Cơ xương khớp']],
        ]
    ],
    'general' => [
        'name' => 'Triệu chứng chung',
        'icon' => '🌡️',
        'symptoms' => [
            'fever' => ['name' => 'Sốt', 'specialties' => ['Nội tổng quát', 'Nhiễm']],
            'fatigue' => ['name' => 'Mệt mỏi', 'specialties' => ['Nội tổng quát', 'Tim mạch']],
            'weight_loss' => ['name' => 'Sụt cân', 'specialties' => ['Nội tổng quát', 'Nội tiết']],
            'insomnia' => ['name' => 'Mất ngủ', 'specialties' => ['Thần kinh', 'Tâm thần']],
        ]
    ],
    'skin' => [
        'name' => 'Da liễu',
        'icon' => '🧴',
        'symptoms' => [
            'rash' => ['name' => 'Phát ban', 'specialties' => ['Da liễu', 'Dị ứng']],
            'itching' => ['name' => 'Ngứa', 'specialties' => ['Da liễu', 'Dị ứng']],
            'acne' => ['name' => 'Mụn', 'specialties' => ['Da liễu']],
            'hair_loss' => ['name' => 'Rụng tóc', 'specialties' => ['Da liễu', 'Nội tiết']],
        ]
    ],
    'eyes' => [
        'name' => 'Mắt',
        'icon' => '👁️',
        'symptoms' => [
            'blurry_vision' => ['name' => 'Mờ mắt', 'specialties' => ['Mắt']],
            'eye_pain' => ['name' => 'Đau mắt', 'specialties' => ['Mắt']],
            'red_eyes' => ['name' => 'Đỏ mắt', 'specialties' => ['Mắt']],
        ]
    ],
    'ent' => [
        'name' => 'Tai mũi họng',
        'icon' => '👂',
        'symptoms' => [
            'ear_pain' => ['name' => 'Đau tai', 'specialties' => ['Tai mũi họng']],
            'hearing_loss' => ['name' => 'Giảm thính lực', 'specialties' => ['Tai mũi họng']],
            'sore_throat' => ['name' => 'Đau họng', 'specialties' => ['Tai mũi họng', 'Nội tổng quát']],
            'tinnitus' => ['name' => 'Ù tai', 'specialties' => ['Tai mũi họng', 'Thần kinh']],
        ]
    ],
];

// Xử lý POST - phân tích triệu chứng
$suggestedSpecialty = null;
$selectedSymptoms = [];
$symptomDuration = '';
$symptomSeverity = '';
$additionalInfo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedSymptoms = $_POST['symptoms'] ?? [];
    $symptomDuration = $_POST['duration'] ?? '';
    $symptomSeverity = $_POST['severity'] ?? '';
    $additionalInfo = $_POST['additional_info'] ?? '';
    
    // Tính điểm cho từng chuyên khoa
    $specialtyScores = [];
    foreach ($selectedSymptoms as $symptomKey) {
        foreach ($symptomCategories as $category) {
            if (isset($category['symptoms'][$symptomKey])) {
                foreach ($category['symptoms'][$symptomKey]['specialties'] as $index => $spec) {
                    // Chuyên khoa đầu tiên được ưu tiên hơn
                    $score = $index === 0 ? 2 : 1;
                    $specialtyScores[$spec] = ($specialtyScores[$spec] ?? 0) + $score;
                }
            }
        }
    }
    
    // Sắp xếp và lấy chuyên khoa phù hợp nhất
    arsort($specialtyScores);
    $suggestedSpecialty = array_key_first($specialtyScores) ?? 'Nội tổng quát';
    
    // Lấy tên triệu chứng đã chọn
    $symptomNames = [];
    foreach ($selectedSymptoms as $symptomKey) {
        foreach ($symptomCategories as $category) {
            if (isset($category['symptoms'][$symptomKey])) {
                $symptomNames[] = $category['symptoms'][$symptomKey]['name'];
            }
        }
    }
    
    // Lưu vào session
    $_SESSION['consultation_symptoms'] = $selectedSymptoms;
    $_SESSION['consultation_symptom_names'] = $symptomNames;
    $_SESSION['consultation_duration'] = $symptomDuration;
    $_SESSION['consultation_severity'] = $symptomSeverity;
    $_SESSION['consultation_additional'] = $additionalInfo;
    $_SESSION['suggested_specialty'] = $suggestedSpecialty;
    
    // Chuyển sang trang DatLichK.php với chuyên khoa gợi ý
    header("Location: DatLichK.php?suggested_specialty=" . urlencode($suggestedSpecialty) . "&from_consultation=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tư vấn triệu chứng - KhamCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        :root {
            --primary: #0891b2;
            --primary-light: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --border: #e2e8f0;
            --radius: 12px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #06b6d4 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: var(--bg-primary);
            border-radius: var(--radius);
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            padding: 30px;
            margin-bottom: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }

        /* Logo Styles - giống TimKiemBS.php */
        .logo-link {
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #06b6d4;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .logo-link span {
            background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 50%, #10b981 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: logoTextShine 3s linear infinite;
        }

        @keyframes logoTextShine {
            0% {
                background-position: 0% center;
            }
            100% {
                background-position: 200% center;
            }
        }

        .logo-link:hover {
            transform: scale(1.02);
        }

        .logo-link:hover span {
            animation-duration: 1.5s;
        }

        .logo-link img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            border-radius: 18px;
            background: transparent;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: logoFloat 3s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
        }

        .logo-link:hover img {
            transform: scale(1.08) rotate(2deg);
            filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.25));
            animation-play-state: paused;
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes logoPulse {
            0%, 100% {
                filter: drop-shadow(0 4px 12px rgba(6, 182, 212, 0.3));
            }
            50% {
                filter: drop-shadow(0 6px 20px rgba(6, 182, 212, 0.5));
            }
        }

        .page-title {
            margin: 0;
            flex: 1;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.025em;
        }

        .page-title i {
            margin-right: 8px;
            color: var(--primary);
        }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .category-tab {
            padding: 10px 18px;
            background: var(--bg-secondary);
            border: 2px solid var(--border);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .category-tab:hover {
            border-color: var(--primary-light);
            background: #ecfeff;
        }

        .category-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Symptoms Grid */
        .symptoms-container {
            display: none;
        }

        .symptoms-container.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .symptoms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }

        .symptom-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: var(--bg-secondary);
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .symptom-checkbox:hover {
            border-color: var(--primary-light);
        }

        .symptom-checkbox.selected {
            background: #cffafe;
            border-color: var(--primary);
        }

        .symptom-checkbox input {
            display: none;
        }

        .symptom-checkbox .check-icon {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: transparent;
            transition: all 0.2s ease;
        }

        .symptom-checkbox.selected .check-icon {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* Additional Info */
        .additional-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid var(--border);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Selected Symptoms Display */
        .selected-symptoms {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: var(--radius);
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .selected-symptoms h4 {
            color: #92400e;
            margin-bottom: 10px;
        }

        .selected-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .symptom-tag {
            background: #fbbf24;
            color: #78350f;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Result Box */
        .result-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid var(--success);
            border-radius: var(--radius);
            padding: 25px;
            margin-top: 25px;
            text-align: center;
        }

        .result-box h3 {
            color: #065f46;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .specialty-badge {
            display: inline-block;
            background: var(--success);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .result-box p {
            color: #047857;
            margin-bottom: 20px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
        }

        .btn-outline {
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .category-tabs {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <a href="TrangChu.php" class="logo-link">
                    <img src="image/logo.png.png" alt="Logo">
                    <span>KhamCare</span>
                </a>
                <h1 class="page-title"><i class="fas fa-stethoscope"></i> Tư vấn triệu chứng</h1>
                <a href="DatLichK.php" class="btn btn-outline" style="padding: 10px 20px;">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            </div>

            <?php if ($suggestedSpecialty): ?>
            <!-- Kết quả phân tích -->
            <div class="result-box">
                <h3><i class="fas fa-lightbulb"></i> Kết quả phân tích</h3>
                <p>Dựa trên các triệu chứng bạn mô tả, chúng tôi gợi ý:</p>
                <div class="specialty-badge">🩺 <?php echo htmlspecialchars($suggestedSpecialty); ?></div>
                <p style="font-size: 0.9rem;">Đây chỉ là gợi ý ban đầu. Bác sĩ sẽ chẩn đoán chính xác khi khám.</p>
                
                <div class="btn-group">
                    <a href="DatLichK_new.php?mode=symptoms&suggested=<?php echo urlencode($suggestedSpecialty); ?>" class="btn btn-success">
                        <i class="fas fa-calendar-check"></i> Đặt lịch khám ngay
                    </a>
                    <a href="TuVanTrucTuyen.php?specialty=<?php echo urlencode($suggestedSpecialty); ?>" class="btn btn-primary">
                        <i class="fas fa-comments"></i> Tư vấn trực tuyến
                    </a>
                    <button type="button" class="btn btn-outline" onclick="location.reload()">
                        <i class="fas fa-redo"></i> Phân tích lại
                    </button>
                </div>
            </div>

            <!-- Hiển thị triệu chứng đã chọn -->
            <?php if (!empty($selectedSymptoms)): ?>
            <div class="selected-symptoms" style="margin-top: 20px;">
                <h4><i class="fas fa-clipboard-list"></i> Triệu chứng đã chọn:</h4>
                <div class="selected-tags">
                    <?php 
                    foreach ($selectedSymptoms as $symptomKey) {
                        foreach ($symptomCategories as $category) {
                            if (isset($category['symptoms'][$symptomKey])) {
                                echo '<span class="symptom-tag">' . htmlspecialchars($category['symptoms'][$symptomKey]['name']) . '</span>';
                            }
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <!-- Form chọn triệu chứng -->
            <form method="POST" id="symptomForm">
                <p style="color: var(--text-secondary); margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> Chọn các triệu chứng bạn đang gặp để nhận gợi ý chuyên khoa phù hợp
                </p>

                <!-- Category Tabs -->
                <div class="category-tabs">
                    <?php $first = true; foreach ($symptomCategories as $catKey => $category): ?>
                    <div class="category-tab <?php echo $first ? 'active' : ''; ?>" 
                         onclick="showCategory('<?php echo $catKey; ?>')">
                        <?php echo $category['icon']; ?> <?php echo $category['name']; ?>
                    </div>
                    <?php $first = false; endforeach; ?>
                </div>

                <!-- Symptoms by Category -->
                <?php $first = true; foreach ($symptomCategories as $catKey => $category): ?>
                <div class="symptoms-container <?php echo $first ? 'active' : ''; ?>" id="cat-<?php echo $catKey; ?>">
                    <h4 style="margin-bottom: 15px; color: var(--text-primary);">
                        <?php echo $category['icon']; ?> <?php echo $category['name']; ?>
                    </h4>
                    <div class="symptoms-grid">
                        <?php foreach ($category['symptoms'] as $symKey => $symptom): ?>
                        <div class="symptom-checkbox" onclick="toggleSymptom(event, this)">
                            <input type="checkbox" name="symptoms[]" value="<?php echo $symKey; ?>">
                            <span class="check-icon"><i class="fas fa-check"></i></span>
                            <span><?php echo htmlspecialchars($symptom['name']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php $first = false; endforeach; ?>

                <!-- Additional Info -->
                <div class="additional-section">
                    <h4 style="margin-bottom: 15px; color: var(--text-primary);">
                        <i class="fas fa-info-circle"></i> Thông tin bổ sung
                    </h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Thời gian xuất hiện triệu chứng:</label>
                            <select name="duration">
                                <option value="">-- Chọn --</option>
                                <option value="today">Hôm nay</option>
                                <option value="few_days">Vài ngày qua</option>
                                <option value="week">Khoảng 1 tuần</option>
                                <option value="month">Hơn 1 tháng</option>
                                <option value="long">Đã lâu (> 3 tháng)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Mức độ nghiêm trọng:</label>
                            <select name="severity">
                                <option value="">-- Chọn --</option>
                                <option value="mild">Nhẹ - Không ảnh hưởng sinh hoạt</option>
                                <option value="moderate">Trung bình - Hơi khó chịu</option>
                                <option value="severe">Nặng - Ảnh hưởng nhiều</option>
                                <option value="critical">Rất nặng - Cần khám gấp</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Mô tả thêm (nếu có):</label>
                        <textarea name="additional_info" rows="3" placeholder="Mô tả chi tiết hơn về triệu chứng, tiền sử bệnh, thuốc đang dùng..."></textarea>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="DatLichK.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Phân tích & Gợi ý chuyên khoa
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

<script>
function showCategory(catKey) {
    // Hide all containers
    document.querySelectorAll('.symptoms-container').forEach(el => {
        el.classList.remove('active');
    });
    
    // Remove active from all tabs
    document.querySelectorAll('.category-tab').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show selected category
    document.getElementById('cat-' + catKey).classList.add('active');
    
    // Activate tab
    event.target.classList.add('active');
}

function toggleSymptom(event, element) {
    // Ngăn sự kiện mặc định của label
    event.preventDefault();
    event.stopPropagation();
    
    // Toggle class và checkbox
    element.classList.toggle('selected');
    const checkbox = element.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
}
</script>
</body>
</html>
