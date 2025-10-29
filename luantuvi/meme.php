<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập Thông Tin Tử Vi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .row {
            display: flex;
            gap: 15px;
        }
        .col {
            flex: 1;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .result {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            display: none;
        }
        .result h2 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .copy-btn {
            background-color: #2196F3;
            margin-top: 15px;
        }
        .copy-btn:hover {
            background-color: #0b7dda;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .checkbox-container input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Nhập Thông Tin Tử Vi</h1>
        
        <form id="dataForm" method="post">
            <div class="form-group">
                <label for="gender">Xưng hô:</label>
                <select id="gender" name="gender" required>
                    <option value="Anh">Anh</option>
                    <option value="Chị">Chị</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="gender-select">Giới tính:</label>
                <select id="gender-select" name="gender-select" required>
                    <option value="nam">Nam</option>
                    <option value="nu">Nữ</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Ngày sinh:</label>
                <div class="row">
                    <div class="col">
                        <label for="day">Ngày:</label>
                        <select id="day" name="day" required>
                            <script>
                                for(let i = 1; i <= 31; i++) {
                                    document.write(`<option value="${i}">${i}</option>`);
                                }
                            </script>
                        </select>
                    </div>
                    <div class="col">
                        <label for="month">Tháng:</label>
                        <select id="month" name="month" required>
                            <script>
                                for(let i = 1; i <= 12; i++) {
                                    document.write(`<option value="${i}">${i}</option>`);
                                }
                            </script>
                        </select>
                    </div>
                    <div class="col">
                        <label for="year">Năm:</label>
                        <select id="year" name="year" required>
                            <script>
                                const currentYear = new Date().getFullYear();
                                for(let i = 1950; i <= currentYear; i++) {
                                    document.write(`<option value="${i}">${i}</option>`);
                                }
                            </script>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="remember-birth-time">Xác nhận thông tin giờ sinh:</label>
                <select id="remember-birth-time" name="remember-birth-time" required>
                    <option value="yes">Tôi nhớ chính xác giờ sinh</option>
                    <option value="no">Tôi không nhớ chính xác giờ sinh</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Giờ sinh:</label>
                <div class="row">
                    <div class="col">
                        <label for="hour">Giờ:</label>
                        <select id="hour" name="hour" required>
                            <script>
                                for(let i = 0; i <= 23; i++) {
                                    document.write(`<option value="${i}">${i}</option>`);
                                }
                            </script>
                        </select>
                    </div>
                    <div class="col">
                        <label for="minute">Phút:</label>
                        <select id="minute" name="minute" required>
                            <script>
                                for(let i = 0; i <= 59; i++) {
                                    document.write(`<option value="${i}">${i}</option>`);
                                }
                            </script>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="namxem">Năm xem:</label>
                <input type="number" id="namxem" name="namxem" min="1990" max="2050" value="2024" required>
            </div>
            
            <!-- Thêm chức năng luận trước cho khách -->
            <div class="form-group checkbox-container">
                <input type="checkbox" id="luanTruoc" name="luanTruoc">
                <label for="luanTruoc">Luận trước cho khách</label>
            </div>
            
            <button type="submit">Xem Kết Quả</button>
        </form>
        
        <div id="resultContainer" class="result">
            <h2>Kết Quả</h2>
            <div id="resultContent"></div>
            <button id="copyBtn" class="copy-btn">Sao Chép Kết Quả</button>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('dataForm');
    const resultContainer = document.getElementById('resultContainer');
    const resultContent = document.getElementById('resultContent');
    const copyBtn = document.getElementById('copyBtn');

    const rememberBirthTime = document.getElementById('remember-birth-time');
    const hourSelect = document.getElementById('hour');
    const minuteSelect = document.getElementById('minute');
    const luanTruocCheckbox = document.getElementById('luanTruoc');

    // Xử lý khi thay đổi trạng thái "Nhớ giờ sinh"
    rememberBirthTime.addEventListener('change', function () {
        if (this.value === 'yes') {
            hourSelect.disabled = false;
            minuteSelect.disabled = false;
        } else {
            hourSelect.disabled = true;
            minuteSelect.disabled = true;
            hourSelect.value = ""; // Xóa giá trị khi không nhớ giờ sinh
            minuteSelect.value = "";
        }
    });

    // Xử lý sự kiện submit form
    form.addEventListener('submit', async function (e) {
        e.preventDefault(); // Ngăn trang tải lại

        const formData = new FormData(form);

        // Xác định URL API dựa trên các lựa chọn của người dùng
        let apiUrl;
        
        // Kiểm tra nếu người dùng chọn "luận trước cho khách"
        if (luanTruocCheckbox.checked) {
            apiUrl = 'luan_truoc_cho_khach.php'; // Trang PHP xử lý chức năng luận trước
        } else if (rememberBirthTime.value === 'no') {
            apiUrl = 'https://luantuvi.io.vn/luan_tuvi_api.php'; // API cho trường hợp không nhớ giờ sinh
        } else {
            apiUrl = 'testthoi.php'; // API thông thường nếu nhớ giờ sinh
        }

        if (rememberBirthTime.value === 'no'){
            try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json(); // Xử lý JSON từ phản hồi

            // Hiển thị chỉ giá trị của "content1" trong kết quả JSON
            resultContent.innerText = result.content1 || "Không có kết quả phù hợp.";
            resultContainer.style.display = 'block'; // Hiện khung kết quả

            } catch (error) {
                console.error('Có lỗi khi gửi request:', error);
                resultContent.innerText = "Đã xảy ra lỗi khi xử lý dữ liệu.";
                resultContainer.style.display = 'block';
            }
        }else{
            try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                body: formData
            });

            const responseText = await response.text();

            // Hiển thị kết quả
            resultContent.innerText = responseText || "Không có kết quả phù hợp.";
            resultContainer.style.display = 'block'; // Hiện khung kết quả

            } catch (error) {
                console.error('Có lỗi khi gửi request:', error);
                resultContent.innerText = "Đã xảy ra lỗi khi xử lý dữ liệu.";
                resultContainer.style.display = 'block';
            }
        }
        // Gửi dữ liệu đến URL tương ứng
        
    });

    // Sao chép kết quả vào clipboard khi nhấn nút "Sao Chép Kết Quả"
    copyBtn.addEventListener('click', function () {
        const text = resultContent.innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Đã sao chép kết quả vào clipboard!');
        });
    });
});
</script>

</body>
</html>