<?php
$host = 'localhost'; 
$db   = 'HAYEON';
$user = 'root'; 
$pass = 'm:Xa/RGQ4K5P';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("CREATE TABLE IF NOT EXISTS photo_event (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uploader_name VARCHAR(255) NOT NULL,
        message TEXT,
        filename VARCHAR(255) NOT NULL,
        uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (\PDOException $e) {
    die("DB 에러: " . $e->getMessage());
}

$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploader = trim($_POST['uploader_name'] ?? '');
    if (!$uploader) $uploader = '익명';
    $msg = trim($_POST['message'] ?? '');
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowed = ['jpg', 'gif', 'png', 'jpeg', 'webp', 'heic'];
        if (in_array($fileExtension, $allowed)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = './uploads/' . $newFileName;

            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $stmt = $pdo->prepare("INSERT INTO photo_event (uploader_name, message, filename) VALUES (?, ?, ?)");
                $stmt->execute([$uploader, $msg, $newFileName]);
                $alert = "success";
            } else { $alert = "error_move"; }
        } else { $alert = "error_ext"; }
    } else { $alert = "error_upload"; }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>📸 강사 찍사 대회</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@400;600;800&family=Gaegu:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #ff8fab;
            --primary-light: #fb6f92;
            --bg-color: #ffe5ec;
            --text-main: #5c3a41;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Pretendard', sans-serif;
            background: linear-gradient(135deg, #ffe5ec 0%, #ffc2d1 100%);
            min-height: 100vh;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 30px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 40px rgba(251, 111, 146, 0.15);
            text-align: center;
        }

        .title {
            font-family: 'Gaegu', cursive;
            font-size: clamp(1.8rem, 8vw, 2.8rem);
            white-space: nowrap;
            color: var(--primary-light);
            margin-bottom: 0.5rem;
            line-height: 1.1;
            letter-spacing: -1px;
            text-shadow: 2px 2px 0 #fff;
        }
        .subtitle {
            font-size: 1.1rem;
            color: #8c6a71;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        /* Image Upload Box */
        .upload-box {
            border: 3px dashed var(--primary);
            border-radius: 20px;
            padding: 2rem 1rem;
            background: rgba(255,255,255,0.6);
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            margin-bottom: 1.5rem;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 250px;
        }
        .upload-box:hover {
            background: rgba(255,255,255,0.9);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .upload-box i {
            font-size: 4rem;
            color: var(--primary-light);
            margin-bottom: 1rem;
        }
        .upload-box p {
            font-weight: 800;
            color: var(--text-main);
        }
        
        #preview-img {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            display: none;
            z-index: 10;
        }

        /* Inputs */
        input[type="text"] {
            width: 100%;
            padding: 1.2rem;
            border-radius: 15px;
            border: 2px solid #fff;
            background: rgba(255,255,255,0.8);
            font-family: inherit;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--text-main);
            outline: none;
            transition: all 0.3s;
            box-shadow: inset 0 2px 5px rgba(0,0,0,0.02);
        }
        input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(251, 111, 146, 0.2);
            background: #fff;
        }
        
        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 1.2rem;
            border-radius: 15px;
            border: none;
            background: var(--primary-light);
            color: white;
            font-size: 1.3rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(251, 111, 146, 0.3);
            font-family: 'Gaegu', cursive;
        }
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(251, 111, 146, 0.4);
            background: #f75c82;
        }

        .floating-nav {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 50;
        }
        .nav-btn {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            padding: 10px 15px;
            border-radius: 20px;
            font-family: inherit;
            font-size: 0.95rem;
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }
        .nav-btn:hover {
            transform: scale(1.05);
            background: white;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

    <div class="container">
        <h1 class="title">📸 강사 찍사 대회</h1>
        <p class="subtitle">강의 중 예쁘게 나온 모습을 포착해주세요!✨<br>베스트 샷을 보내주신 분들께 선물을 드립니다🎁</p>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <!-- Hidden File Input -->
            <input type="file" name="photo" id="photoInput" accept="image/*" style="display:none;" required>
            
            <div class="upload-box" onclick="document.getElementById('photoInput').click()">
                <div id="upload-content">
                    <i class="fa-solid fa-camera-retro"></i>
                    <p>사진을 선택하거나 촬영해주세요!</p>
                </div>
                <img id="preview-img" src="" alt="preview">
            </div>

            <input type="text" name="uploader_name" placeholder="👤 닉네임 (또는 O연수장 N조)" required>
            <input type="text" name="message" placeholder="💬 남기고 싶은 코멘트 (선택)">
            
            <button type="submit" class="submit-btn" id="submitBtn">바로 전송하기 🚀</button>
        </form>
    </div>

    <div class="floating-nav">
        <button onclick="location.href='http://54.116.99.115/HAYEON/photo_event_app/index.html';" class="nav-btn">🏠 메인페이지</button>
    </div>

    <script>
        // Preview Image
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('preview-img');
                    img.src = e.target.result;
                    img.style.display = 'block';
                    document.getElementById('upload-content').style.opacity = '0';
                }
                reader.readAsDataURL(file);
            }
        });

        // Submit Loader
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 전송중...';
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
        });

        // Alerts
        const alertStatus = "<?= $alert ?>";
        if(alertStatus === 'success') {
            Swal.fire({
                title: '전송 완료! 🎉',
                text: '예쁜 사진 너무 감사합니다! 좋은 결과 기대해주세요🥰',
                icon: 'success',
                confirmButtonText: '확인',
                confirmButtonColor: '#fb6f92'
            });
        } else if(alertStatus !== '') {
            Swal.fire({
                title: '오류 발생 😢',
                text: '업로드 중 문제가 발생했습니다. 다시 시도해주세요.',
                icon: 'error',
                confirmButtonColor: '#fb6f92'
            });
        }
    </script>
</body>
</html>
