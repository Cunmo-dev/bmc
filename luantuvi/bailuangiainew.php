<?php
        header('Content-Type: text/html; charset=utf-8');
        function removeAccents($str)
        {
            $str = preg_replace('/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/', 'a', $str);
            $str = preg_replace('/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/', 'e', $str);
            $str = preg_replace('/ì|í|ị|ỉ|ĩ/', 'i', $str);
            $str = preg_replace('/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/', 'o', $str);
            $str = preg_replace('/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/', 'u', $str);
            $str = preg_replace('/ỳ|ý|ỵ|ỷ|ỹ/', 'y', $str);
            $str = preg_replace('/đ/', 'd', $str);
            return $str;
        }
        function conSoChuDao($ngay, $thang, $nam)
        {

            $ngaythangnam = $ngay . $thang . $nam;
            // Tách từng chữ số thành một mảng
            $mangChuSo = str_split($ngaythangnam);

            // Cộng tất cả các chữ số lại với nhau
            $tong = array_sum($mangChuSo);

            // Nếu kết quả là 11 hoặc 22, giữ nguyên
            if ($tong == 11 || $tong == 22) {
                return $tong;
            }

            // Cộng lại nếu kết quả không phải 11 hoặc 22 để đảm bảo chỉ là một chữ số
            return array_sum(str_split($tong));
        }
        function thanglongdaoquan($ngay,$thang,$nam,$giochuyendoi,$gt,$name){
            set_time_limit(300); 
            $uniqueCookieFile1 = __DIR__ . '/cookie_' . uniqid() . '.txt';
            $curl = curl_init();
            // Bước 1: Khởi tạo phiên (session) và lưu cookie
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://thanglongdaoquan.vn/la-so-bat-tu/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                //CURLOPT_COOKIEJAR => $uniqueCookieFile1,  // Lưu cookie vào file
                CURLOPT_COOKIEFILE => $uniqueCookieFile1, // Sử dụng lại cookie
                CURLOPT_HTTPHEADER => array(
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                    'accept-language: en-US,en;q=0.9,vi;q=0.8',
                    'accept-encoding: gzip, deflate',  // Thêm encoding để hỗ trợ nén
                    'connection: keep-alive'  // Đảm bảo kết nối giữ sống
                ),
            ));

            $response = curl_exec($curl);

            // Kiểm tra phản hồi sau yêu cầu đầu tiên
            if (!$response) {
                echo "Yêu cầu đầu tiên thất bại. Chi tiết lỗi: " . curl_error($curl);
                curl_close($curl);
                exit;
            } else {
            }
            $namHienTai = date("Y");

            $urlGet = "https://thanglongdaoquan.vn/la-so-bat-tu/?hoten=".$name."&ngay=".$ngay."&thang=".$thang."&nam=".$nam."&gio=".$giochuyendoi."&phut=30&gioitinh=".$gt;
            // Bước 2: Gửi yêu cầu chính với cookie
            curl_setopt_array($curl, array(
                CURLOPT_URL => $urlGet,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'accept-language: en-US,en;q=0.9,vi;q=0.8',
                'priority: u=0, i',
                'referer: https://thanglongdaoquan.vn/la-so-bat-tu/',
                'sec-ch-ua: "Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: document',
                'sec-fetch-mode: navigate',
                'sec-fetch-site: same-origin',
                'sec-fetch-user: ?1',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            if (file_exists($uniqueCookieFile1)) {
                unlink($uniqueCookieFile1); // Xóa file
            }
            $CungLuanGiai = explode(
                    '<div id="thoi_van_vuong_suy"',
                    explode('Tổng quan ưu, khuyết điểm của mệnh chủ</span></h4></div>', $response)[1]
                )[0];
                return $CungLuanGiai;
        }
        function lichvannien365($ngay,$thang,$nam,$giochuyendoi,$gt,$name){
            set_time_limit(300); 
            $uniqueCookieFile1 = __DIR__ . '/cookie_' . uniqid() . '.txt';
            $curl = curl_init();
            // Bước 1: Khởi tạo phiên (session) và lưu cookie
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://lichvannien365.com/la-so-tu-vi/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                //CURLOPT_COOKIEJAR => $uniqueCookieFile1,  // Lưu cookie vào file
                CURLOPT_COOKIEFILE => $uniqueCookieFile1, // Sử dụng lại cookie
                CURLOPT_HTTPHEADER => array(
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                    'accept-language: en-US,en;q=0.9,vi;q=0.8',
                    'accept-encoding: gzip, deflate',  // Thêm encoding để hỗ trợ nén
                    'connection: keep-alive'  // Đảm bảo kết nối giữ sống
                ),
            ));

            $response = curl_exec($curl);

            // Kiểm tra phản hồi sau yêu cầu đầu tiên
            if (!$response) {
                echo "Yêu cầu đầu tiên thất bại. Chi tiết lỗi: " . curl_error($curl);
                curl_close($curl);
                exit;
            } else {
            }
            $namHienTai = date("Y");

            $urlGet = "name=".$name."&gender=".$gt."&day=".$ngay."&month=".$thang."&year=".$nam."&hour=".$giochuyendoi."&minute=30";
            // Bước 2: Gửi yêu cầu chính với cookie
            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://lichvannien365.com/la-so-tu-vi',
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => $urlGet,
              CURLOPT_HTTPHEADER => array(
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'accept-language: en-US,en;q=0.9',
                'cache-control: max-age=0',
                'content-type: application/x-www-form-urlencoded',
                'origin: https://lichvannien365.com',
                'priority: u=0, i',
                'referer: https://lichvannien365.com/la-so-tu-vi',
                'sec-ch-ua: "Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
                'sec-fetch-dest: document',
                'sec-fetch-mode: navigate',
                'sec-fetch-site: same-origin',
                'sec-fetch-user: ?1',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
              ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            if (file_exists($uniqueCookieFile1)) {
                unlink($uniqueCookieFile1); // Xóa file
            }
            $CungLuanGiai = explode(
                    '<div class="livn-lgiai-item clearfix">',
                    explode('<span>MỆNH</span>', $response)[1]
                )[0];
            return $CungLuanGiai;
        }
        function tuviDotVn($ngay,$thang,$nam,$giochuyendoi,$gt,$name){
            set_time_limit(300); 
            $uniqueCookieFile1 = __DIR__ . '/cookie_' . uniqid() . '.txt';
            $curl = curl_init();
            // Bước 1: Khởi tạo phiên (session) và lưu cookie
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://tuvi.vn/lap-la-so-tu-vi',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                //CURLOPT_COOKIEJAR => $uniqueCookieFile1,  // Lưu cookie vào file
                CURLOPT_COOKIEFILE => $uniqueCookieFile1, // Sử dụng lại cookie
                CURLOPT_HTTPHEADER => array(
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
                    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                    'accept-language: en-US,en;q=0.9,vi;q=0.8',
                    'accept-encoding: gzip, deflate',  // Thêm encoding để hỗ trợ nén
                    'connection: keep-alive'  // Đảm bảo kết nối giữ sống
                ),
            ));

            $response = curl_exec($curl);

            // Kiểm tra phản hồi sau yêu cầu đầu tiên
            if (!$response) {
                echo "Yêu cầu đầu tiên thất bại. Chi tiết lỗi: " . curl_error($curl);
                curl_close($curl);
                exit;
            } else {
            }
            $namHienTai = date("Y");

            $urlGet = "name=".$name."&dayOfDOB=".$ngay."&monthOfDOB=".$thang."&yearOfDOB=".$nam."&calendar=true&timezone=1&hourOfDOB=".$giochuyendoi."&minOfDOB=30&gender=".$gt."&viewYear=".$namHienTai."&viewMonth=10";
            // Bước 2: Gửi yêu cầu chính với cookie
                    curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tuvi.vn/la-so',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $urlGet,
            CURLOPT_HTTPHEADER => array(
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-language: en-US,en;q=0.9,vi;q=0.8',
            'cache-control: max-age=0',
            'content-type: application/x-www-form-urlencoded',
            'origin: https://tuvi.vn',
            'priority: u=0, i',
            'referer: https://tuvi.vn/lap-la-so-tu-vi',
            'sec-ch-ua: "Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            'sec-ch-ua-arch: "x86"',
            'sec-ch-ua-bitness: "64"',
            'sec-ch-ua-full-version: "131.0.6778.70"',
            'sec-ch-ua-full-version-list: "Google Chrome";v="131.0.6778.70", "Chromium";v="131.0.6778.70", "Not_A Brand";v="24.0.0.0"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-model: ""',
            'sec-ch-ua-platform: "Windows"',
            'sec-ch-ua-platform-version: "15.0.0"',
            'sec-fetch-dest: document',
            'sec-fetch-mode: navigate',
            'sec-fetch-site: same-origin',
            'sec-fetch-user: ?1',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
            ),
        ));
            $response = curl_exec($curl);
            return $response;
        }
        function chiSoNgaySinh($ngay)
        {


            $mangChuSo = str_split($ngay);

            // Cộng tất cả các chữ số lại với nhau
            $tong = array_sum($mangChuSo);

            // Nếu kết quả là 11 hoặc 22, giữ nguyên
            if ($tong == 11 || $tong == 22 || $tong == 10) {
                return $tong;
            }

            // Cộng lại nếu kết quả không phải 11 hoặc 22 để đảm bảo chỉ là một chữ số
            return array_sum(str_split($tong));
        }
        function demSoKyTuTrongChuoi($name)
        {
            $str = str_replace(" ", "", $name);
            echo $str;
            $counts = array();
            for ($i = 0; $i < strlen($str); $i++) {
                $char = strtolower($str[$i]);
                if ($char >= 'a' && $char <= 'z') {
                    if (isset($counts[$char])) {
                        $counts[$char]++;
                    } else {
                        $counts[$char] = 1;
                    }
                }
            }

            print_r($counts);
        }
        function XuLyDuLieuBangGemini($noiDung)
        {
            $curl = curl_init();
            $apiKey = str_replace("\"","",GetApi());
            //$apiKey = "AIzaSyDkNIEF9J_SVH_aLdxjHSC3jaoAFk8QdRo";
            
            $loiDan = "Viết lại 1 cách ngắn gọn đoạn văn sau đây thành một văn bản mạch lạc và cân bằng. Hãy:

1. Kết hợp các ý kiến trái chiều thành một lập luận trung hòa
2. Đối với những câu miêu tả ngoại hình thì bỏ đi
3. Làm rõ và phát triển những ý không xung đột với nhau
4. Sử dụng ngôi thứ hai (BẠN) làm ngôi xưng chính, viết trực tiếp cho người đọc về đặc điểm của họ
5. Chỉ giữ lại nội dung chính, loại bỏ mọi ghi chú, lưu ý, cảnh báo hoặc thông tin phụ. Đoạn văn là: ".$noiDung;
              curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key='.$apiKey,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS =>'{
                  "contents": [{
                    "parts":[{"text": "' . $loiDan . '"}]
                    }]
                   }',
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
              ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            // Giải mã JSON và lấy nội dung trả về
            $decodedResponse = json_decode($response, true);  // Chuyển đổi JSON thành mảng

            // Truy cập nội dung bên trong mảng
            if (isset($decodedResponse['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $decodedResponse['candidates'][0]['content']['parts'][0]['text'];
                return $content;
            }else {
	            return "";
            }
        }
        function GetApi(){
            // Kết nối MySQL
            $mysqli = new mysqli("localhost", "luantuvi_admin", "thanhcong140421@", "luantuvi_database");
            //$mysqli = new mysqli("localhost", "root", "", "database");

            // Kiểm tra kết nối
            if ($mysqli->connect_error) {
                die("Kết nối thất bại: " . $mysqli->connect_error);
            }

            // Kiểm tra và tạo bảng nếu chưa tồn tại
            $createTableQuery = "CREATE TABLE IF NOT EXISTS api_index (
                id INT AUTO_INCREMENT PRIMARY KEY,
                current_index INT NOT NULL
            )";
            if (!$mysqli->query($createTableQuery)) {
                die("Lỗi khi tạo bảng: " . $mysqli->error);
            }

            // Kiểm tra xem bảng đã có dữ liệu chưa, nếu chưa thì thêm dữ liệu ban đầu
            $result = $mysqli->query("SELECT COUNT(*) as count FROM api_index");
            $row = $result->fetch_assoc();
            if ($row['count'] == 0) {
                $mysqli->query("INSERT INTO api_index (current_index) VALUES (0)");
            }

            // Danh sách API
            $apiList = [
                "AIzaSyCz6Tj9J82pRd8KPIFA6_aWNCjdZhe9zoU",
                "AIzaSyAX5jTaHFHDrLkP7X6a42tKipK29zK7QqE",
                "AIzaSyCO1YH23TKUduOvllyGDet9nmOwnkr44Ko",
                "AIzaSyBYmtWX_LuP79T7OSj-OnjGXoRJgqnvrzk",
                "AIzaSyCeIN3NKWpOXI8cHmrFxueryriQhjhpJdI",
                "AIzaSyDkNIEF9J_SVH_aLdxjHSC3jaoAFk8QdRo",
                "AIzaSyBcCKVD0ZebGikqedHJWXioN6Nq-xLFkwA",
                "AIzaSyCp4QXnKidUxcZRxg9GVuI47k7SBKe0DSA",
                "AIzaSyCuFt1znCO4usaecoPq-0I6ll6_IPm0XeE",
                "AIzaSyABMvJI0-ovV6s2k9pTOPWrHZuzO9dtnDQ",
                "AIzaSyBH1r-l-aOKjMRbLFHr3XBVh9KjLb-kDjY"
            ];

            // Lấy chỉ số hiện tại từ bảng
            $result = $mysqli->query("SELECT current_index FROM api_index LIMIT 1");
            $row = $result->fetch_assoc();
            $currentApiIndex = $row['current_index'];

            // Lấy API hiện tại
            $apiToUse = $apiList[$currentApiIndex];

            // Tăng chỉ số, quay lại 0 nếu vượt quá danh sách
            $newApiIndex = ($currentApiIndex + 1) % count($apiList);

            // Cập nhật chỉ số trong bảng
            $mysqli->query("UPDATE api_index SET current_index = $newApiIndex");

            // Đóng kết nối
            $mysqli->close();

            // Trả API cho người dùng
            header('Content-Type: application/json');
            return json_encode($apiToUse);
        }
        function bieudoTen($name)
        {
            $map = array(
                "a" => 1, "j" => 1, "s" => 1,
                "b" => 2, "k" => 2, "t" => 2,
                "c" => 3, "l" => 3, "u" => 3,
                "d" => 4, "m" => 4, "v" => 4,
                "e" => 5, "w" => 5, "n" => 5,
                "x" => 6, "o" => 6, "f" => 6,
                "y" => 7, "p" => 7, "g" => 7,
                "z" => 8, "q" => 8, "h" => 8,
                "r" => 9, "i" => 9
            );
            $str1 = str_replace(" ", "", $name);
            $str = strtolower($str1);
            $result = "";
            foreach (str_split($str) as $char) {
                $result .= $map[$char];
            }
            $result = str_split($result);
            sort($result);
            $result = implode('', $result);
            return $result;
        }
        function bieudoNgaySinh($ngay, $thang, $nam)
        {
            $result = $ngay . $thang . $nam;
            $result = str_split($result);
            sort($result);
            $result = implode('', $result);
            return $result;
        }
        function soNoiCam($numbers)
        {
            foreach ($numbers as &$num) {
                $num = intval($num);
            }

            $max = max($numbers); // tìm giá trị lớn nhất
            $result = [];

            foreach ($numbers as $key => $value) {
                if ($value == $max) {
                    $result[] = $key;
                }
            }

            return $result; // [5, 8]
        }
        function soKhuyet($daysonumbers)
        {
            $missing = [];
            $numbers = range(1, 9);
            foreach ($numbers as $number) {
                if (strpos($daysonumbers, (string) $number) === false) {
                    $missing[] = $number;
                }
            }
            return $missing;
        }
        function demSo($str)
        {
            $count = [];
            $result = [];
            $prev = '';
            foreach (str_split($str) as $num) {
                if ($num != $prev) {
                    if ($prev != '') {
                        $result[$prev] = $count[$prev];
                    }
                    $prev = $num;
                    $count[$num] = 1;
                } else {
                    $count[$num]++;
                }
            }

            $result[$prev] = $count[$prev];
            return $result;
        }
        function INT($d)
        {
            return floor($d);
        }
        function jdFromDate($dd, $mm, $yy)
        {
            $a = INT((14 - $mm) / 12);
            $y = $yy + 4800 - $a;
            $m = $mm + 12 * $a - 3;
            $jd = $dd + INT((153 * $m + 2) / 5) + 365 * $y + INT($y / 4) - INT($y / 100) + INT($y / 400) - 32045;
            if ($jd < 2299161) {
                $jd = $dd + INT((153 * $m + 2) / 5) + 365 * $y + INT($y / 4) - 32083;
            }
            return $jd;
        }
        function jdToDate($jd)
        {
            if ($jd > 2299160) { // After 5/10/1582, Gregorian calendar
                $a = $jd + 32044;
                $b = INT((4 * $a + 3) / 146097);
                $c = $a - INT(($b * 146097) / 4);
            } else {
                $b = 0;
                $c = $jd + 32082;
            }
            $d = INT((4 * $c + 3) / 1461);
            $e = $c - INT((1461 * $d) / 4);
            $m = INT((5 * $e + 2) / 153);
            $day = $e - INT((153 * $m + 2) / 5) + 1;
            $month = $m + 3 - 12 * INT($m / 10);
            $year = $b * 100 + $d - 4800 + INT($m / 10);
            //echo "day = $day, month = $month, year = $year\n";
            return array($day, $month, $year);
        }
        function getNewMoonDay($k, $timeZone)
        {
            $T = $k / 1236.85; // Time in Julian centuries from 1900 January 0.5
            $T2 = $T * $T;
            $T3 = $T2 * $T;
            $dr = M_PI / 180;
            $Jd1 = 2415020.75933 + 29.53058868 * $k + 0.0001178 * $T2 - 0.000000155 * $T3;
            $Jd1 = $Jd1 + 0.00033 * sin((166.56 + 132.87 * $T - 0.009173 * $T2) * $dr); // Mean new moon
            $M = 359.2242 + 29.10535608 * $k - 0.0000333 * $T2 - 0.00000347 * $T3; // Sun's mean anomaly
            $Mpr = 306.0253 + 385.81691806 * $k + 0.0107306 * $T2 + 0.00001236 * $T3; // Moon's mean anomaly
            $F = 21.2964 + 390.67050646 * $k - 0.0016528 * $T2 - 0.00000239 * $T3; // Moon's argument of latitude
            $C1 = (0.1734 - 0.000393 * $T) * sin($M * $dr) + 0.0021 * sin(2 * $dr * $M);
            $C1 = $C1 - 0.4068 * sin($Mpr * $dr) + 0.0161 * sin($dr * 2 * $Mpr);
            $C1 = $C1 - 0.0004 * sin($dr * 3 * $Mpr);
            $C1 = $C1 + 0.0104 * sin($dr * 2 * $F) - 0.0051 * sin($dr * ($M + $Mpr));
            $C1 = $C1 - 0.0074 * sin($dr * ($M - $Mpr)) + 0.0004 * sin($dr * (2 * $F + $M));
            $C1 = $C1 - 0.0004 * sin($dr * (2 * $F - $M)) - 0.0006 * sin($dr * (2 * $F + $Mpr));
            $C1 = $C1 + 0.0010 * sin($dr * (2 * $F - $Mpr)) + 0.0005 * sin($dr * (2 * $Mpr + $M));
            if ($T < -11) {
                $deltat = 0.001 + 0.000839 * $T + 0.0002261 * $T2 - 0.00000845 * $T3 - 0.000000081 * $T * $T3;
            } else {
                $deltat = -0.000278 + 0.000265 * $T + 0.000262 * $T2;
            }
            ;
            $JdNew = $Jd1 + $C1 - $deltat;
            //echo "JdNew = $JdNew\n";
            return INT($JdNew + 0.5 + $timeZone / 24);
        }
        function getSunLongitude($jdn, $timeZone)
        {
            $T = ($jdn - 2451545.5 - $timeZone / 24) / 36525; // Time in Julian centuries from 2000-01-01 12:00:00 GMT
            $T2 = $T * $T;
            $dr = M_PI / 180; // degree to radian
            $M = 357.52910 + 35999.05030 * $T - 0.0001559 * $T2 - 0.00000048 * $T * $T2; // mean anomaly, degree
            $L0 = 280.46645 + 36000.76983 * $T + 0.0003032 * $T2; // mean longitude, degree
            $DL = (1.914600 - 0.004817 * $T - 0.000014 * $T2) * sin($dr * $M);
            $DL = $DL + (0.019993 - 0.000101 * $T) * sin($dr * 2 * $M) + 0.000290 * sin($dr * 3 * $M);
            $L = $L0 + $DL; // true longitude, degree
            //echo "\ndr = $dr, M = $M, T = $T, DL = $DL, L = $L, L0 = $L0\n";
            $L = $L * $dr;
            $L = $L - M_PI * 2 * (INT($L / (M_PI * 2))); // Normalize to (0, 2*PI)
            return INT($L / M_PI * 6);
        }
        function getLunarMonth11($yy, $timeZone)
        {
            $off = jdFromDate(31, 12, $yy) - 2415021;
            $k = INT($off / 29.530588853);
            $nm = getNewMoonDay($k, $timeZone);
            $sunLong = getSunLongitude($nm, $timeZone); // sun longitude at local midnight
            if ($sunLong >= 9) {
                $nm = getNewMoonDay($k - 1, $timeZone);
            }
            return $nm;
        }
        function getLeapMonthOffset($a11, $timeZone)
        {
            $k = INT(($a11 - 2415021.076998695) / 29.530588853 + 0.5);
            $last = 0;
            $i = 1; // We start with the month following lunar month 11
            $arc = getSunLongitude(getNewMoonDay($k + $i, $timeZone), $timeZone);
            do {
                $last = $arc;
                $i = $i + 1;
                $arc = getSunLongitude(getNewMoonDay($k + $i, $timeZone), $timeZone);
            } while ($arc != $last && $i < 14);
            return $i - 1;
        }
        /* Comvert solar date dd/mm/yyyy to the corresponding lunar date */
        function convertSolar2Lunar($dd, $mm, $yy, $timeZone)
        {
            $dayNumber = jdFromDate($dd, $mm, $yy);
            $k = INT(($dayNumber - 2415021.076998695) / 29.530588853);
            $monthStart = getNewMoonDay($k + 1, $timeZone);
            if ($monthStart > $dayNumber) {
                $monthStart = getNewMoonDay($k, $timeZone);
            }
            $a11 = getLunarMonth11($yy, $timeZone);
            $b11 = $a11;
            if ($a11 >= $monthStart) {
                $lunarYear = $yy;
                $a11 = getLunarMonth11($yy - 1, $timeZone);
            } else {
                $lunarYear = $yy + 1;
                $b11 = getLunarMonth11($yy + 1, $timeZone);
            }
            $lunarDay = $dayNumber - $monthStart + 1;
            $diff = INT(($monthStart - $a11) / 29);
            $lunarLeap = 0;
            $lunarMonth = $diff + 11;
            if ($b11 - $a11 > 365) {
                $leapMonthDiff = getLeapMonthOffset($a11, $timeZone);
                if ($diff >= $leapMonthDiff) {
                    $lunarMonth = $diff + 10;
                    if ($diff == $leapMonthDiff) {
                        $lunarLeap = 1;
                    }
                }
            }
            if ($lunarMonth > 12) {
                $lunarMonth = $lunarMonth - 12;
            }
            if ($lunarMonth >= 11 && $diff < 4) {
                $lunarYear -= 1;
            }
            return array($lunarDay, $lunarMonth, $lunarYear, $lunarLeap);
        }
        /* Convert a lunar date to the corresponding solar date */
        function convertLunar2Solar($lunarDay, $lunarMonth, $lunarYear, $lunarLeap, $timeZone)
        {
            if ($lunarMonth < 11) {
                $a11 = getLunarMonth11($lunarYear - 1, $timeZone);
                $b11 = getLunarMonth11($lunarYear, $timeZone);
            } else {
                $a11 = getLunarMonth11($lunarYear, $timeZone);
                $b11 = getLunarMonth11($lunarYear + 1, $timeZone);
            }
            $k = INT(0.5 + ($a11 - 2415021.076998695) / 29.530588853);
            $off = $lunarMonth - 11;
            if ($off < 0) {
                $off += 12;
            }
            if ($b11 - $a11 > 365) {
                $leapOff = getLeapMonthOffset($a11, $timeZone);
                $leapMonth = $leapOff - 2;
                if ($leapMonth < 0) {
                    $leapMonth += 12;
                }
                if ($lunarLeap != 0 && $lunarMonth != $leapMonth) {
                    return array(0, 0, 0);
                } else if ($lunarLeap != 0 || $off >= $leapOff) {
                    $off += 1;
                }
            }
            $monthStart = getNewMoonDay($k + $off, $timeZone);
            return jdToDate($monthStart + $lunarDay - 1);
        }
        function LayNoiDungCungLuan ($baiLuan){
                    $CungLuanGiai = explode(
                        '<div class="cursor-pointer cung-data" id="cung-than" data-loaded="false">',
                        explode('<div class="detail-binh-giai-12-cung show-detail-item-12-cung">', $baiLuan)[1]
                    )[0];
                    
                    // Tạo đối tượng DOMDocument và nạp HTML vào với mã hóa UTF-8
                    $dom = new DOMDocument;
                    libxml_use_internal_errors(true); // Để tránh lỗi với các thẻ không hợp lệ
                    $dom->loadHTML(mb_convert_encoding($CungLuanGiai, 'HTML-ENTITIES', 'UTF-8'));  // Chuyển mã hóa sang HTML Entities
                    // Lấy tất cả các thẻ <p> có class="y_nghia"
                    $xpath = new DOMXPath($dom);
                    $nodes = $xpath->query("//p[@class='m-b-10 txt-content']");
                    // Biến để lưu kết quả
                    $result = "";
                    $noiDungCuoi = "";
                    // Kiểm tra nếu có các thẻ <p class="y_nghia">
                    if ($nodes->length > 0) {
                        foreach ($nodes as $node) {
                            $result .= trim($node->nodeValue) . "<br>"; // Thêm nội dung vào biến result
                        }
                        $ndSua = str_replace(":",".",str_replace("\"","",$result));
                        $nd = XuLyDuLieuBangGemini($ndSua);
                        sleep(5);
                        // Tách chuỗi thành mảng các câu
                        $cacCau = explode(". ", $nd);
                        // Lấy từ câu thứ hai trở đi
                        $cacCauSau = array_slice($cacCau, 0);

                        // Nối các câu lại thành chuỗi với dấu chấm câu
                        $noiDungCuoi = implode(". ", $cacCauSau);
                        } else {
                        }
                    // Trả về kết quả
                    return $noiDungCuoi;
                    //return $result;
                }
        function countWordsWithDiacritics($string) {
            // Dùng regex để tìm tất cả các từ có chứa chữ cái và dấu
            preg_match_all('/\p{L}+/u', $string, $matches);

            // Số lượng từ là số phần tử trong mảng matches
            return count($matches[0]);
        }

        function convertThangAm($ngay, $thang, $nam)
        {
            $arr = array_slice(convertSolar2Lunar($ngay, $thang, $nam, 7), 0, 3);
            $arr[0] = str_pad($arr[0], 2, '0', STR_PAD_LEFT);
            $arr[1] = str_pad($arr[1], 2, '0', STR_PAD_LEFT);
            //return implode("/", $arr);
            return $arr[1];
        }
        function generateOrderCode() {
            // Tạo 2 chữ cái ngẫu nhiên
            $prefix = chr(rand(65, 90)) . chr(rand(65, 90));
            
            // Tạo 7 chữ số ngẫu nhiên
            $suffix = rand(1000000, 9999999);
            
            // Kết hợp 2 phần để tạo mã đơn hàng hoàn chỉnh
            $orderCode = $prefix . $suffix;
            
            return $orderCode;
        }
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $nameVN = $_POST['username'];
            $name = removeAccents($nameVN);
            $ngay = $_POST['day'];
            $thang = $_POST['month'];
            $nam = $_POST['year'];
            $gio = $_POST['hour'];
            $newDateFormat = $ngay . " " . "tháng " . $thang . " năm " . $nam;
            $giochuyendoi = 0;
            $tenGioSinh = "";
            if (isset($_POST['remember']) && $_POST['remember'] == "1") {
                switch ($gio) {
                    case 0;
                        $giochuyendoi = 23;
                        $tenGioSinh = "Giờ Tý";
                        break;
                    case 1;
                        $giochuyendoi = 1;
                        $tenGioSinh = "Giờ Sửu";
                        break;
                    case 2;
                        $giochuyendoi = 3;
                        $tenGioSinh = "Giờ Dần";
                        break;
                    case 3;
                        $giochuyendoi = 5;
                        $tenGioSinh = "Giờ Mão";
                        break;
                    case 4;
                        $giochuyendoi = 7;
                        $tenGioSinh = "Giờ Thìn";
                        break;
                    case 5;
                        $giochuyendoi = 9;
                        $tenGioSinh = "Giờ Tỵ";
                        break;
                    case 6;
                        $giochuyendoi = 11;
                        $tenGioSinh = "Giờ Ngọ";
                        break;
                    case 7;
                        $giochuyendoi = 13;
                        $tenGioSinh = "Giờ Mùi";
                        break;
                    case 8;
                        $giochuyendoi = 15;
                        $tenGioSinh = "Giờ Thân";
                        break;
                    case 9;
                        $giochuyendoi = 17;
                        $tenGioSinh = "Giờ Dậu";
                        break;
                    case 10;
                        $giochuyendoi = 19;
                        $tenGioSinh = "Giờ Tuất";
                        break;
                    case 11;
                        $giochuyendoi = 21;
                        $tenGioSinh = "Giờ Hợi";
                        break;

                }
            } else {
                $tenGioSinh = "Không nhớ giờ sinh";
            }
            switch ($gio) {
                    case 0;
                        $giochuyendoi = 23;
                        break;
                    case 1;
                        $giochuyendoi = 1;
                        break;
                    case 2;
                        $giochuyendoi = 3;
                        break;
                    case 3;
                        $giochuyendoi = 5;
                        break;
                    case 4;
                        $giochuyendoi = 7;
                        break;
                    case 5;
                        $giochuyendoi = 9;
                        break;
                    case 6;
                        $giochuyendoi = 11;
                        break;
                    case 7;
                        $giochuyendoi = 13;
                        break;
                    case 8;
                        $giochuyendoi = 15;
                        break;
                    case 9;
                        $giochuyendoi = 17;
                        break;
                    case 10;
                        $giochuyendoi = 19;
                        break;
                    case 11;
                        $giochuyendoi = 21;
                        break;
                }
            $gioiTinhStr = "";
            $gioitinh = $_POST["gender"];
            if ($gioitinh == "Nam") {
                $gioiTinhStr = "true";
                $gt = "nam";
            } else {
                $gioiTinhStr = "false";
                $gt = "nu";
            }
            $thongtinLuanGiai = "<h2>Thông tin luận giải</h2>";
            if (!empty($name) && !empty($ngay) && !empty($thang) && !empty($nam)) {
                $thongtinLuanGiai1 = '<div id="result">Họ tên: ' . $nameVN . '<br>Ngày sinh: ' . $newDateFormat . '<br> Sinh: ' . $tenGioSinh . '<br> Giới Tính: ' . $gioitinh . '</div>';
            }
            $thangAm = convertThangAm($ngay, $thang, $nam);
            if (isset($_POST['remember']) && $_POST['remember'] == "0") {
                $thongtinLuanGiai2 = " <h4> Lưu ý: Đây là bài luận giải Cơ bản(Không phải chuyên sâu) qua ngày tháng năm sinh mà $nameVN cung cấp.<br> Do không có giờ sinh cụ thể nên việc luận giải sẽ chỉ đúng tầm 70% </h4>";
                switch (conSoChuDao($ngay, $thang, $nam)) {
                    case 1;
                        $ketquaLg[] = "<br>•	Bạn là người có tố chất Lãnh đạo.<br>
                •	Có ý chí mạnh mẽ, luôn tin tưởng và theo đuổi những ý tưởng của bản thân.<br>
                •	Ham muốn quyền lực, khát vọng dẫn dắt<br>
                •	Độc lập, tự chủ cao độ<br>
                •	Luôn cầu toàn, khó hài lòng<br>
                •	Tuy nhiên đôi khi bạn cũng cô độc và ít quan tâm đến cảm nhận của người khác.<br>";
                        break;
                    case 2;
                        $ketquaLg[] = "<br>•	Bạn là người nhạy cảm, dễ bị tổn thương, biết lắng nghe và động viên mọi người.<br>
                •	Romantic, lãng mạn trong tình cảm<br>
                •	Hay lo lắng, dễ mất cân bằng và dễ có suy nghĩ tiêu cực<br>
                ";
                        break;
                    case 3;
                        $ketquaLg[] = "<br>•	Bạn là người giỏi giao tiếp, sáng tạo và lưu loát trong ứng xử.<br>
                •	Bạn thích gặp gỡ, giao lưu với mọi người và là người vui vẻ, hòa đồng.<br>
                •	Ưa truyền cảm hứng cho người khác<br>
                •	Bạn dễ bị phân tâm, cũng là người thiếu kiên định<br>
                ";
                        break;
                    case 4;
                        $ketquaLg[] = "<br>•	Bạn là người thực tế, kỷ luật, siêng năng, chịu khó, có tinh thần trách nhiệm.<br>
                •	Tuy nhiên đôi khi bạn cũng giới hạn bản thân trong những quy tắc và khuôn khổ nhất định.<br>
                •	Điều đó làm cho người khác cảm nhận bạn là người cứng nhắc, khó thích nghi với những thay đổi.<br>
                ";
                        break;
                    case 5;
                        $ketquaLg[] = "<br>•	Bạn là người tự do, linh hoạt và thích thay đổi.<br>
                •	Bạn là người nhiệt tình, năng động và luôn tìm tòi học hỏi<br>
                •	Tuy nhiên đôi khi bạn cũng khó tập trung và dễ thay đổi quan điểm, thiếu nhất quán.<br>
                •	Ưa mạo hiểm, thích khám phá những điều mới lạ<br>
                ";
                        break;
                    case 6;
                        $ketquaLg[] = "<br>•	Bạn là mẫu người của gia đình.<br>
                •	Bạn có trách nhiệm cao, đáng tin cậy vì biết giữ chữ tín.<br>
                •	Tuy nhiên đôi khi bạn cũng dễ bị lợi dụng và quá tin người.<br>
                •	Ưu tiên gia đình, luôn chu đáo chăm sóc người thân<br>
                ";
                        break;
                    case 7;
                        $ketquaLg[] = "<br>•	Bạn là người thông minh, sâu sắc, và có óc phán đoán nhanh nhạy.<br>
                •	Bạn là người có tầm nhìn xa, ham hiểu biết và khám phá bản chất sự vật.<br>
                •	Tuy nhiên bạn cũng khá kín đáo, ít bộc lộ cảm xúc, khó mở lòng với người khác.<br>
                ";
                        break;
                    case 8;
                        $ketquaLg[] = "<br>•	Bạn là 1 người thực tế, tinh ý và giỏi tính toán chiến lược.<br>
                •	Bạn có khả năng quản lý tài chính tốt và có ý trí cầu tiến.<br>
                •	Tuy nhiên đôi khi bạn cũng trở nên tham vọng và vô cảm.<br>
                ";
                        break;
                    case 9;
                        $ketquaLg[] = "<br>•	Bạn là người nhân hậu, rộng lượng và bao dung, thích giúp đỡ kẻ yếu,<br>
                •	Tuy nhiên đôi khi bạn cũng dễ bị kẻ xấu lợi dụng vì sống chân thành, chu đáo và luôn nghĩ về lợi ích của những người xung quang<br>
                ";
                        break;
                    case 11;
                        $ketquaLg[] = "<br>•	Bạn là người thường rất nhạy cảm và giàu trực giác.<br>
                •	Bạn có khả năng giao tiếp tốt, kết nối với người khác dễ dàng và có sức hấp dẫn mạnh mẽ.<br>
                •	Tuy nhiên đôi khi bạn cũng khó kiểm soát cảm xúc cá nhân và dễ căng thẳng.<br>
                ";
                        break;
                    case 22;
                        $ketquaLg[] = "<br>•	Bạn là người thực tế, có tầm nhìn xa và giỏi xây dựng kế hoạch, chiến lược.<br>
                •	Bạn có tài quản lý lãnh đạo.<br>
                •	Bạn phải gánh vác nhiều trách nhiệm nên dễ căng thẳng, mệt mỏi<br>
                ";
                        break;
                }
                 $dayso = bieudoNgaySinh($ngay, $thang, $nam);
                // $daySoTen = bieudoTen($name);
                // $mangbieudoTen = demSo($daySoTen);
                // $mangSoNoiCam = soNoiCam($mangbieudoTen);
                // $mangSoKhuyet = soKhuyet($daySoTen);
                $mangbieudo = demSo($dayso);
                if (isset($mangbieudo[1])) {
                    switch (intval($mangbieudo[1])) {
                        case 1;
                            $ketquaLg[] = "•	Bạn thường gặp khó khăn trong việc diễn đạt cảm xúc, tâm tư nội tâm của bản thân, mặc dù bạn có thể nói hay về những chủ đề khác.<br>
                    •   Đôi khi bạn còn nói những lời làm tổn thương người khác một cách vô tình.<br>
                    •   Bạn có ý chí mạnh mẽ, luôn tin tưởng và theo đuổi những ý tưởng của bản thân.<br>
                    •	Để cải thiện, bạn cần học cách suy nghĩ kỹ trước khi nói, phản ứng chậm lại trước những ý kiến phê bình.Độc lập, tự chủ cao độ<br>
                    •	Bên cạnh đó, bạn thường cảm thấy thiếu thiếu điều gì đó trong cuộc sống. Để khắc phục, bạn nên viết nhật ký cảm xúc hàng ngày, đọc lại và quan sát phản ứng của bản thân trước gương để tự tin hơn trong việc diễn đạt cảm xúc.<br>";
                            break;
                        case 2;
                            $ketquaLg[] = "
                    •   Bạn có thể dễ dàng thương lượng, thuyết phục và làm việc hiệu quả với nhiều loại người khác nhau.<br>
                    •   Tuy nhiên, không nên lạm dụng hoặc khoe khoang, hoặc coi thường người khác, nhất là người yêu hay vợ/chồng<br>";
                            break;
                        case 3;
                            if (isset($mangbieudo[2]) && isset($mangbieudo[5]) && isset($mangbieudo[8])) {
                                $ketquaLg[] = "• Bạn là một người năng động, lạc quan, hay nói, chia sẻ niềm vui cuộc sống với mọi người.<br>
                        •   Tuy nhiên, Bạn cũng là người có một tâm trạng khá thất thường<br>";
                            } else {
                                $ketquaLg[] = "• Bạn là một người trầm lặng, ít nói sống hướng nội.<br>
                        •   Tuy nhiên, Bạn cũng là người có một tâm trạng khá thất thường<br>
                        ";
                            }
                            break;
                        case 4;
                            $ketquaLg[] = "• Bạn là người hay gặp trục trặc với vấn đề diễn đạt bằng lời, và vì vậy, rất hay bị người khác hiểu lầm.<br>
                        •   Bạn là người có cái tôi cao nhưng bạn lại không dễ dàng diễn đạt được ra ngoài những cảm giác sâu, đậm như vậy về bản thân mình.<br>
                        •   Chính vì vậy bạn cần học cách kiểm soát cảm xúc để cuộc sống thoải mái hơn.<br>
                        ";
                            break;
                        default;
                            $ketquaLg[] = "• Bản ngã bị đè nén nên rất khó diễn đạt cảm xúc. Dễ cô độc, mất cân bằng.<br>
                    •   Cần cho trẻ em tham gia các hoạt động nghệ thuật để giúp chúng biểu đạt cảm xúc.<br>";
                            break;
                    }
                }
                if (isset($mangbieudo[2])) {
                    switch (intval($mangbieudo[2])) {
                        case 1;
                            $ketquaLg[] = "•	Bạn không phải là người có trực giác nhanh nhậy, nói cách khác trực giác của bạn chỉ đang ở mức độ cơ bản, có thể chưa đủ để thành công trong thế giới cạnh tranh.<br>
                    •   Bạn cần phát triển thêm tính linh hoạt và khả năng thích ứng để cạnh tranh tốt hơn.<br>
                    •   Bạn cần có được sự cân bằng về mặt cảm xúc để không bị tổn thương dễ dàng.<br>";
                            break;
                        case 2;
                            $ketquaLg[] = "• Bạn có 1 trực giác và độ nhạy cảm tốt, được mọi người đánh giá là rất thông minh.<br>
                    •   Trực giác của bạn rất đáng tin khi đưa ra \"ấn tượng đầu tiên\"..<br>
                    •   Tuy nhiên, cần tránh để cái tôi làm sai lệch trực giác.<br>";
                            break;
                        case 3;
                            $ketquaLg[] = "• Bạn dễ bị ảnh hưởng bởi cảm xúc của người khác và thường xuyên bị lôi kéo vào vấn đề của người khác.<br>
                    •   Để tự bảo vệ, bạn thường ở trong thế giới riêng, có xu hướng trở nên đơn độc và cô độc.<br>
                    •   Bạn thường bị quá mức nhạy cảm, khó cân bằng và mang lại gánh nặng tâm lý.<br>
                    ";
                            break;
                        case 4;
                            $ketquaLg[] = "• Bạn là người có mức độ nhạy cảm quá cao, cần được kiểm soát chặt chẽ, nếu không sẽ dễ dẫn đến các phản ứng tiêu cực.<br>
                        •   Bạn hay thiếu kiên nhẫn, dễ diễn dịch sai và tin nhầm người. Hay phản ứng thái quá và mất cân bằng cảm xúc.<br>
                        •   Bạn ít bạn bè, bạn cần học cách tự kiểm soát, thư giãn bằng thiền, chấp nhận dòng đời và mở lòng để được tư vấn.<br>
                        ";
                            break;
                        default;
                            $ketquaLg[] = "• Bạn là người có mức độ nhạy cảm đặc biệt cao, dễ bị kích động và phản ứng mạnh mẽ trước các tác động bên ngoài..<br>
                    •   Bạn thường thử thách giới hạn kiên nhẫn của mọi người xung quanh.<br>
                    •   Bạn rất cần sự quan tâm chăm sóc và hướng dẫn cực kỳ tận tâm, kiên nhẫn từ phía cha mẹ, thầy cô ngay từ khi còn nhỏ.<br>";
                            break;
                    }
                }
                if (isset($mangbieudo[3])) {
                    switch (intval($mangbieudo[3])) {
                        case 1;
                            $ketquaLg[] = "•	Bạn sống tích cực, mang năng lượng tích cực vào công việc và có mức độ tự tin cao, góp phần vào thành công.<br>
                    •   Hồi trẻ bạn là người sáng dạ, học tập tốt, cả học chính khóa và học hỏi ngoài đời, do chủ động quan tâm đến môi trường xung quanh.<br>";
                            break;
                        case 2;
                            $ketquaLg[] = "• Bạn là người sáng dạ,có trí tưởng tượng phong phú và khả năng văng chương tốt .<br>";
                            break;
                        case 3;
                            $ketquaLg[] = "• Bạn là người có khuynh hướng mất liên kết với thực tế và tập trung vào tưởng tượng quá nhiều.<br>
                    •   Bạn khó tin tưởng người khác, dễ bị stress.<br>
                    •   Bạn rất ít bạn thân và cảm thấy không hạnh phúc.<br>
                    ";
                            break;
                        default;
                            $ketquaLg[] = "• Bạn là kiểu tưởng tượng, mơ mộng xa rời thực tế quá mức. <br>
                    •   Bạn Cả ngày sẽ chỉ đắm chìm trong sự mơ mộng xa xôi và bay bổng của thế giới hư ảo. Điều này, khiến bạn dễ bị cô lập và bỏ rơi cuộc sống thực tại.<br>";
                            break;
                    }
                }
                if (isset($mangbieudo[4])) {
                    switch (intval($mangbieudo[4])) {
                        case 1;
                            $ketquaLg[] = "•	Bạn là người rất chủ động, hăng hái, và thực tế trong công việc.<br>
                    •   Bạn thích chủ động nhận trước và hỗ trợ đồng nghiệp trong các hoạt động tập thể.<br>
                    •   Bạn dễ nghi ngờ và hoài nghi, bạn chỉ tin tưởng dựa trên kết quả và diễn biến của sự việc.<br>
                    •   Bạn cũng quý trọng lời hứa và ghét trễ hẹn, thất hứa. <br>
                    ";
                            break;
                        case 2;
                            $ketquaLg[] = "• Bạn là người rất thực tế, bạn coi trọng vật chất và ít quan tâm đến giá trị tinh thần.<br>
                    •   Nhiều khi bạn đặt lợi ích cá nhân của mình lên trên khiến mất đi thiện cảm đối với người khác<br>
                    •   Để cải thiện điều này, bạn cần tập trung vào việc hài hòa và cân bằng suy nghĩ, cuộc sống, và góc nhìn giữa vật chất và tình cảm. <br>
                    •   Bạn nên chọn những người bạn biết trân trọng những giá trị thẩm mỹ, văn hóa, đạo đức của bạn, thì cuộc đời của bạn sẽ được cân bằng nhanh về hướng tốt đẹp.<br>
                    ";
                            break;
                        default;
                            $ketquaLg[] = "• Bạn bị cột chặt vào những giá trị vật chất, mỗi khi bạn có ý thức muốn thoát ra để tiến xa hơn, lại dễ bị giá trị vật chất lôi kéo trở lại.<br>
                    •   Bạn khó thay đổi suy nghĩ liên quan đến lợi ích cá nhân và cần ý chí cao độ để thay đổi.<br>
                    •   Bạn rất cần sự quan tâm, dẫn dắt, định hướng, kiên nhẫn từ những người thân xung quanh.<br>
                    •   Bạn nên chọn những người bạn biết trân trọng những giá trị thẩm mỹ, văn hóa, đạo đức của bạn, thì cuộc đời của bạn sẽ được cân bằng nhanh về hướng tốt đẹp.<br>"
                            ;
                            break;
                    }
                }
                if (isset($mangbieudo[5])) {
                    switch (intval($mangbieudo[5])) {
                        case 1;
                            $ketquaLg[] = "•	Bạn có khả năng kiểm soát cảm xúc tốt và nhạy cảm với mọi vấn đề trong cuộc sống, giúp bạn duy trì sự cân bằng giữa vật chất và tinh thần. <br>
                    •   Bạn cũng dễ dàng lựa chọn lý trí hay cảm xúc và biết điều chỉnh cảm xúc nhờ khả năng nhạy bén.<br>
                    ";
                            break;
                        case 2;
                            $ketquaLg[] = "• Bạn có tính cách khó tính, tự tin và quyết tâm.<br>
                    •   Bạn cần chú trọng đến việc quản lý cảm xúc và học cách cân bằng để không bị sa đà vào các thói quen gây nghiện nhằm giải tỏa cảm xúc dồn nén.<br>
                    ";
                            break;
                        case 3;
                            $ketquaLg[] = "• Bạn là người rất cá tính, nhưng cũng đồng nghĩa với bạn khó kiểm soát được cảm xúc của mình.<br>
                    •   Bạn cần chú trọng đến việc quản lý cảm xúc và học cách cân bằng, hãy suy nghĩ cẩn trọng trước khi cất lời để tránh vô tình làm người khác mất lòng<br>
                    ";
                            break;
                        default;
                            $ketquaLg[] = "• Bạn gặp các vấn đề sức khỏe có liên quan đến vùng bụng.<br>
                    •   Bạn hay bị căng thẳng và stress.<br>";
                            break;
                    }
                }
                if (isset($mangbieudo[6])) {
                    switch (intval($mangbieudo[6])) {
                        case 1;
                            $ketquaLg[] = "•	Bạn có khả năng sáng tạo tốt<br>
                    •   Bạn có trách nhiệm cao với gia đình, luôn sẵn sàng hỗ trợ, giúp đỡ người thân<br>
                    •   Bạn nên tìm cách cân bằng cuộc sống và chú trọng đến sức khỏe tinh thần.<br>
                    ";
                            break;
                        case 2;
                            $ketquaLg[] = "• Bạn có tính cách Cứng đầu, bướng bỉnh hoặc thiếu mất một khía cạnh nào đó trong tính cách. <br>
                    •   Bạn nên làm công việc sáng tạo, tránh công việc bị áp lực.<br>
                    •   Bạn nên tìm cách cân bằng cuộc sống và chú trọng đến sức khỏe tinh thần.<br>
                    ";
                            break;
                        case 3;
                            $ketquaLg[] = "• Bạn Dễ căng thẳng, lo lắng quá mức về gia đình. <br>
                    •   Khi có gia đình bạn sẽ là người chiều chuộng và bảo bọc con cái rất nhiều<br>
                    •   Bạn nên tìm cách cân bằng cuộc sống và chú trọng đến sức khỏe tinh thần.<br>
                    ";
                            break;
                        default;
                            $ketquaLg[] = "• Thường lo lắng thái quá, dễ mắc các vấn đề về sức khỏe.<br>
                    •   Bạn nên tìm cách cân bằng cuộc sống và chú trọng đến sức khỏe tinh thần.<br>";
                            break;
                    }
                }
                if (isset($mangbieudo[7])) {
                    switch (intval($mangbieudo[7])) {
                        case 1;
                            $ketquaLg[] = "•	Trong cuộc đời bạn sẽ có lúc gặp phải 1 trong 3 thử thách lớn về sức khỏe, hoặc tài chính hoặc là tình cảm.<br>
                    •   Khi trải qua khó khăn, Bạn sẽ trưởng thành và mạnh mẽ hơn. Đó là cách để vượt ra khỏi vùng an toàn của bản thân.<br>
                    •   Hiểu rằng cuộc sống có nhiều bất ngờ. Hãy sống hết mình, đừng quá kỳ vọng và tập trung vào hạnh phúc. Mọi thứ đều có sự sắp đặt riêng.<br>
                    •   Học cách chấp nhận hy sinh một trong 3 khía cạnh kia nếu cần. Tập trung vào việc rèn luyện sức khỏe tinh thần.<br>
                    ";
                            break;
                        case 2;
                            $ketquaLg[] = "• Bạn là người rất chăm chỉ học hỏi và tiếp thu kiến thức. <br>
                    •   Trong cuộc đời bạn sẽ có lúc gặp phải 2 trong 3 thử thách lớn là sức khỏe, tài chính, tình cảm.<br>
                    •   Điều quan trọng là bạn cần học cách giữ tâm bình tĩnh trước mọi khó khăn. Luôn giữ thái độ tích cực.<br>
                    ";
                            break;
                        case 3;
                            $ketquaLg[] = "• Bạn thường gặp nhiều việc đáng buồn, mất mát trong cuộc sống <br>
                    •   Tuy nhiên, bản thân bạn lại ít bị ảnh hưởng bởi những mất mát đó mà người xung quanh mới chịu tác động nhiều hơn.<br>
                    •   Điều này là do bạn đã học cách chấp nhận và xem những khó khăn là thử thách để vượt qua.<br>
                    ";
                            break;
                        default;
                            $ketquaLg[] = "• Bạn thường gặp nhiều xui xẻo, rủi ro trong cuộc sống.<br>
                    •   Không chỉ bản thân bạn mà cả gia đình đều chịu ảnh hưởng và cảm thấy khó khăn, bế tắc.<br> 
                    •   Đây được xem như là quá trình tất yếu để bạn trưởng thành, rèn luyện mình.<br> 
                    ";
                            break;
                    }
                } else {
                    $ketquaLg[] = "•	 Những thử thách mà bạn cần trải qua và đối mặt chính là những bài học, nợ nghiệp mà kiếp trước họ chưa trả hết.<br>
                •   Do vậy ở cuộc sống kiếp này, điều bạn cần làm là cố gắng và mạnh mẽ để vượt qua và trưởng thành.<br>";
                }
                if (isset($mangbieudo[8])) {
                    switch (intval($mangbieudo[8])) {
                        case 1;
                            $ketquaLg[] = "•	Bạn là người cần chú ý đến việc ổn định cảm xúc và kiểm soát mong muốn tốt hơn. <br>
                    •   Nếu có thái độ tích cực và mục tiêu rõ ràng, cuộc sống sẽ rất tốt. <br>
                    •   Ngược lại, bạn sẽ dễ chán nản, thiếu ổn định.<br>
                    ";
                            break;
                        case 2;
                            $ketquaLg[] = "• Bạn là người dễ bị tác động bởi môi trường sống và điều kiện sống. <br>
                    •   Khi có thái độ tích cực, bạn rất cẩn thận, tỉ mỉ và ham học hỏi.<br>
                    •   Nhưng nếu môi trường kém,bạn dễ cảm thấy bị gò bó, ảnh hưởng xấu..<br>
                    ";
                            break;
                        case 3;
                            $ketquaLg[] = "• Chỉ cần đủ thấu hiểu và biết cách cải thiện nó thì dù quá khứ có như nào, hiện tại và tương lai bạn cũng sẽ trở nên tốt đẹp hơn. <br>
                    •   Hãy sống theo hướng tích cực, đời sống vui vẻ và dễ chịu, được mọi người yêu mến.<br>
                    •   Còn nếu sống theo hướng tiêu cực thì dễ mất cân bằng, cần được quan tâm để thay đổi.<br>
                    ";
                            break;
                        default;
                            $ketquaLg[] = "• Bạn là người rất năng động và có nhiều năng lượng sống.<br>
                    •   Bạn cần phải học cách kiểm soát và sắp xếp năng lượng để tránh lãng phí..<br> 
                    ";
                            break;
                    }
                }
                if (isset($mangbieudo[9])) {
                    switch (intval($mangbieudo[9])) {
                        case 1;
                            $ketquaLg[] = "•	Bạn có ba đặc điểm nổi bật là tham vọng, trách nhiệm và lý tưởng. <br>
                    •   Tuy nhiên cần chú trọng trau dồi bản thân, chứ không chỉ dựa vào sẵn có. <br>
                    ";
                            break;
                        case 2;
                            $ketquaLg[] = "• Bạn là người Rất quyết tâm và có lý tưởng rõ ràng. Nhưng dễ nhầm lẫn thực tại với lý tưởng. <br>
                    •   Bạn nên chú ý đến việc luyện tập khả năng diễn đạt để tăng khả năng thể hiện suy nghĩ, cảm xúc và ý tưởng của mình.<br>
                    ";
                            break;
                        case 3;
                            $ketquaLg[] = "• Bạn là người có nguồn năng lượng về lý tưởng, tham vọng cao hơn người khác nên dễ mất cân bằng cảm xúc. <br>
                    •   Hãy nhìn nhận cuộc sống, nhìn nhận mọi việc theo cách khách quan. Đừng quá áp đặt bản thân vào các mục tiêu quá cao tự làm khổ chính mình<br>
                    ";
                            break;
                        case 4;
                            $ketquaLg[] = "• Bạn là người đôi khi hay mộng mơ rời xa thực tế. <br>
                    •   Sẽ có những lúc bạn sân si , soi mói người khác, hãy quản trị tốt cảm xúc của mình để có một phiên bản tốt đẹp hơn<br>
                    ";
                            break;
                        default;
                            $ketquaLg[] = "• Bạn là người Hoặc sống trong thế giới tưởng tượng, hoặc thích dằn vặt người khác để thỏa mãn bản thân.<br> 
                    •   Hãy học cách để bản thân trở nên dịu dàng, ấm áp và thực tế hơn.<br> 
                    ";
                            break;
                    }
                } else {
                    $ketquaLg[] = "• Bạn là người có hoài bão, trách nhiệm, lý tưởng và ước mơ nhưng bạn thực sự cần nỗ lực nhiều hơn nữa <br>
                ";
                }
                // for ($i = 0; $i < count($mangSoNoiCam); $i++) {
                //     if ($mangSoNoiCam[$i] == 1) {
                //         $ketquaLg[] = "•	Bạn mạnh mẽ, có cá tính và độc lập. Đây là những tư chất của một thủ lĩnh mà ít ai có được.<br>";
                //     }
                //     if ($mangSoNoiCam[$i] == 2) {
                //         $ketquaLg[] = "•	Bạn có thiên hướng gia đình, luôn muốn gắn kết các thành viên và có trực giác chuẩn xác. Bạn cũng dễ bị tổn thương và hay “mít ướt”.<br>";
                //     }
                //     if ($mangSoNoiCam[$i] == 3) {
                //         $ketquaLg[] = "•	Bạn sáng tạo, vui vẻ và hoà đồng. Bạn giỏi ăn nói và mang đến nhiều niềm vui, nguồn cảm hứng cho mọi người.<br>";
                //     }
                //     if ($mangSoNoiCam[$i] == 4) {
                //         $ketquaLg[] = "•	Bạn trung thực, chân thành. Bạn cũng dễ rơi vào xu hướng bảo thủ, cố chấp và gây ra sự tranh luận, cãi vã.<br>";
                //     }
                //     if ($mangSoNoiCam[$i] == 5) {
                //         $ketquaLg[] = "•	Bạn là người thích đi chơi, đi du lịch và khám phá.<br>";
                //     }
                //     if ($mangSoNoiCam[$i] == 6) {
                //         $ketquaLg[] = "•	Bạn có xu hướng quan tâm, chăm sóc và giúp đỡ mọi người. Ban hay lo lắng quá đà khiến bản thân trở nên bao đồng và rơi vào bế tắc, mệt mỏi.<br>";
                //     }
                //     if ($mangSoNoiCam[$i] == 7) {
                //         $ketquaLg[] = "•	Bạn là người trí tuệ, logic và có tinh thần thép. Bạn sống tình cảm nhưng cũng không tránh khỏi những lúc “chua cay”.<br>";
                //     }
                //     if ($mangSoNoiCam[$i] == 8) {
                //         $ketquaLg[] = "•	Bạn giỏi kinh doanh và dành hầu hết thời gian, sức lực bản thân cho công việc.<br>";
                //     }
                //     if ($mangSoNoiCam[$i] == 9) {
                //         $ketquaLg[] = "•	Bạn là người độ lượng, phóng khoáng, biết quan tâm mọi người xung quanh nên rất được yêu mến và tin tưởng. Bạn cũng có có tố chất của một nhà lãnh đạo đa tài.<br>";
                //     }

                // }
                // for ($k = 0; $k < count($mangSoKhuyet); $k++) {
                //     if ($mangSoKhuyet[$k] == 1) {
                //         $ketquaLg[] = "•	Bạn gặp khó khăn nếu phải một mình đưa ra các quyết định trong công việc và cuộc sống.<br>";
                //     }
                //     if ($mangSoKhuyet[$k] == 3) {
                //         $ketquaLg[] = "•	Đôi khi bạn khó gần, dễ căng thẳng. Bạn hạn chế phát triển các mối quan hệ và không có khả năng truyền đạt thông tin.<br>";
                //     }
                //     if ($mangSoKhuyet[$k] == 4) {
                //         $ketquaLg[] = "•	Bạn thiếu sự tỉ mỉ, đôi khi làm việc không kế hoạch nên hiệu quả không cao.<br>";
                //     }
                //     if ($mangSoKhuyet[$k] == 5) {
                //         $ketquaLg[] = "•	Có phải cuộc sống của bạn đơn điệu, ít màu sắc và nhàm chán?<br>";
                //     }
                //     if ($mangSoKhuyet[$k] == 6) {
                //         $ketquaLg[] = "•	Bạn gặp khó khăn trong thể hiện cảm xúc. Bạn bị đánh giá là khô khan và điều đó khiến bạn ức chế, khó chịu.<br>";
                //     }
                //     if ($mangSoKhuyet[$k] == 7) {
                //         $ketquaLg[] = "•	Bạn sống quá thực tế, ít suy nghĩ sâu xa nên khó thấy được những gì thuộc về tư tưởng, triết lý.<br>";
                //     }
                //     if ($mangSoKhuyet[$k] == 8) {
                //         $ketquaLg[] = "•	Bạn không quan trọng vật chất và thực tiễn cuộc sống. Điều này gây ra không ít khó khăn về tài chính và sự thăng tiến của bạn.<br>";
                //     }
                //     if ($mangSoKhuyet[$k] == 9) {
                //         $ketquaLg[] = "•	Bạn có xu hướng nghĩ đến bản thân hơn, ít thể hiện vì lợi ích của cộng đồng.<br>";
                //     }
                // }
                $kqLuan = implode(", ", $ketquaLg); // In các phần tử cách nhau bằng dấu phẩy
                $kqluan1 = XuLyDuLieuBangGemini($kqLuan);
                if($kqluan1==""){
                        $kqluan1 = "Hiện tại do số người xem luận giải miễn phí quá lớn, chúng tôi đang quá tải, bạn vui lòng quay lại sau, xin cảm ơn!";
                        $thongtinLuanGiai = "";
                        $thongtinLuanGiai1 = "";
                        $thongtinLuanGiai2 = "";
                    }else {
	                    
                    }
            } else 
            {
                $thongtinLuanGiai2 = " <h4> Lưu ý: Đây là bài luận giải Cơ bản(Không phải chuyên sâu) về bản thân của $nameVN qua ngày tháng năm sinh được cung cấp.<br> Do có rất nhiều người đang chờ luận giải chuyên sâu nên tôi chỉ xem sơ qua và bài luận có thể chỉ đúng tầm 70% - 80% <br> </h4>";
                $kqluan = "";
                $kqLuan = tuviDotVn($ngay,$thang,$nam,$giochuyendoi,$gioiTinhStr,$name);
                if($kqLuan!=""){
                    $k = 0;
                    tl1:
                    $kqluan1 = LayNoiDungCungLuan($kqLuan);
                    if($kqluan1==""){
                        $k += 1;
                        if($k<3){
                            goto tl1;
                        }else
                        {
                            $kqluan = lichvannien365($ngay,$thang,$nam,$giochuyendoi,$gt,$name);
                            $dom = new DOMDocument();
                            libxml_use_internal_errors(true); // Bỏ qua các lỗi HTML không hợp lệ
                            $dom->loadHTML($kqluan);
                            libxml_clear_errors();
                            $xpath = new DOMXPath($dom);
                            $node = $xpath->query('//div[@class="livn-lgiai-content"]')->item(0);
                            $content="";
                            if ($node) {
                                $content1 = strip_tags($dom->saveHTML($node)); // Loại bỏ các thẻ HTML
                                $content = utf8_decode($content1);
                            }
                            if($content!=""){
                                $kqluan1 = XuLyDuLieuBangGemini($content);
                                if($kqluan1==""){
                                $kqluan1 = $kqluan;
                                }
                            }
                            else
                            {
                                $kqluan1 = thanglongdaoquan($ngay,$thang,$nam,$giochuyendoi,$gt,$name);
                            }
                        }
                    }
                }
                else 
                {
                    $kqluan = lichvannien365($ngay,$thang,$nam,$giochuyendoi,$gt,$name);
                    $dom = new DOMDocument();
                    libxml_use_internal_errors(true); // Bỏ qua các lỗi HTML không hợp lệ
                    $dom->loadHTML($kqluan);
                    libxml_clear_errors();

                    $xpath = new DOMXPath($dom);
                    $node = $xpath->query('//div[@class="livn-lgiai-content"]')->item(0);
                    $content="";
                    if ($node) {
                        $content1 = strip_tags($dom->saveHTML($node)); // Loại bỏ các thẻ HTML
                        $content = utf8_decode($content1);
                    }
                    if($content!=""){
                        $kqluan1 = XuLyDuLieuBangGemini($content);
                        if($kqluan1==""){
                        $kqluan1 = $kqluan;
                        }
                    }
                    else
                    {
                        $kqluan1 = thanglongdaoquan($ngay,$thang,$nam,$giochuyendoi,$gt,$name);
                    }
                }
            }
        } else {
            die;
        }
        $currentMinutes = date('H') * 60 + date('i')*60;
        $time = $currentMinutes.$ngay;
        $filename = "kqLuan".$ngay.$thang.$nam."_".$time.".html";
// Tạo nội dung HTML
        $htmlContent ="<!DOCTYPE html>
            <html lang='vi'>

            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Tiến Hành Luận Giải</title>
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        background: #f5f5f5;
                        font-family: Arial, sans-serif;
                    }

                    .container {
                        width: 80%;
                        margin: 50px auto;
                        margin: 50px auto;
                        background: #fff;
                        padding: 20px;
                        border-radius: 10px;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
                        /* Thêm background-image để chèn ảnh nền */
                        background-image: url('background.jpg');
                        /* Thay 'duong_dan_den_anh.jpg' bằng đường dẫn thực đến ảnh của bạn */
                        background-size: cover;
                        /* Chọn kích thước ảnh nền (cover, contain, auto, ...) */
                        background-position: center center;
                        /* Điều chỉnh vị trí ảnh nền */
                        background-repeat: no-repeat;
                        /* Tắt lặp lại ảnh nền */
                    }

                    h2 {
                        text-align: center;
                        color: #333;
                    }

                    label {
                        display: block;
                        margin: 10px 0;
                        font-size: 16px;
                    }

                    input {
                        width: calc(100% - 20px);
                        padding: 10px;
                        margin: 5px 0 15px;
                        box-sizing: border-box;
                    }

                    input[type='submit'] {
                        background-color: #4CAF50;
                        color: white;
                        cursor: pointer;
                    }

                    #result {
                        margin-top: 20px;
                        font-size: 18px;
                        font-weight: bold;
                        text-align: center;
                        color: #4CAF50;
                    }

                    @keyframes fade {
                        30% {
                            opacity: 0.5;
                        }
                    }

                    .view-more-btn {
                        background-color: #008CBA;
                        border: none;
                        color: white;
                        padding: 15px 32px;
                        text-align: center;
                        text-decoration: none;
                        display: inline-block;
                        font-size: 16px;
                        margin: auto;
                        cursor: pointer;
                        border-radius: 8px;
                        position: fixed;
                        left: 70%;
                        transform: translateX(-50%);
                        bottom: 1%;
                        animation: fade 1.5s step-start infinite;
                        white-space: nowrap;
                    }

                    .back-btn {
                        background-color: #008CBA;
                        border: none;
                        color: white;
                        padding: 15px 32px;
                        text-align: center;
                        text-decoration: none;
                        display: inline-block;
                        font-size: 16px;
                        margin: auto;
                        cursor: pointer;
                        border-radius: 8px;
                        position: fixed;
                        left: 30%;
                        transform: translateX(-50%);
                        bottom: 1%;
                        animation: fade 1.5s step-start infinite;
                        white-space: nowrap;
                    }
                </style>
            </head>

            <body>

                <div class='container'>
                <p> $thongtinLuanGiai </p>
                <p> $thongtinLuanGiai1 </p>
                <p> $thongtinLuanGiai2 </p>
                <p> $kqluan1 </p>
                </div>
                <button class='view-more-btn' id='viewMore'>Xem Thêm</button>
                <button class='back-btn' id='goBack'>Quay Lại</button>
                <script>
                    // Lấy button element
                    const btn = document.getElementById('viewMore');

                    // Thêm sự kiện click
                    btn.addEventListener('click', function () {

                        // Chuyển hướng đến trang chi tiết phí
                        window.location.href = 'vipMember.php';
                    });
                    // Lấy button element
                    const btn1 = document.getElementById('goBack');

                    // Thêm sự kiện click
                    btn1.addEventListener('click', function () {
                        // Chuyển hướng đến trang chi tiết phí
                        window.location.href = 'index.php';
                    });

                </script>
                <script>
        // Gửi yêu cầu xóa file khi đóng trình duyệt
        window.addEventListener('beforeunload', function () {
            navigator.sendBeacon('delete_file.php?file=" . urlencode($filename) . "');
        });
    </script>

            </body>

            </html>";
            // Lưu nội dung HTML vào file
            file_put_contents($filename, $htmlContent);

            // Tự động chuyển hướng đến trang mới
            header("Location: $filename");
            exit;
        ?>

------WebKitFormBoundarydjDc5L8sdwA7fRE2
Content-Disposition: form-data; name="overwrite"

0