<?php
header('Content-Type: text/html; charset=utf-8');
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $yearWatch = $_POST["namxem"];
    $day = $_POST["day"];
    $month = $_POST["month"];
    $year = $_POST["year"];
    $gender = $_POST["gender"];
    $gt = "";
    if($gender == "Chị"||$gender == "chị"){
        $gt = "false";
    }else{
        $gt = "true";
    }
    $hour = $_POST["hour"];
    $min = $_POST["minute"];
    $postData = http_build_query([
        "day" => $day,
        "month" => $month,
        "year" => $year,
        "gender" => $gt,
        "hour" => $hour,
        "minute"=> $min,
        "namxem" => $yearWatch 
    ]);

    $html = LayDsSao($day,$month,$year,$hour,$yearWatch,$gt);
    $tuViData = extractLaSo($html);
    //$string =  json_encode($tuViData, JSON_UNESCAPED_UNICODE);
    $string = luanGiaiTuHoaPhai($tuViData,$gt);
    //print_r($tuViData);
    $luanhoaky = LuanGiaiTuHoa($tuViData,"Hóa Kỵ");
    $luanhoakhoa = LuanGiaiTuHoa($tuViData,"Hóa Khoa");
    $luanhoaquyen = LuanGiaiTuHoa($tuViData,"Hóa Quyền");
    $luanhoaloc = LuanGiaiTuHoa($tuViData,"Hóa Lộc");
    //echo "Dưới đây là 1 số sự việc tiêu biểu diễn ra trong năm ngoái(NĂM 2024) của bạn, bạn chiêm nghiệm xem đúng không nhé!!! \r\nNăm 2024 là 1 năm mà bạn: \r\n.";
    $thaitue = LuanGiaiLuuNien ($tuViData, "L.Thái Tuế");
    $locton = LuanGiaiLuuNien ($tuViData, "L.Lộc Tồn");
    $thienma = LuanGiaiLuuNien ($tuViData, "L.Thiên Mã");
    $thienkhoc = LuanGiaiLuuNien ($tuViData, "L.Thiên Khốc");
    $thienhu = LuanGiaiLuuNien ($tuViData, "L.Thiên Hư");
    $tangmon = LuanGiaiLuuNien ($tuViData, "L.Tang Môn");
    $luangiai = "\r\n\r\n Luận giải Tổng Quát \r\n\r\n" . $luanhoakhoa ."\r\n" . $luanhoaquyen ."\r\n" . $luanhoaloc ."\r\n" . $luanhoaky ."\r\n\r\n Luận giải năm xem ".$yearWatch."\r\n".$thaitue . "\r\n" . $locton. "\r\n" . $thienma. "\r\n" . $thienkhoc. "\r\n" . $thienhu. "\r\n" . $tangmon;
    // Chia nhỏ nội dung thành các phần tối đa 1500 ký tự

   echo $luangiai ."\r\n\r\n Luận giải 12 cung \r\n\r\n" . $string;
 }
function extractLaSo($html) {
    libxml_use_internal_errors(true); // Bỏ warning
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html); // ép utf-8
    $xpath = new DOMXPath($dom);

    $cungNodes = $xpath->query('//td[contains(@class, "cung")]');

    $result = [];

    foreach ($cungNodes as $cungNode) {
        $cung = [];

        // Lấy tên cung
        $cungNameNode = $xpath->query('.//p[contains(@class, "text-sao-chinh-tinh")]', $cungNode);
        if ($cungNameNode->length > 0) {
            $cung['cung'] = trim($cungNameNode->item(0)->nodeValue);
        } else {
            $cung['cung'] = 'Không xác định';
        }

        // Lấy chính tinh
        $chinhTinhNodes = $xpath->query('.//p[contains(@class, "text-chinh-chinh")]', $cungNode);
        $chinhTinh = [];
        foreach ($chinhTinhNodes as $node) {
            $text = trim($node->textContent);
            $text = ltrim($text, '+-'); // ← loại bỏ dấu + hoặc -
            $text = preg_replace('/\s*\([^)]*\)\s*$/', '', $text);
            $chinhTinh[] = $text;

        }
        $cung['chinhTinh'] = $chinhTinh;

        // Lấy phụ tinh
        $phuTinhNodes = $xpath->query('.//div[contains(@class, "sao-tot")]//div[contains(@class, "text-sao-xau-tot")] | .//div[contains(@class, "sao-xau")]//div[contains(@class, "text-sao-xau-tot")]', $cungNode);
        $phuTinh = [];
        foreach ($phuTinhNodes as $node) {
            $text = trim($node->textContent);
            $phuTinh[] = preg_replace('/\s*\([^)]*\)\s*$/', '', $text);
        }
        $cung['phuTinh'] = $phuTinh;

        // Lấy tứ hóa phái
        $tuHoaPhai = [];
        $tuHoaNodes = $xpath->query('.//div[contains(@class, "cung-middle-tu-hoa-phai")]//p', $cungNode);
        foreach ($tuHoaNodes as $node) {
            $text = trim($node->textContent);
            if (preg_match('/Tự\s+Hóa\s+(Lộc|Quyền|Khoa|Kỵ)/u', $text, $matches)) {
                $sao = 'Tự Hóa ' . $matches[1];
                $tuHoaPhai[$sao] = 'Chính cung'; // không có chỉ định, bạn có thể chỉnh lại
            }
            if (preg_match('/(Hóa\s\w+)\s*-\s*(.+)/u', $text, $matches)) {
                $sao = trim($matches[1]);        // Ví dụ: Hóa Lộc
                $cungLienQuan = trim($matches[2]); // Ví dụ: Huynh Đệ
                $tuHoaPhai[$sao] = $cungLienQuan;
            }
        }
        $cung['tu_hoa_phai'] = $tuHoaPhai;

        $result[] = $cung;
    }

    return $result;
}
function LayDsSao($ngay, $thang, $nam, $gio, $namXem, $gt) {
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://tuvi.vn/la-so',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'name=&dayOfDOB=' . $ngay . '&monthOfDOB=' . $thang . '&yearOfDOB=' . $nam . '&calendar=true&timezone=1&hourOfDOB=' . $gio . '&minOfDOB=30&gender=' . $gt . '&viewYear=' . $namXem . '&viewMonth=3',
        CURLOPT_HTTPHEADER => array(
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'accept-language: en-US,en;q=0.9,vi;q=0.8',
            'cache-control: max-age=0',
            'content-type: application/x-www-form-urlencoded',
            'origin: https://tuvi.vn',
            'priority: u=0, i',
            'referer: https://tuvi.vn/lap-la-so-tu-vi',
            'sec-ch-ua: "Google Chrome";v="135", "Not-A.Brand";v="8", "Chromium";v="135"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
            'sec-fetch-dest: document',
            'sec-fetch-mode: navigate',
            'sec-fetch-site: same-origin',
            'sec-fetch-user: ?1',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}
//echo json_encode($tuViData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
function timSaoThuocCung($laSoData, $tenSao) {
    // Kết quả sẽ lưu danh sách các cung chứa ngôi sao
    $tenCung = '';
    
    // Duyệt qua từng cung trong lá số
    foreach ($laSoData as $cung) {
       
        
        // Tìm trong chính tinh
        if (isset($cung['chinhTinh']) && !empty($cung['chinhTinh'])) {
            // Duyệt qua từng sao trong chính tinh
            foreach ($cung['chinhTinh'] as $sao) {
                // Tách phần tên sao và trạng thái (nếu có)
                $tenSaoTach = trim(explode('(', $sao)[0]);
                if ($tenSaoTach === $tenSao) {
                    $tenCung = $cung['cung'];
                    break;
                }
            }
        }
        
        // Tìm trong phụ tinh nếu chưa tìm thấy trong chính tinh
        if (isset($cung['phuTinh']) && !empty($cung['phuTinh'])) {
            // Duyệt qua từng sao trong phụ tinh
            foreach ($cung['phuTinh'] as $sao) {
                // Tách phần tên sao và trạng thái (nếu có)
                $tenSaoTach = trim(explode('(', $sao)[0]);
                if ($tenSaoTach === $tenSao) {
                    $tenCung = $cung['cung'];
                    break;
                }
            }
        }
    }

    return $tenCung;
}

function kiemTraSaoTrongCung($cung, $tenSao) {
    // Kiểm tra trong chính tinh
    if (isset($cung['chinhTinh']) && !empty($cung['chinhTinh'])) {
        foreach ($cung['chinhTinh'] as $sao) {
            // Tách phần tên sao và trạng thái (nếu có)
            $tenSaoTach = trim(explode('(', $sao)[0]);
            if ($tenSaoTach === $tenSao) {
                return true;
            }
        }
    }
    
    // Kiểm tra trong phụ tinh
    if (isset($cung['phuTinh']) && !empty($cung['phuTinh'])) {
        foreach ($cung['phuTinh'] as $sao) {
            // Tách phần tên sao và trạng thái (nếu có)
            $tenSaoTach = trim(explode('(', $sao)[0]);
            if ($tenSaoTach === $tenSao) {
                return true;
            }
        }
    }
    
    // Không tìm thấy sao trong cung này
    return false;
}

function kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungIdentifier, $tenSao) {
    // Tìm cung theo định danh
    $cungTimThay = null;
    
    if (is_numeric($cungIdentifier)) {
        // Tìm theo chỉ số (0-11)
        $index = (int)$cungIdentifier;
        if ($index >= 0 && $index < count($laSoData)) {
            $cungTimThay = $laSoData[$index];
        }
    } else {
        // Tìm theo tên cung
        foreach ($laSoData as $cung) {
            if (isset($cung['cung']) && $cung['cung'] === $cungIdentifier) {
                $cungTimThay = $cung;
                break;
            }
        }
    }
    
    // Nếu không tìm thấy cung, trả về false
    if ($cungTimThay === null) {
        return false;
    }
    
    // Kiểm tra sao trong cung tìm thấy
    return kiemTraSaoTrongCung($cungTimThay, $tenSao);
}

function LuanGiaiLuuNien ($laSoData, $tenSao){

    $luanGiaiSaoLuu = "";
    $cungChuaSao = timSaoThuocCung($laSoData, $tenSao);
    $tenCungChuaSao = $cungChuaSao;

    // luận lưu thái tuế
    if($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Mệnh"){
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm đánh dấu sự thay đổi lớn về bản thân của bạn, Có cả sự bị động và chủ động thay đổi cho bản thân liên quan mọi mặt trong cuộc sống.";
        if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Kỵ")){
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Gặp nhiều thị phi, 1 năm có nhiều sự do dự trong suy tính.";
        }
        if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đào Hoa")){
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Các mối quan hệ với người khác giới khá tốt.";
        }
        if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Hình")&& kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Kỵ") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Hình") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Kình Dương")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có việc liên quan đến pháp luật hay cơ quan công quyền.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có người đàn ông giúp đỡ dẫn đường chỉ lỗi cho mình trong cuộc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Âm") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có người phụ nữ giúp đỡ dẫn đường chỉ lỗi cho mình trong cuộc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")|| (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có người người lừa gạt tiền bạc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Kình Dương")&& kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thất Sát")||kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao,"Kình Dương"))
            {
                $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n bị thương tật ở cơ thể.";
            }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đà La"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Bị ốm. phát hiện bệnh trong người.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm khó kiếm được tiền, cứ có tiền là lại có việc phải tiêu luôn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Mã"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n biến động khá nhiều, có nhiều việc khiến bản thân phải di chuyển chạy đi chạy lại.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Đồng"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm mọi chuyện sẽ thuận lợi giai đoạn đầu, nhưng về sau sẽ bế tắc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tuần")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Triệt"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n công việc có nhiều sự bế tắc, trắc trở.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm công việc làm ăn khá thuận lợi.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm mọi chuyện sẽ nhiều rối ren, điên đầu.";
        }
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Phụ Mẫu")
        {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Quan tâm, tương tác đến bố mẹ nhiều hơn, sức khỏe của bố mẹ năm đó không được ổn so với năm trước.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Kỵ"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm có sự cãi vã với bố mẹ.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Được bố giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Âm") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Được mẹ giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có sự bất hòa với song thân phụ mẫu.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Kình Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thất Sát") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Kình Dương"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n bố mẹ có khả năng bị thương tật ở cơ thể.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đà La"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Bố mẹ sức khỏe kém.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Mất 1 khoản tiền cho bố mẹ.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Mã"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n biến động khá nhiều, bố mẹ có nhiều việc khiến bản thân phải di chuyển chạy đi chạy lại.";
        }
        
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n có sự bất đồng quan điểm, sảy ra cãi vã với bố mẹ.";
        }
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Phúc Đức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm trong họ hàng có nhiều công việc khiến mình phải chạy đi chạy lại, sức khỏe của ông bà nội ngoại 2 bên giảm.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Lộc Tồn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  lo lắng nhiều về phúc phần, mồ mả, trong họ dễ có sự sửa sang từ đường hoặc mồ mả.";
        }
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Điền Trạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n có nhiều chuyện liên quan đến nhà cửa đất cát.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tiểu Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  buôn bán nhà cửa dễ dàng.";
        }
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Quan Lộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm phải Thu dọn, thu xếp để chuẩn bị cho một cuộc di chuyển ở công việc. Không chuyển việc thì cũng có việc phải đi xa, đi lại nhiều, công việc không được thuận lợi.";
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Nô Bộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm mà bạn bè dễ hỏi mượn tiền hoặc cho vay tiền, Năm này dễ làm ăn với người ngoài, tư vấn, giúp đỡ về tiền bạc, hùn vốn (nên cẩn trọng suy xét). Tình cảm có lúc rạn nứt sau lại bình thường .";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Kỵ"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm có sự cãi vã, thị phi với bạn bè.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Được 1 người đàn ông giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Âm") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Được 1 người phụ nữ giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n cẩn trọng về vấn đề tiền bạc, dễ bị bạn bè lừa gạt, hãm hại.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n mất tiền vì bạn bè(cho vay hoặc hùn vốn làm ăn).";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Mã"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n có bạn ở xa đến thăm, hoặc đi thăm bạn ,hoặc bạn bè có nhiều việc khiến bản thân phải di chuyển chạy đi chạy lại.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n là 1 năm mà bạn bè mang đến cho bạn nhiều chuyện thị phi.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Khốc") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n là 1 năm mà bạn bè mang đến cho bạn nhiều chuyện phải suy nghĩ và đưa ra quyết định.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Hư") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n bạn bè kết hợp làm ăn dễ hỏng việc , giúp đỡ bạn bè k thành, bạn bè k giúp đỡ đc gì.";
        }
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Thiên Di")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm mà bạn có nhiều công việc phải đi lại nhiều.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Kỵ"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n đi ra ngoài không được việc gặp sự cãi vã, thị phi.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n đi ra ngoài gặp được người đàn ông giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Âm") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Đi ra ngoài gặp được người phụ nữ giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n cẩn trọng về vấn đề tiền bạc, đi ra ngoài dễ bị lừa gạt, hãm hại mất mát về tiền bạc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n đi ra ngoài bị tiêu tốn nhiều tiền bạc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Mã"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n năm đó có việc phải đi xa.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n ra ngoài hay bị khẩu thiệt, thị phi, năm đó có thể bị kiện cáo, cãi vã.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Khốc") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n đi ra ngoài gặp nhiều chuyện phải suy nghĩ và đưa ra quyết định.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Hư")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n công việc đi ra ngoài có nhiều trắc chở, không được việc .";
        }
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Tật Ách")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm mà bạn cần xem trọng sức khỏe...không bệnh này thì tật kia, hay dính vào những sự việc không phải của mình không do mình gây ra nhưng phải chịu.";
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Tài Bạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm bạn dễ gặp thị phi cãi nhau về chuyện tiền bạc.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Được người đàn ông giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Âm") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Được người phụ nữ giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n cẩn trọng về vấn đề tiền bạc,dễ bị lừa gạt, hãm hại mất mát về tiền bạc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm khó kiếm được tiền, cứ có tiền là có việc phải tiêu .";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n đau đầu vì tiền.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Khốc") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n lo toan muộn phiền vì tiền bạc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Hư") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Mất tiền để lo việc nhưng kết quả không như mong đợi .";
        }
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Tử Tức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n con cái với bố mẹ sảy ra cãi nhau, bất đồng quan điểm.";
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Phu Thê")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n sẽ có sự thay đổi công việc hoặc lấy vợ lấy chồng nếu chưa lập gia đình,nếu chưa có người yêu thì dễ có người yêu hay bị hỏi han về vấn đề yêu đương và giục lập gia đình, trường hợp đương số có người yêu rồi thì dễ lục đục có thể là chia tay.";
    }
    else 
    if ($tenSao == "L.Thái Tuế" && $tenCungChuaSao == "Huynh Đệ")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có sự biến động trong mối quan hệ giữa anh em bạn bè.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Kỵ"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n có sự cãi vã lớn với anh em bạn bè, gây sứt mẻ tình cảm.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n được anh em trai giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Âm") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Được chị em gái giúp đỡ chỉ điểm hỗ trợ trong công việc làm ăn.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n cẩn trọng về vấn đề tiền bạc, không nên hùn vốn làm ăn với anh em bạn bè trong năm này.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n cẩn trọng trong việc đầu tư làm ăn với anh em trong gia đình, dễ bị thua lỗ.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Mã")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "L.Thiên Mã"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n năm đó vì ae mà phải chạy đi chạy lại nhiều.";
        }
        
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Khốc") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n anh em mang đến nhiều chuyện muộn phiền phải suy nghĩ nhiều.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Hư") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n anh em kết hợp làm ăn trong năm nay dễ hỏng việc , giúp đỡ anh em k thành, anh em k giúp đỡ đc gì .";
        }
    }

    // LUẬN LƯU LỘC TỒN

    if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Mệnh")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm bạn khá hanh thông về mặt tiền tài tiền bạc, hết tiền lại có, tuy nhiên bạn sẽ khá vất vả trong công việc, có nhiều phiền muộn trong công việc, tiền tài tiền bạc khiến bạn suy nghĩ rất nhiều.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Kỵ"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có may mắn tiền tài tiền bạc thì họa cũng theo đến cùng, nên chuyển đổi tiền thành dạng tài sản khác sẽ giữ được(vàng, xe cộ, đất cát).";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc")&&(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "L.Thái Tuế")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có người đàn ông giúp đỡ tiền bạc, dẫn đường chỉ lỗi cho mình trong cuộc sống.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Âm") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc") && (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "L.Thái Tuế")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có người phụ nữ giúp đỡ tiền bạc, dẫn đường chỉ lỗi cho mình trong cuộc sống.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")) || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Không"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n cẩn thận Có người người lừa gạt tiền bạc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm kiếm được tiền, tuy nhiên cứ có tiền là lại có việc phải tiêu luôn.";
        }
        
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tuần") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Triệt"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n công việc có nhiều sự bế tắc, trắc trở, hao tài tốn của.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm công việc làm ăn khá thuận lợi, nhiều cơ hội đến với bạn.";
        }
        
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Phúc Đức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm có nhiều sự may mắn đến với bạn, sẽ có 1 khoảng thời gian bạn kiếm được nhiều tiền trong 1 khoảng thời gian rất ngắn.Trong gia đình, dòng họ song hỷ lâm môn anh em cô gì con cháu đua nhau cưới chồng cưới vợ nhà đẻ thêm nhiều người.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "L.Thái Tuế"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  lo lắng nhiều về phúc phần, mồ mả, trong họ dễ có sự sửa sang từ đường hoặc mồ mả.";
        }
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Điền Trạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n có ý định sửa sang nhà cửa hoặc mua thêm đất cát, nếu mua đất cát thì được giá.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tiểu Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  buôn đi bán lại đất thuận lợi và sinh lời.";
        }
        if (!kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao") || !kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tiểu Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  buôn đi bán lại đất gặp khó khăn.";
        }
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Quan Lộc"||($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Tài Bạch"))
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có lộc hoặc được chỗ làm thưởng tiền, nếu làm tốt công việc của mình và ngược lại nếu không sẽ thành ra là bị phạt, đền.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Kỵ"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có may mắn tiền tài tiền bạc thì họa cũng theo đến cùng, nên chuyển đổi tiền thành dạng tài sản khác sẽ giữ được(vàng, xe cộ, đất cát).";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Dương") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc") && (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "L.Thái Tuế")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có người đàn ông giúp đỡ tiền bạc, dẫn đường chỉ lỗi cho mình trong công việc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Âm") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Hóa Lộc") && (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thái Tuế") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "L.Thái Tuế")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có người phụ nữ giúp đỡ tiền bạc, dẫn đường chỉ lỗi cho mình trong công việc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")) || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Không"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Có người người lừa gạt tiền bạc.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n cứ có tiền là lại có việc phải tiêu luôn.";
        }

        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tuần") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Triệt"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n công việc có nhiều sự bế tắc, trắc trở, hao tài tốn của.";
        }
        
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Nô Bộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm mà bạn ít giao du với bạn bè hơn so với những năm trước .";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Không") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Địa Kiếp")))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n cẩn trọng về vấn đề tiền bạc, dễ bị bạn bè lừa gạt, hãm hại.";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Đại Hao"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n mất tiền vì bạn bè(cho vay hoặc hùn vốn làm ăn).";
        }
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Mã"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n có bạn ở xa đến thăm.";
        }
        
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Thiên Di")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm mà bạn đi ra ngoài làm có nhiều cơ hội kiếm tiền,ký kết hợp đồng,cơ hội làm ăn, có người giúp đỡ tuy nhiên cũng dễ bị người khác hãm hại, làm việc hay đi lại dễ bị tai nạn .";
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Tật Ách")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu .  "ít ốm đau, năm nay có ốm cũng gặp thầy hay thuốc giỏi, tuy nhiên các bệnh mà phát hiện từ năm trước thì năm nay cần lưu ý kiểm tra lại.";
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Tử Tức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm chủ về việc hơi khó mang bầu, nếu mang bầu đc thì rất là tốt, chủ về con cái khỏe mạnh, công việc cũng hanh thông hơn.";
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Phu Thê")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n vk ck phối hợp làm ăn tốt, ng ck ng vk kiếm tiền tốt, bên nội bên ngoại cho khoản tiền lớn, nếu chưa lập ra đình thì kết hôn sẽ hôi khó, đi lám xa vẫn dễ có tiền.";
    }
    else if ($tenSao == "L.Lộc Tồn" && $tenCungChuaSao == "Huynh Đệ")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n kết hợp làm ăn với Huynh Đệ rất tốt, tài lộc kém nhưng tư tưởng năm đó rất tốt.";
    }

    // luận thiên mã

    if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Mệnh")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm bạn có nhiều sự thay đổi lớn, đi xa, đi lại nhiều.";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Điền Trạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n dễ thay đổi môi trường sống(sửa sang nhà nếu k có nhu cầu di chuyển chỗ ở).";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Quan Lộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n dễ thay đổi công việc, hoặc vị trí chức tước trong công việc mình đang làm.";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Tài Bạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n Là 1 năm mà bạn dễ phải chi tiêu khối lượng tiền lớn, lưu thông dòng tiền lớn.";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Nô Bộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n vì bạn vì bè có việc phải nhờ mình thì đi xa, đi gần giúp bạn.";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Thiên Di")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm mà bạn đi lại nhiều,đi xa, môi trường bên ngoài tác động thúc ép bạn phải thay đổi bản thân.";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Tật Ách")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm mà bạn phải di chuyển nhiều, sức khỏe cũng có nhiều thay đổi, nên chú ý sức khỏe.";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Tử Tức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm dễ đưa con đi xa hoặc đến nơi con cái ở xa để chăm sóc con cháu.";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Phu Thê")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm có sự trái tính trái nết với vợ chồng, thay đổi trong mối quan hệ vợ chồng.";
    }
    else if ($tenSao == "L.Thiên Mã" && $tenCungChuaSao == "Phụ Mẫu")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n có sự tương tác với bố mẹ nhiều hơn, bố mẹ đi lại nhiều.";
    }

    //luận lưu thiên khốc

    if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Mệnh")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n 1 năm mà lúc nào bạn cũng đắn đo suy nghĩ nhiều.";
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Phúc Đức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n chú ý sức khỏe ông bà nội ngoại 2 bên.";
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Quan Lộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  khổ tâm lao lực đầu óc về công việc khá là nhiều.";
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Nô Bộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  bạn bè đưa đến cho bạn nhiều phiền muộn và mang đến cho bạn phải suy nghĩ để đưa ra quyết định gì đó .";
        
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Thiên Di")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  đi ra ngoài đường làm ăn dễ có nhiều chuyện muộn phiền đến với mình trong năm này.";
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Tử Tức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  trong năm này có nhiều sự bất đồng quan điểm với con cái, con cái gây muộn phiền cho bản thân, phải đắn đo suy nghĩ đưa ra các quyết định liên quan đến con cái.";
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Phu Thê")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n trong năm này có nhiều sự bất đồng quan điểm với người hôn phối, vợ chồng gây muộn phiền cho bản thân, phải đắn đo suy nghĩ đưa ra các quyết định liên quan đến nửa kia.";
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Huynh Đệ")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n anh em trong nhà đưa đến cho bạn nhiều phiền muộn và mang đến cho bạn phải suy nghĩ để đưa ra quyết định gì đó.";
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Tài Bạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  phiền muộn lo toan về tiền bạc, lúc nào cũng đau đầu để đưa ra quyết định xem nên đầu tư tiền như nào trong làm ăn.";
    }
    else if ($tenSao == "L.Thiên Khốc" && $tenCungChuaSao == "Phụ Mẫu")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n năm đó sức khỏe bố mẹ kém.";
    }


    // Luận lưu thiên hư
    if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Mệnh")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  1 năm sẽ có nhiều chuyện không được như ý.";
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Phúc Đức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  chú ý sức khỏe bố mẹ ông bà nội ngoại 2 bên.";
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Điền Trạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  nhà cửa dễ hỏng hóc, phải sửa sang hoặc xây mới, sức khỏe mọi người trong nhà khá kém.";
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Quan Lộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  công việc k như ý, nhiều việc nghĩ làm đc nhưng mà gặp rất nhiều khó khăn.";
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Nô Bộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  bạn bè kết hợp làm ăn dễ hỏng việc , giúp đỡ bạn bè k thành, bạn bè k giúp đỡ đc gì .";

    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Thiên Di")
    {
    $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  đi công tác, đi xa dễ gặp trắc chở, không được việc.";
    if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Thiên Mã")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "L.Thiên Mã"))
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n xe cộ đi lại dễ bị hỏng hóc.";
    }
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Tật Ách")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  sức khỏe kém, bệnh tật thì k thành hình nhưng mà cơ thể cảm thấy mệt mỏi yếu kém, có nhiều phiền muộn trong đó.";
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Tử Tức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  trong năm này con cái dễ bị ốm đau.";
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Phu Thê")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n trong năm này có nhiều sự bất đồng quan điểm với người hôn phối, vợ chồng gây muộn phiền cho bản thân.";
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Tài Bạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  năm nay sẽ mất mát 1 khoản tiền mà không đem lại lợi ích gì cho bạn.";
    }
    else if ($tenSao == "L.Thiên Hư" && $tenCungChuaSao == "Phụ Mẫu")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n năm đó sức khỏe bố mẹ kém.";
    }


    // Luận Lưu Tang Môn

    if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Mệnh")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  1 năm mà bạn suy tính mong muốn nhiều thứ nhưng bất thành, có sự muộn phiền.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Phúc Đức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  chú ý sức khỏe bố mẹ ông bà nội ngoại 2 bên.";
        if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $tenCungChuaSao, "Tang Môn"))
        {
            $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n trong họ có tang.";
        }
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Điền Trạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  1 năm dễ dễ hao hụt nhà cửa, dễ thay đổi chỗ ở, chỗ làm, con cái dễ ốm đau, làm giấy tờ về đất cát dễ bị kiện cáo.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Quan Lộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  sẽ có sự luân chuyển, nhưng luân chuyển bị động, phiền muộn bế tắc muốn thay đổi trong công viêc nhưng rất khó để thay đổi.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Nô Bộc")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  bạn bè mang đến cho bạn nhiều phiền muộn, rắc rối.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Thiên Di")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  1 năm mà bạn hay phải đi ra ngoài xử lý công việc nhưng k được việc.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Tật Ách")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  sức khỏe bố mẹ kém, dễ ốm đau.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Tử Tức")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  trong năm này con cái dễ bị ốm đau.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Phu Thê")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  2 vợ chồng có nhiều phiền muộn với nhau trong năm đó, nếu có thiên mã đóng vào thì 2 vợ chồng năm đó sẽ có công việc đi cùng nhau đi xa.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Tài Bạch")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n  năm nay sẽ suy nghĩ về mặt tiền tài tiền bạc rất nhiều, và phải sử dụng nhiều tiền để lo việc nhưng lại không kiếm về được nhiều.";
    }
    else if ($tenSao == "L.Tang Môn" && $tenCungChuaSao == "Phụ Mẫu")
    {
        $luanGiaiSaoLuu = $luanGiaiSaoLuu . "\r\n trong họ có tang(tang xa).";
    }

    return $luanGiaiSaoLuu;
}

function splitTextIntoChunks($text, $maxLength = 1000) {
    $sentences = preg_split('/(?<=[.!?])\s+/', $text); // Chia theo câu
    $chunks = [];
    $currentChunk = ".";

    foreach ($sentences as $sentence) {
        if (strlen($currentChunk . " " . $sentence) <= $maxLength) {
            $currentChunk .= " " . $sentence;
        } else {
            $chunks[] = trim($currentChunk);
            $currentChunk = $sentence;
        }
    }
    if (!empty($currentChunk)) {
        $chunks[] = trim($currentChunk);
    }

    return $chunks;
}
// Set header để trình duyệt hiểu là UTF-8

function LuanGiaiTuHoa($laSoData, $tenSao){
    $luanTuHoaNamSinh = "";
    $cungChuaSao = timSaoThuocCung($laSoData, $tenSao);
    $cungAnSaoHoaLoc = timSaoThuocCung($laSoData, "Hóa Lộc");
    $cungAnSaoHoaKy = timSaoThuocCung($laSoData, "Hóa Kỵ");
    $cungAnSaoTaPhu = timSaoThuocCung($laSoData, "Tả Phù");
    $cungAnSaoHuuBat = timSaoThuocCung($laSoData, "Hữu Bật");
    $cungAnSaoDaoHoa = timSaoThuocCung($laSoData, "Đào Hoa");
    //Luận Hóa Kỵ
    if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Mệnh"){
        $luanTuHoaNamSinh =  $luanTuHoaNamSinh . " Trong cuộc sống, bất kể các chuyện  vui mừng, oán hận, than thở, buồn rầu ... thảy đều do bản thân bạn âm thầm nhận chịu; cũng vì áp lực quá lớn như vậy, nên tâm tình của bạn cũng thay đổi lên xuống với biên độ rất lớn." .
        "\r\n vì sướng khổ đều do bản thân nhận chịu, ít chia sẻ với người khác, nên cũng không chịu mang cách suy nghĩ của mình thố lộ với người khác. Cá tính của bạn khá \"thu vào\" (không giỏi biểu đạt tình cảm), do đó bạn sẽ có tâm trạng cô độc" .
        "\r\n Vì có tâm trạng cô độc khó diễn tả như vậy, nên thường thường sẽ khiến bạn có trạng thái tâm lí đa nghi, càng không giỏi (mà thật ra cũng không ưa) giao tế. Cho nên rất khó thấy người này có nét mặt tươi cười.Chỉ khi nào bạn có được cảm giác thành tựu thì mới thấy được vé mặt rạng rỡ." .
        "\r\n lúc bạn giao du với người khác rất dễ cảm thấy tự ti trước, cùng với tính cách thu vào khiến bạn phải thay đổi trạng thái tâm lí để gánh vác trọng trách (bạn là người có năng lực đảm đương, gánh vác), cho nên sẽ dễ từ mặc cảm tự ti biến thành tự phụ, thường thấy bạn có trạng thái tâm lí lưỡng cực hóa." .
        "\r\n Người khác sẽ đánh giá bạn là người quá độ cẩn thận và bảo thủ. Thông thường bạn sẽ không chủ động giao du với ai, trừ phi họ là người rất được tín nhiệm, nếu không ít thấy qua lại với nhau, nhưng một khi được bạn tín nhiệm, liệt vào danh sách \"bạn bè\", có thể nói là rất vinh hạnh." .
        "\r\n Bạn là mẫu người có duyên sâu nặng và lâu dài với bạn bè, cho nên thà chịu tổn hại chớ không chịu chiếm lợi thế, và ít khi làm tổn thương bạn bè" .
        "\r\n Bạn là người trong cuộc sống đa phần sẽ cảm thấy bản thân bị áp lực về tâm lí, tuy nhiên bạn khá chủ quan (nhưng không nhất định sẽ biểu đạt ý chủ quan của mình), cũng sẽ có trạng thái tâm lí lợi ki" .
        "\r\n Bạn trong giai đoạn đi học sẽ đến nơi khác ở, hoặc là vì nhu cầu công tác mà đi xa, cũng có khả năng di cư tha hương, ở nước ngoài; nhưng cho dù lúc trẻ phiêu bạt ở bên ngoài, cuối cùng cũng \"lá rụng về cội\"." .
        "\r\n Do trong tính cách có chút thu vào nên thường hay có cảm giác không an toàn, lại muốn trong một lúc gánh vác rất nhiều chuyện, do đó thường thường sẽ bức bách bản thân có tài năng trác việt(có năng lực hoặc tài năng vượt trội), kì vọng bản thân sẽ dựa vào tài năng trác việt này mà được an thân, nhưng cũng do có tài năng trác việt này mà làm tăng thêm cơ hội \"dịch động\", đi xa." .
        "\r\n Bạn sẽ chịu trách nhiệm về hạnh phúc của người yêu, sẽ gánh vác trách nhiệm làm cho người phối ngẫu vui vẻ, hạnh phúc" .
        "\r\n Bạn sẽ thiếu tự tin và có cách suy nghĩ bi quan, do đó sinh ra cảm giác sai lầm là không được cha thương. Vì vậy, đôi khi bạn có lòng nghi ngờ đối với tình thân, tình yêu và tình bạn.";

    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Phụ Mẫu"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . " cha mẹ bạn sống với nhau không được hòa hợp, thậm chí còn có nhiều điềm báo khác. Như quan hệ giữa cha và mẹ không đơn thuần, có thể một trong hai người có thêm người khác." .
        "\r\n Những khi bất đồng quan điểm với bố mẹ, sẽ có những lúc bạn có suy nghĩ trong song thân phụ mẫu có một người không hơn bạn, bất luận về học lực, về kiến thức, hay ở phương diện nhân sinh quan, bạn đều cảm thấy cách suy nghĩ của mình đúng hơn, sáng suốt hơn." .
        "\r\n Tuy bạn luôn thấy có khoảng cách thế hệ giữa bạn và cha mẹ nhưng lại rất quan tâm đến cha mẹ. Điều này có thể dẫn đến hiện tượng vừa thương yêu vừa oán giận: có thể yêu thương một người trong khi oán giận người kia. Cũng có trường hợp đối xử với cha mẹ không như nhau, ví dụ như đặc biệt gần gũi với cha nhưng xa lánh mẹ, hoặc ngược lại. " .
        "\r\n Hồi niên thiếu phần nhiều học lực của bạn sẽ không cao, hoặc việc học có thời gian đình chỉ tạm thời" .
        "\r\n Thông thường, bạn có suy nghĩ sẽ chọn nghề nghiệp khó áp dụng những kiến thức đã học ở trường. bạn sẽ tìm con đường khác hoặc học cái mới để vận dụng vào công việc. Có thể trong quá trình học tập, bạn đã từng nỗ lực học hai lĩnh vực khác nhau." .
        "\r\n Bạn hay gặp phiền phức về giấy tờ, dễ dính kiện tụng thị phi" .
        "\r\n Bạn không có duyên với cấp trên. Nói một cách khác, vì có ý tượng đối đãi đơn phương, nên cấp trên sẽ mang lại rất nhiều áp lực cho bạn." .
        "\r\n Bạn là người quan tâm lo lắng đến con cái, con cái cũng không thực sự quá gần gũi với bạn" .
        "\r\n Khi bạn tranh cãi với 1 ai đó, rất dễ vì khó kiềm chế cảm xúc mà dùng những lời lẽ hà khắc" .
        "\r\n Sức khỏe của bạn từ nhỏ đã không thật sự được tốt";

    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Phúc Đức"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn là người không biết tự điều chỉnh bản thân, áp lực cũng khó có cách giải tỏa, ngay cả lúc có đời sống vật chất sung túc bạn vẫn có một cuộc sống với chất lượng kém." .
        "\r\n Bạn sẽ cho rằng mình không đủ năng lực ứng phó trong công việc; về tiền bạc và vấn đề kiếm tiền, bạn có ý thức lo lắng rất cao và cảm giác bất an nghiêm trọng; bạn sẽ có tư duy tiêu cực và có thái độ vồ vập trong việc kiếm tiền." .
        "\r\n Bạn dễ có quan niệm tiêu cực khi phán đoán sự việc, là người bi quan. Ngoài ra, lúc động tâm khởi niệm cũng dễ có thái độ hoài nghi; gặp sự cố trở ngại, thất lợi, dễ có cách suy nghĩ tiêu cực và âu sầu buồn bực" .
        "\r\n Bạn lo lắng rằng mình thiếu kiến thức và sợ phán đoán sai lầm, nên bạn khá trầm lặng. bạn cho rằng nói nhiều dễ mắc sai lầm, nên thường ít nói và chỉ phát biểu ý kiến khi thực sự cần thiết. Điều này không có nghĩa là bạn thiếu chủ kiến, nhưng đôi khi bạn suy nghĩ bế tắc và tự nhốt mình vào những vấn đề không thể giải quyết, dẫn đến hao tổn công sức một cách vô ích." .
        "\r\n Bạn thường tạo được ấn tượng là người thực thà trong mắt người khác, khi đối diện với nguy cơ, bạn thường tiếp nhận và chịu đựng thay vì phản ứng mạnh mẽ." .
        "\r\n Khi gặp khó khăn trong công việc bạn sẽ có xu hướng căng thẳng lo âu" .
        "\r\n bạn là người thực dụng, tin rằng cần tiến từng bước, phải rất nỗ lực, dùng học thức và hi sinh thời gian mới được trả thù lao, chứ không phải nghĩ việc kiếm tiền giàu có dựa vào thời vận tốt hay không" .
        "\r\n Bạn ở bên ngoài sẽ có biểu hiện rất kém, khó phát huy tri thức sở trường một cách hợp lí trong hoàn cảnh khó khăn, cho nên nếu được phái ra ở bên ngoài công tác, e rằng khó có biểu hiện tốt." .
        "\r\n Tình trạng hôn nhân không được tốt, cảm thấy cuộc sống hôn nhân áp lực, mâu thuẫn hiểu lầm với người phối ngẫu" .
        "\r\n Bạn hay lo lắng, chịu áp lực lớn từ công việc cuộc sống nên ảnh hưởng rất nhiều đến sức khỏe, bạn nên bồi dưỡng sở thích, thị hiếu cho thích đáng, và thay đổi nhân sinh quan theo hướng lạc quan hơn";
    
    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Điền Trạch"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn là người luôn lo lắng về việc có thể kiếm được tiền liên tục hay không và thường thiếu cảm giác an toàn về tài chính (luôn thấy thiếu tiền), nên bạn có tư duy thực dụng rất mạnh." .
                    "\r\n Trong cuộc sống hằng ngày bạn thường giấu tiền riêng để dùng trong những lúc cần thiết.";
                
                if($cungAnSaoHoaLoc == "Mệnh"||$cungAnSaoHoaLoc=="Tài Bạch"||$cungAnSaoHoaLoc=="Quan Lộc"|| $cungAnSaoHoaLoc == "Tật Ách" || $cungAnSaoHoaLoc == "Điền Trạch" || $cungAnSaoHoaLoc == "Phúc Đức")
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người sẽ vì cảm giác không an toàn về tài chính mà liều mạng kiếm tiền, quyết tâm tích lũy tiền bạc, nên sẽ có tiềm lực làm cho gia sản trở nên to tát";
                }
                else
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn không phải là người quản lý tiền bạc giỏi";
                }
                $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n bạn khi gặp sự cố xảy ra đột ngột sẽ dễ có phản ứng sai lầm, thậm chí dễ nổi giận." .
                    "\r\n Căn phòng bạn ở thường không được gọn gàng cho lắm, khá bừa bộn, truy cứu nguyên nhân, phần nhiều là vì bạn mua hay sưu tập rất nhiều đồ đạc, gia cụ vật phẩm, mà xếp đặt bài trí rất lộn xộn; còn có thói quen thanh lí đồ linh tinh theo kiểu bỏ mà không vứt đi" .
                    "\r\n Bạn có bất động sản nhưng quyền sở hữu không rõ ràng (hai ba người cùng đứng tên ..) hoặc có tình trạng chuyển dời bất động sản rất phiền phức, mà bất động sản còn dễ bị rò rỉ nước, đường ống nước không thông ...";
                if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hỏa Tinh")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Linh Tinh"))
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\n đề phòng bất động sản dễ bị cháy";
                }
                else if (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Thiên Hình"))
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\n đề phòng bất động sản dễ bị trộm ghé thăm";
                }
                $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn nên thường xuyên kiểm tra sức khỏe ở những bệnh viện lớn,vì đi khám bệnh sẽ khó tìm ra đúng bệnh, để lâu dài dễ mắc các bệnh mãn tính" .
                    "\r\n Thời niên thiếu cuộc sống gia đình của bạn thường xảy ra rất nhiều vấn đề, thông thường là do hôn nhân của bố mẹ không được hòa hợp " .
                    "\r\n Tuy bạn là người tích cực lo cho cuộc sống gia đình nhưng tình cảm với các con sẽ không quá thân thiết gần gũi, thiếu cảm giác ấm áp" .
                    "\r\n Trong công việc Có thể thấy hoàn cảnh môi trường làm việc của bạn không được lý tưởng. Nếu không gặp vấn đề về tài chính, thì sẽ có tình trạng rối loạn, chế độ nhân sự không rõ ràng, hoặc ông chủ ngầm giở quỷ kế.";
    
    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Quan Lộc"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn là 1 người luôn muốn học hỏi và bồi đắp bản thân, bạn thường đặt ra cho mình những mục tiêu, hoặc quá chú trọng vào lĩnh vực mà mình đang quan tâm trong công việc";
        if ($cungAnSaoHoaLoc == "Mệnh" || $cungAnSaoHoaLoc == "Tài bạch" || $cungAnSaoHoaLoc == "Quan lộc" || $cungAnSaoHoaLoc == "Tật ách" || $cungAnSaoHoaLoc == "Điền trạch" || $cungAnSaoHoaLoc == "Phúc đức")
        {
            $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người có lí tưởng, còn có tinh thần trách nhiệm và biết được hướng nỗ lực.Chính vì vậy bất kể trong học vấn hay công việc, thường thấy bạn bỏ ra rất nhiều thời gian, tinh thần vào việc bồi đắp bản thân, nâng địa vị lên; có khuynh hướng làm việc điên cuồng.";
        }
        else
        {
            $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn dễ tự mâu thuẫn với chính mình, thường muốn nỗ lực nhưng lại thiếu lòng tự tin, muốn đông muốn tây, mục tiêu không rõ ràng, khiến làm việc thiếu hiệu quả. Bạn lại phải bỏ ra nhiều thời gian và tinh thần hơn vào việc học hay việc làm, do đó bị anh hưởng ngược lại, làm cho bạn có cảm giác thiếu an toàn, thiếu sức bật trong công việc, sự nghiệp";
        }
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người có khuynh hướng kết hôn muộn, tập chung nhiều thời gian cho công việc cả trước và sau hôn nhân, nên đôi khi sẽ khiến cho người yêu, người phối ngẫu của bạn cảm thấy bạn là người vô tâm" .
            "\r\n Trong cuộc sống bạ luôn muốn giải quyết mọi vấn đề 1 cách hoàn hảo tuyệt đối,Thường thường người ngoài nhìn thì thấy đương sự đã làm rất khá, nhưng bản thân bạn vẫn cho rằng còn quá tệ. Nên đôi khi bạn hay chú trọng về những tiểu tiết lặt vặt";
    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Nô Bộc"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn thường thường là kết giao bạn bè có địa vị xã hội không cao; hoặc là người có cách xử thế, cách suy nghĩ có vấn đề, khác với quan niệm thông thường của thế tục. Bạn bè dễ mang lại phiền phức cho bạn" .
        "\r\n Trogn cuộc sống ở một mức độ nào đó, bạn sẽ kì vọng ở sự trợ giúp, hợp tác của thân hữu, hoặc kì vọng bạn bè mang lại điều tốt cho mình, tuy nhiên bạn thường thấy hụt hẫng bởi sự đáp lại kỳ vọng của bạn bè không như mong muốn" .
        "\r\n Có 1 số mối quan hệ không đem lại lợi ích, niềm vui gì cho bạn nhưng bạn lại thấy có chỗ khó xừ, bắt buộc phải kết giao, phải quan tâm, hoặc có những mối quan hệ khiến bạn suy nghĩ, lẽ ra mình đối sử với bạn bè không nên quá nhiệt tình tâm huyết đến vậy" .
        "\r\n Bạn thường có tình cảm không hòa hợp với bạn bè, không chỉ không nhận được lợi ích gì mà còn bị tổn thất tiền bạc, bị tổn thương về tình cảm." .
        "\r\n Đối tượng hợp tác làm ăn của bạn phần lớn thiếu thành tín, khiến bạn rất dễ phải gánh chịu hậu quả thay cho bạn bè trong chuyện hợp tác. Đặc biệt là trong việc vay mượn tiền bạc, nguy cơ mất tiền mà không thu hồi được là rất cao, gây hao tốn tài sản và chuốc thêm phiền phức." .
        "\r\n thời niên thiếu sức khỏe của bạn không thật sự tốt" .
        "\r\n cha mẹ của bạn không hòa hợp, hoặc tính cách của họ không đơn thuần. Một trong hai người có hoàn cảnh làm việc hoặc sự nghiệp khá gian khổ, dẫn đến cuộc sống gia đình không vui vẻ.";

    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Thiên Di"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn luôn cảm thấy tương lai đầy mơ hồ và nhiều khó khăn, Bạn hay phải đối mặt với những áp lực, tình huống thất lợi, và trở ngại trùng trùng; dù đang ở trong đại vận bình an vô sự, bạn cũng dễ vì kinh nghiệm đã trải qua mà cảm thấy lo lắng không yên về tiền đồ tương lai, không cách nào diễn tả được." .
        "\r\n Khả năng điều chỉnh tâm trang của bạn cũng không tốt, bạn không thích 1 thứ gì đó quá lâu nên khó có thể dựa vào những sở thích đó để giải tỏa hay điều chỉnh tâm trạng" .
        "\r\n Về người phối ngẫu của bạn, người đó rất mong muốn kiếm được nhiều tiền, dùng nhiều phương thức khác nhau để kiếm tiền, điều đó làm cho bạn cảm thấy bất an" .
        "\r\n Mối tình đầu của bạn tạo áp lực khá lớn cho bạn đồng thời nhân tố hành vi đan xen phức tạp giữa hai người cũng dễ liên quan đến tiền bạc, khiến cho bạn cảm thấy hai người sẽ không có cùng một tương lai. Nên tình cảm đầu đời của bạn phần nhiều là đau buồn." .
        "\r\n khi đi xa đi học, đi làm việc hay đi công tác xa bạn luôn muốn quay về nhà, nhưng về nhà rồi lại muốn đi xa, ở bên ngoài ít có bạ tri kỷ" .
        "\r\n Bạn là người quan hệ giao tế không được tốt, lòng tự tôn cao và \"da mặt quá mỏng\"" .
        "\r\n Thời niên thiếu của bạn là khoảng thời gian mà sức khỏe của bố hoặc mẹ bạn không được tốt";
                
    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Tật Ách"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn có tinh thần ẩn nhẫn và sức chịu đựng khá mạnh, thường chịu đựng buồn phiền hoặc thua thiệt mà không bộc lộ ra ngoài, tuy nhiên tâm tư thường nóng nảy và không yên định" .
        "\r\n bạn là người luôn biết che giấu cảm xúc, là mẫu người điển hình của trường hợp nuốt nước mắt mà phải mỉm cười." .
        "\r\n  là dễ mắc bệnh mạn tính, hoặc bệnh tật không rõ ràng, đi khám bệnh thường không ra bệnh ngay" .
        "\r\n Bạn dễ bị mặc cảm tự ti, hành vi có thiên hướng bảo thủ. Vì lòng tự trọng khiến bạn khó biểu hiện tình cảm của mình, có lúc hay nghi ngờ người khác." .
        "\r\n trước khi kết hôn, người phối ngẫu đã từng thất bại trong tình yêu, mà còn là mối tình khắc cốt ghi tâm; thông thường là bạn không biết câu chuyện quá khứ này" .
        "\r\n Đối với cha, tình cảm của bạn vừa gần gũi vừa xa cách, đôi khi khó xử và mâu thuẫn." .
        "\r\n Bạn khó giữ được điền sản của cha ông để lại nhất là đến đại vận hóa kỵ bị xung kích" .
        "\r\n Tình cảm của cha mẹ bạn không mấy hòa hợp, nguyên nhân có thể là lúc trẻ, tình cảm của cha mẹ rất phức tạp ";
                
    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Tài Bạch"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . " Bạn luôn luôn có cảm giác là lúc nào mình cũng thiếu tiền, tiền trong túi bạn không bao giờ là đủ,  đối với tài phú bạn có dục vọng theo đuổi cực cao " .
        "\r\n Bạn kiếm được tiền, hay có được tài phú trong hoàn cảnh thị phi. Vì vậy, bạn có năng lực kiếm tiền khá cao, có bản lãnh nhìn ra cơ hội kiếm tiền mà người khác không nhìn ra." .
        "\r\n Bạn cũng hay gặp tình huống không được thuận lợi toại ý về tài vận, hoặc bị thất nghiệp trong một thời gian ngắn" .
        "\r\n Bạn muốn tìm đủ mọi cách để kiếm tiền, bản thân luôn có cảm giác thiếu an toàn về tiền bạc, thường mong muốn có thật nhiều tiền để ổn định cuộc sống, vì vậy có tiền hay không có tiền bạn cũng đều than nghèo" .
        "\r\n cuộc đời bạn cũng tiêu tốn rất nhiều thời gian trong việc kiếm tiền, bạn hay lấy việc có tiền hay không có tiền đế làm tiêu chuẩn đánh giá địa vị hay thành tựu cao thấp." .
        "\r\n ý thức lo xa và tằm lí nặng được mất của bạn khá nặng, thường thấy vì vấn đề tiền bạc mà tâm thần không yên." .
        "\r\n người phối ngẫu sẽ cho rằng bạn là người đa sầu đa cảm, còn nhận định bạn không quan tâm chăm sóc, chủ nghĩa cá nhân quá nặng, trong cảm giác của người phối ngẫu, bạn là người chỉ tốt với bản thân." .
        "\r\n bạn rất biết kiếm tiền, còn rất biết tiêu xài tiền, thích mua đồ xa xỉ hay là hưởng thụ, nhưng bạn thường thường chỉ tiêu xài tiền cho bản thân và người thân mà thôi. Sau khi tiêu xài tiền bạn sẽ cảm thấy xót ruột";
    
    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Tử Tức"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Sức khỏe của con cái không được tốt, sinh con trai hay con gái đều gặp khó khăn" .
        "\r\n Bạn mong muốn con cái nỗ lực học hành, còn trông mong con cái cống hiến cho gia đình, nhưng thường thường con cái rất khó đạt yêu cầu của bạn, mà nhiều còn trở thành tai hạn." .
        "\r\n Bạn thường cư xử và mong đợi con cái theo một cách nhất định, nhưng thường thì con cái lại có thái độ và hành xử khác so với mong đợi đó. Vì vậy, bạn không chỉ cảm thấy có khoảng cách với con cái, mà mối quan hệ giữa bạn và con cái cũng không được hòa thuận, gây ra nhiều khó khăn." .
        "\r\n Vì con cái mà ảnh hưởng đến sức khỏe" .
        "\r\n Bạn rất muốn đầu tư làm ăn, hợp tác để kiếm tiền tuy nhiên lúc hợp tác làm ăn với người khác thường thường sẽ khiến bạn mất cả chì lẫn chài, không những vốn đầu tư lỗ mất, mà cuộc hợp tác làm ăn có thể giống như cái hố sâu, cần phải liên tục đổ của cải vào" .
        "\r\n Bạn là người kỳ vọng nhiều vào con cái, cũng rất thương và chiều chuộng con cái" .
        "\r\n Bạn khó giữ bất động sản, ngồi nhà đầu tiên bạn mua thì rất khó ở lâu trong ngôi nhà đó";

    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Phu Thê"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn có ý trông đợi người phối ngẫu gánh vác trách nhiện thay cho bạn trong cuộc sống, người bạn đời của bạn chịu nhiều áp lực, gánh nặng" .
        "\r\n người phối ngẫu sẽ hướng nội, trầm mặc, hay đa nghi hơn bạn; nhưng không có nghĩa là người phối ngẫu vốn như vậy, mà đây là bạn cảm thấy như vậy." .
        "\r\n Bạn không biết cách biểu đạt tình yêu của mình đối với người phối ngẫu, Bạn còn không yên tâm về người phối ngẫu, hay ghen tuông. Nhưng nếu so sánh, thì người phối ngẫu cũng khó có tình ý với người khác, mà e rằng họ còn ghen tuông hơn bạn. " .
        "\r\n Đối với bạn, hôn nhân cũng giống như cái hố sâu chứa đầy bất mãn, khó có đối tượng nào làm cho bạn vừa ý, thỏa mãn hoàn toàn" .
        "\r\n Trong cuộc sống hôn nhân, sẽ có những lúc bạn cảm thấy giữa hai người như là đang \"mắc nợ nhau\"; thường thường là muốn đứt đoạn mà không đứt đoạn, oán nhau nhưng lại không cách nào rời xa nhau." .
        "\r\n Gia thế hoặc gia cảnh của người phối ngẫu không được tốt cho lắm, không tốt bằng gia thế gia cảnh của bạn. Người phối ngẫu có nhiều chuyện cần phải gánh vác" .
        "\r\n Trong thời kì đi học, thì chuyện bạn bè trai gái sẽ ảnh hưởng nghiêm trọng đến ý nguyện học tập của bản thân bạn, cũng ảnh hưởng đến thành tích học tập và sự thành tựu về học vấn" .
        "\r\n Sau khi kết hôn, sẽ có ảnh hưởng xấu đối với sự nghiệp của bạn, bạn cần phai trải qua một lần chịu ảnh hưởng xấu, mới có thể tìm ra hướng đi mới. Hơn nữa, có thể sau khi kết hôn bạn giường như là 1 trụ cột gia đình.";


    }else if($tenSao == "Hóa Kỵ"&&$cungChuaSao=="Huynh Đệ"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn là người rất quan tâm đến anh em trong gia đình, tuy nhiên cách ứng xử và cách sử dụng ngôn từ của bạn đối với anh chị em trong gia đình thường thiếu hòa nhã dẫn đến mối quan hệ  với anh chị em không được hòa hợp" .
        "\r\n anh chị em là những người dễ gây ra phiền phức, bạn thường vì anh em mà đau đầu nhức óc. Nhưng lúc anh chị em có nạn, bạn nhất định sẽ trợ giúp, nhưng lại dễ rơi vào tình trạng càng giúp thì càng bận rộn." .
        "\r\n Trong nhà có nhiều anh chị em" .
        "\r\n Lúc điều độ tiền bạc, của cải sẽ có vấn đề, nếu là người kinh doanh buôn bán thì cần phải dự phòng trường hợp điều chuyển vốn liếng mất linh hoạt, còn người đi làm hưởng lương thì dễ túng quẫn." .
        "\r\n bạn không nên có sự qua lại tiền bạc với anh chị em, thường thường sẽ có tổn thất về tiền bạc. Hơn nữa, trong đời bạn dễ xảy ra một lan \"phá tài\" lớn, do đó nếu muốn cho anh chị em mượn tiền, tốt nhất nên chuẩn bị tâm lí." .
        "\r\n Nếu bạn cầm cố bất động sản để vay tiền thì dễ gặp được tình trạng vay số tiền vượt quá giá trị tài sản thế chấp" .
        "\r\n Bạn giao du với người khác giới sẽ có lúc đột nhiên đứt đoạn, không liên lạc nhau, không qua lại với nhau; hoặc có một khoảng thời gian không giao du bạn khác giới.ư" .
        "\r\n Giữa bạn và người phối ngẫu (hoặc người yêu) sẽ dễ vì sự giao lưu không được tốt mà xảy ra tình trạng hiểu lầm; cho nên, bạn trong thời kì giao du muốn tiến tới hôn nhân sẽ gặp trở lực trùng trùng, thường hay xảy ra sự cố một cách đột ngột.Sau khi hôn nhân thành lập, giữa hai người dễ xảy ra tranh chấp cãi vã, thường thấy tình trạng dùng ngôn từ chua cay hoặc khắc bạc mắng nhiếc nhau. " .
        "\r\n Thể chất của bạn không được tốt, lúc mắc bệnh có sức đề kháng yếu. Lúc bạn đến tuổi trung niên, cần phải kiểm tra sức khỏe định kì, để dự phòng các chứng cấp tính." .
        "\r\n Giữa bạn và bạn bè tốt nhất là không nên qua lại tiền bạc, nếu không sẽ rất dễ xảy ra phiền phức, không những chủ về bản thân bị tổn thất mà tình bạn cũng dễ bị tổn hại";
    
    }
    //luận hóa khoa
    if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Mệnh"){
        $luanTuHoaNamSinh =  $luanTuHoaNamSinh . "Bề ngoài bản thân bạn trông rất có khí chất,cho người ngoài ấn tượng văn nhã, sở học thâm hậu; cũng khiến cho người ta có cảm giác bạn là một người mẫu mực, hoặc con nhà gia giáo." .
        "\r\n Người khác có ấn tượng với bạn là một người hiếu học, học lực ở mức trung bình khá trở lên, rất sáng dạ, ưa tiếp cận những sự vật liên quan đến phương diện văn học hoặc nghệ thuật, cũng thích hợp với học thuật nghiên cứu, có thiên phú về văn nghệ, tuy nhiên có tính chủ quan và thích so sánh sở học với người khác" .
        "\r\n Bạn thường có thể giữ tâm trạng bình yên và tìm cách thư giãn, nhưng khi gặp khó khăn, bạn thường tập trung vào bản thân và ít quan tâm đến người khác. Bạn biết cách thao túng tâm lý người khác theo ý của mình, để đạt được những gì mà bạn mong muốn." .
        "\r\n Bạn thường gặp may mắn trong cuộc sống, khi đối mặt với nguy hiểm, bạn thường tỏ ra thận trọng và không mạo hiểm quá mức, thường lựa chọn bảo toàn sức khỏe và không tham gia vào những hành động liều lĩnh." .
        "\r\n Bạn thường sử dụng kỹ năng chuyên môn và quan điểm tích cực về cuộc sống, sử dụng lời nói hoa mỹ để che giấu nhược điểm của bản thân." .
        "\r\n Bạn dễ nuối tiếc tình xưa, nhớ chuyện cũ, thích gió yên sóng lặng, có thói quen thay đổi dần dần, bạn thường lấy kinh nghiệm bản thân làm trung tâm" .
        "\r\n Bạn thường có khả năng giảm bớt căng thẳng trong không khí gia đình, có thể giải quyết những tình huống mâu thuẫn và đưa gia đình trở lại với nhau. Bạn thường đóng vai trò là người trung gian giữa các thành viên trong gia đình, đóng góp vào sự hòa thuận và hiệp nhất." .
        "\r\n Trong hôn nhân, bạn thường sử dụng sự kiên định và quan điểm của mình để ảnh hưởng đến người phối ngẫu làm họ cảm thấy an tâm " .
        "\r\n Con cái của bạn thường rất gắn bó với gia đình và thường sống hòa thuận và vui vẻ trong môi trường gia đình. bạn thường xem gia đình như một nơi an toàn, nơi bạn có thể tránh khỏi những khó khăn và vấn đề. Khi bạn gặp vấn đề, điều đầu tiên bạn nghĩ đến là trò chuyện và chia sẻ với gia đình. Con cái thường có nhiều kỳ vọng đối với bạn và mong muốn sự hỗ trợ và hiểu biết từ phía bạn.";

    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Phụ Mẫu"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . " cha mẹ bạn là người hiểu biết hoặc có giáo dục, ít nhất nhìn bề ngoài cũng có khí chất, có công phu hàm dưỡng, hoặc là có tố chất học thuật, có năng lực chuyên môn." .
        "\r\n cha mẹ sống với nhau hòa mục, là tấm gương để bạn noi theo, trong cách nhìn của bạn, cha mẹ là hình tượng một đôi vợ chồng rất tốt đẹp." .
        "\r\n gặp bất cứ vấn đề gì, nếu bạn đều nghe theo ý kiến của cha mẹ, có tác dụng giúp ích bạn rất nhiều." .
        "\r\n Trong công việc bạn rất được lòng cấp trên, được cấp trên yêu mến, nâng đỡ nhiều" .
        "\r\n Con của bạn có chỉ số EQ cao,rất biết tiết chế bản thân, có bản lãnh đế tiến lên";

    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Phúc Đức"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn là người rất hiểu biết ý vị tình cảm trong cuộc sống, phẩm vị của thị hiếu cũng cao, có thể dựa vào lạc thú cuộc sống để điều chỉnh tâm tình của bản thân cho thích hợp" .
        "\r\n Do có tính cẩn thận, suy nghĩ kỹ lưỡng nên bạn rất thích hợp làm những công việc như lập kế hoạch, tham mưu, phụ tá, thư kí thân tín quan trọng. Bạn là mẫu người lí trí, ít thấy tình trạng vì lợi lộc mà che mờ lí trí, trong ứng sử biết cương nhu đúng lúc" .
        "\r\n Bạn là người có chỉ số EQ cao,  khó nổi giận một cách kịch liệt, cũng khó bị khích tướng, nguyên nhân lớn nhất là vì họ biết cảm thấy đủ. Cho nên bạn sẽ không theo đuổi những điều cao xa, mà rất biết cách tự điều chỉnh bản thân, khiến cho thân tâm được điều hòa một cách hợp lí." .
        "\r\n vợ chồng bạn sẽ sống với nhau hòa hợp, quan hệ của hai người là sự phối hợp rất ăn ý, ít có chuyện tranh chấp cãi vã." .
        "\r\n lúc ra bên ngoài, hay lúc đi công tác xa, bạn đều có biểu hiện khá tốt, đồng thời còn có thể phát huy sở học, năng lực, nghề chuyên môn một cách hợp lí và đúng mực.";
    
    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Điền Trạch"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn có năng lực quản lí tiền bạc, sử dụng tài sản, bất động sản hợp lí, là nhân tài quản lí tài sản và quy hoạch bất động sản." .
        "\r\n Bạn còn có thói quen giữ tiền riêng, năng lực giữ tiền cực tốt." .
        "\r\n nhà ở trang hoàng, bài trí thanh nhã, trong nhà lúc nào cũng sạch sẽ, chỉnh tề, nhưng không phải trang trí kiểu khoa trương hào nhoáng theo thói tục, ý tứ rất tinh xảo." .
        "\r\n Bạn và người nhà sống với nhau hòa hợp vui vẻ; nhà ở hiếm khi bị trộm cướp, hỏa tai, rò rỉ nước; hơn nữa, còn có thể sống hòa mục với xóm giềng." .
        "\r\n Bạn là mẫu người lí trí; dù bề ngoài trông giống như người xấu, hoặc tính khi có vẻ như không được tốt lắm, nhưng bạn là người mặt ác mà tâm thiện." .
        "\r\n Phụ nữ chú ý bệnh về phụ khoa sinh đẻ" .
        "\r\n bạn có mắc bệnh tật thì cũng hay gặp thầy giỏi thuốc hay chữa trị kịp thời" .
        "\r\n con cái bạn rất hướng về gia đình, cho rằng gia đình là nơi bảo vệ chúng, là nơi con cái đặt kì vọng, nên luôn duy trì mối ràng buộc hòa hợp với gia đình." .
        "\r\n Bố mẹ sống hòa thuận, ít sảy ra cãi vã" .
        "\r\n Bạn làm việc trong một môi trường vững chắc, là cơ cấu nổi tiếng hoặc có hình tượng tốt đẹp trong xã hội.";

    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Quan Lộc"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Trong công việc bạn là người có thực lực bởi lẽ trong việc học hành hay tiếp thu kiến thức, bạn đều tuần tự tiến từng bước, hấp thu và tiêu hóa kiến thức với trạng thái tâm lí vững vàng, không hấp tấp; bạn mong muốn sau khi hiểu biết thông suốt sẽ mang ra vận dụng" .
        "\r\n Bạn rất có hứng thú đối với học vấn và kiến thức, có tinh thần tự tìm hiểu, nghiên cứu một cách bền bỉ; mà mục đích tìm hiểu, nghiên cứu không phải là vì muốn nâng cao địa vị, phần nhiều xuất phát từ niềm hứng thú của bản thân" .
        "\r\n Bạn  có EQ khá cao, lúc làm việc đều có kế hoạch trước, tính logic và tính suy luận của bạn cũng nhất trí. Nói một cách tổng thể, công việc giao cho bạn đều được xử lí thỏa đáng và chu đáo." .
        "\r\n Bạn là mẫu người quang minh lỗi lạc. Bất luận trong môi trường làm việc hay lúc còn đi học, thậm chí trong cuộc sống thường ngày, bạn cũng đều sinh hoạt rất có quy luật, có quy củ, cho nên thường được mọi người xem là mẫu mực." .
        "\r\n Bạn có nội tâm khá ổn định, bên ngoài dù sóng to gió lớn cũng rất khó dao động, là người có tâm tính bình hòa, biết tiến thoái, không thích gây sự với ai, là người rất biết \"minh triết bảo thân\"." .
        "\r\n Trong môi trường làm việc, bạn thường thu hút sự tin tưởng của cấp trên không chỉ bởi ổn định và đáng tin cậy của mình mà còn bởi sự khôn ngoan trong việc bảo vệ bản thân. Bạn thường được coi là người \"phù hộ\" trong công việc, giúp giảm bớt rủi ro và bảo vệ môi trường làm việc. Ngoài ra, do có khiếu thiếu toan tính cho tương lai, bạn thường thích làm việc trong môi trường lớn hơn vì cảm thấy an toàn hơn." .
        "\r\n Bạn thích được người khác khen ngợi và khẳng đinh, nếu \"Gãi Đúng Chỗ Ngứa\" có thể sẽ khiến bạn vui rất lâu" .
        "\r\n người phối ngẫu sử dụng lý trí, suy nghĩ logic để lập kế hoạch cho tương lai, sự nghiệp hoặc các mục tiêu dài hạn của bản thân hoặc của gia đình, không để cảm xúc ảnh hưởng đến quá trình này.";
    

    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Nô Bộc"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn bè qua lại của bạn đại đa số là những người có học thức, có thân phận địa vị" .
        "\r\n Bạn bè tính tình khá bình hòa, không có ý tranh giành hay tranh cường hiếu thắng với bạn" .
        "\r\n Với bạn bè, bạn phần nhiều là trò chuyện trao đổi với nhau tâm đắc về phương diện học vấn, tri thức,sức khỏe, hoặc tham dự hiệp hội đoàn thể liên quan đến việc học hành." .
        "\r\n sự trợ giúp của bạn bè, phần nhiều là đề nghị hay chỉ dẫn có thiện ý, mà rất ít khi trợ giúp về tài chính. Nói một cách tương đối, giữa bạn bè cùng rất ít có sự qua lại về lợi ích." .
        "\r\n Bạn thiếu thái độ đối ứng thận trọng, cũng thiếu sự bảo vệ trong thời gian đầu, cần phải mất nhiêu thời gian để đối mặt với cành khó khăn và nguy cơ" .
        "\r\n cha mẹ là người phúc hậu, tâm tính bình hòa, hoặc là người hiểu biết, có học thức, cuộc sống song thân ổn định" .
        "\r\n Về phương diện con cái thì con cái sống nề nếp ngăn nắp, công việc ổn định";
    
    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Thiên Di"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . " Bạn đối với tiền đồ tương lai đều có tính toán hợp lí, không rơi vào mơ mộng cao xa, ít có cách suy nghĩ không tưởng, mà có thể vạch ra kế hoạch một cách hợp lí." .
        "\r\n Bạn là người khá khiêm tốn, đi công tác xa, hay đi chơi bạn đều là người lên kế hoạch chi tiết chứ không phải hứng đâu làm đó, là người có duyên với người ở bên ngoài" .
        "\r\n người phối ngẫu có sở học tình chuyên, lời nói cử chỉ của họ rất có phép tắc, tôn trọng lẫn nhau, Người phối ngẫu ít có khả năng tự kinh doanh" .
        "\r\n Bạn làm cho người ta có cảm giác bạn là người có ngăn nắp, phần nhiều đều biểu đạt được cách suy nghĩ của mình một cách hữu hiệu, để lại ấn tượng tốt nơi người khác, quan hệ giao tế rất khả quan.";
    
    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Tật Ách"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn được đánh giá là người thông minh, sáng dạ trong việc học hành" .
        "\r\n tâm trạng mừng giận buồn vui của bạn có biên độ không quá lớn, ít nhất về biểu hiện là như vậy; cũng không dễ dàng nổi giận hay vui mừng quá đáng, so với người bình thường là thiên nặng lí tính." .
        "\r\n Trong công việc bạn làm việc có trình tự quy củ rõ ràng" .
        "\r\n Bạn có thân hình vừa phải không quá gầy cũng không quá mập, sức khỏe ít có vấn đề" .
        "\r\n Về phương diện bất động sản, phần lớn bạn đều giữ được bất động sản, mà phẩm chất bất động sản và quyền sở hữu cũng ít có vấn đề." .
        "\r\n Cha của bạn là người thông tình đạt lý, bạn thường chịu ảnh hưởng từ người cha nhiều" .
        "\r\n Người phối ngẫu của bạn trước khi kết hôn đã từng có bạn thân khác giới,  đoạn tình cảm này phần nhiều là phát ở \"chữ tình\" mà dừng lại ở \"chữ lễ\", mà không có quan hệ nam nữ \"thân mật\" trước hôn nhân. ";
    
    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Tài Bạch"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . " Bạn rất chú ý lời nói cử chỉ của mình, và mong muốn hành vi biểu hiện của mình ở các phương diện đều tuân thủ quy củ" .
        "\r\n Bạn có trình độ cao hơn người khác cùng cấp trong môi trường làm việc" .
        "\r\n Bạn ít khi rơi vào tình huống không tìm được việc làm; tức lúc mất việc bạn sẽ mau chóng tìm được công việc mới.Tuy nhiên lúc làm việc đôi khi bạn khá cứng nhắc, bảo thủ tuân thủ quy củ hơi thiếu linh động" .
        "\r\n Bạn có quý nhân giúp đỡ trong lúc tìm việc làm, duy trì mối ràng buộc trong công việc, do luôn có tinh thần trách nhiệm, tận tâm với công việc được giao phó nên luôn có quý nhân giúp đỡ" .
        "\r\n Bạn là người không bao giờ bẻ cong phép tắc, không tiêu xài tiền loạn xạ, cũng không đầu cơ, không mạo hiếm" .
        "\r\n Bạn thích hợp với nghề văn, nghề dạy học, hoặc làm việc trong cơ cấu thuộc loại lớn, tức là công việc có tính ổn định cao." .
        "\r\n Bạn luôn được người yêu người phối ngẫu tín nhiệm, tôn trọng";
    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Tử Tức"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "con cái khá ưu tú, tâm tính, tư duy đều bình ổn; hơn nữa, phần nhiều là tướng mạo thanh tú" .
        "\r\n Bạn cũng sẽ tận tâm tận lực che chở bảo vệ con cái, trong quá trình sinh đẻ phần nhiều cũng được \"gặp hung hóa cát\", con cái ít gặp nạn." .
        "\r\n Người trong nhà đều trông mong con cái thành tựu, gia đình bạn không có kì vọng cao xa, cũng sẽ không mong ước hay theo đuổi cuộc sống xa hoa; vì vậy an định, bình dị, thực thà, không lo phiền và không có tai họa là những mong ước chủ yếu." .
        "\r\n lúc tiến hành đầu tư phần nhiều bạn dựa vào lí trí và rất thực tế. việc hùn vốn làm ăn được bình yên thuận lợi, giữa những người hợp tác có chế độ hợp lí và tôn trọng lẫn nhau, đối tượng hợp tác cũng là người bình dị, quá trình hợp tác cũng thấy có hòa khí." .
        "\r\n Bạn bè qua lại đều là người gần gũi sốt sắng, thấu tình đạt lý" .
        "\r\n cha mẹ có thái độ đối nhân xử thế hòa hợp" .
        "\r\n Bạn biết cách dùng tiền hợp lí, có quan niệm thu chi cân đối, có kế hoạch chi xuất rõ ràng" .
        "\r\n Con cái giống như là quý nhân của đời bạn, khi có con cuộc sống hôn nhân ổn định hơn";

    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Phu Thê"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn là người che chở cho người phối ngẫu" .
        "\r\n người phối ngẫu có học vấn, hoặc học lực cao hơn bạn; khí chất và diện mạo bề ngoài, hoặc thậm chí kể cả cách nói năng của họ, đều văn nhã hơn bạn." .
        "\r\n luận về tiền bạc thì người phối ngẫu không hơn bạn được, mức độ giàu có của gia đình cùng không tốt hơn bạn, nhưng có thể đoán định là có gia cảnh thanh bạch." .
        "\r\n Sau hôn nhân, nhờ có người phối ngẫu mà hình tượng, danh vọng, cho đến địa vị xã hội của hai người đều được nâng lên. luận cho đến ngọn nguồn thì người phối ngẫu và bạn là quý nhân của nhau." .
        "\r\n người phối ngẫu sẽ không can dự vào sự nghiệp của bạn, trừ khi đó là ý của bạn đề ra" .
        "\r\n Bạn đi ra ngoài làm ăn xa gặp thuận lợi hơn làm ở gần nhà" .
        "\r\n Bạn là người sống tình cảm";

    }else if($tenSao == "Hóa Khoa"&&$cungChuaSao=="Huynh Đệ"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn không phải là con độc nhất mà có anh chị em" .
        "\r\n Trong số các anh chị em có người có học lực, khí chất cao hơn bạn" .
        "\r\n anh chị em rất thương yêu nhau; dù có xảy ra chuyện tranh chấp cãi vã với anh chị em, rốt cuộc vẫn có thể vãn hồi." .
        "\r\n lúc tuổi trẻ tiền túi và tiền để dành của bạn đã hơn những người bạn cùng trang lứa; ngoài ra, bạn quản lí tiền bạc khá cấn thận, hợp lí." .
        "\r\n mẹ là người thông tình đạt lí, xử lí việc nhà cũng có trật tự trước sau rõ ràng." .
        "\r\n song thân của \"một nửa kia\" là người thông tình đạt lí, không thù cựu, sẽ không làm khó bạn." .
        "\r\n Các bậc trưởng bối chi giúp đỡ cho bạn về tình cảm, chớ không phải về tiền bạc; lúc bậc trưởng bối giúp đỡ tiền bạc thì hầu hết đã là lúc bạn rất nguy cấp." .
        "\r\n Bạn là người có sức khỏe tốt, ít khi mắc các bệnh vặt như cảm..." .
        "\r\n Mối quan hệ vợ chồng của bạn khá là tốt, giữa hai người có trò chuyện trao đổi với nhau, giữa vợ chồng có sự hiểu biết nhau" .
        "\r\n lúc bạn muốn điều phối lại bất động sản của mình, thông thường đều có thể rời khỏi tay mình với giá cả họp lí, mà không bị tổn thất, không bị ảnh hưởng lên xuống của thị trường" .
        "\r\n bản thân bạn không có năng lực xử lí nguy cơ, mà thường thường đến gian đoạn thử hai mới được người khác trợ giúp, ít nhiêu bạn cũng có mặc cảm \"bất túc\"." .
        "\r\n Bạn là quý nhân của anh chị em, sau khi trợ giúp và bảo vệ anh chị em rồi bạn mới có được cảnh anh em trợ giúp nhau.";    
    }

    // Luận Hóa Quyền
    if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Mệnh"){
        $luanTuHoaNamSinh =  $luanTuHoaNamSinh . "Bạn có tinh thần trách nhiệm và chịu được áp lực, muốn có địa vị xã hội, người khác đánh giá bạn có vẻ bề ngoài khá nghiêm túc." .
        "\r\n Tính cách của bạn khó mà tâm bình khí hòa, tâm cảnh của bạn khó tĩnh lặng, sẽ có lối suy nghĩ vội vàng, dễ bị kích động, trong lòng hay lo lắng không yên, và hay nhận định một cách hấp tấp." .
        "\r\n Bạn có suy nghĩ tích cực, quyết đoán, hành động nhanh chóng, nhưng lại rất chủ quan. Bạn có ý thức về bản thân rất mạnh, dễ lấy \"cái tôi\" làm trung tâm, không quan tâm hoặc xem thường quan điểm của người khác, tự cho mình là đúng, ngoan cố và khó thông cảm với người khác." .
        "\r\n Bạn thích nắm quyền, thường có cảm giác mình cao hơn người khác một bậc, sẽ dễ bành trướng \"cái tôi\", thiếu tính nhẫn nại, không thích nhận lỗi, nhưng cũng nhờ tính cách không chịu thua này mà bạn tự đốc thúc bản thân rất mạnh." .
        "\r\n Bạn có 1 vận trình cuộc đời với nhiều thăng trầm, khó tránh bôn ba gặp nhiều sóng gió" .
        "\r\n Bạn có tính vội vàng, hấp tấp, dễ bị kích động, sẽ ảnh hưởng đến trạng thái tâm lí của \"một nửa kia\"; bạn luôn muốn dùng thời gian ngắn nhất để tạo ra hiệu quá lớn nhất; sau khi kết hôn, cuộc sống chung của hai người sẽ thấy căng thẳng" .
        "\r\n Bản thân bạn là người chủ chốt trong gia đình; nói một cách tương đối, lúc quan hệ giữa cha mẹ và anh em hơi căng thẳng thì bạn thường là người đứng ra hòa giải." .
        "\r\n Bạn là người rất uy nghiêm, khiến con cái ở trong nhà bị áp lực rất nặng, tuy con cái hướng về gia đình, nhưng cũng có phần sợ trong đó, nên ít khi ở nhà.";

    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Phụ Mẫu"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "bản thân bạn sẽ thiếu động lực, thiếu hành động thực tiễn để đạt thành mục tiêu, cần phải dựa vào sự trợ giúp của người nhà, cha mẹ. Địa vị của bạn ở trong nhà là ở thế yếu." .
        "\r\n Bạn cảm thấy cha mẹ mình quá độc đoán, dẫn đến sự ngăn cách giữa hai thế hệ hoặc khó trò chuyện với nhau." .
        "\r\n Bạn là người mạnh mẽ để đối mặt với vấn đề, thành người không chịu cúi dầu trước trở ngại hay thất bại." .
        "\r\n Xét về tiền bạc, bạn hoặc thân nhân của bạn thường nhạy cảm với tiền và có khát vọng kiếm tiền. Hơn nữa, phương thức kiếm tiền của họ thường liên quan đến cha mẹ của bạn" .
        "\r\n Bạn bè hoặc thân nhân của bạn có năng lực tốt, chuyên nghiệp và thực tế. Tuy nhiên, họ làm việc theo nguyên tắc và thiếu thái độ thân thiết, hòa đồng." .
        "\r\n Con cái của bạn có chí hướng cao, tự lập, tự cường và có lòng tự ái. Ngoài ra, vận trình học hành của con cái cũng khá tốt. Tuy nhiên, quan hệ giữa bạn và con cái không được hòa hợp, bạn khó quản lý được con cái" .
        "\r\n trong công việc bạn sẽ cảm thấy có sự ngăn cách với cấp trên, khó trò chuyện trao đổi với nhau.";

    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Phúc Đức"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn thích khám phá và thử nghiệm cái mới, tính cách hay thay đổi và thích theo đuổi những xu hướng mới nhất." .
        "\r\n Bạn là người lao tâm lao lực, lúc xử lí các sự cố phức tạp, đối diện với thị phi, sẽ có hành vi hấp tấp vội vàng, không yên, hơn nữa, nội tâm dễ cảm thấy phiền muộn, lo lắng." .
        "\r\n  về trạng thái tâm lí, dễ cố chấp ý kiến của mình để vạch ra hướng tiến hành một sự kiện, do đó thường thường làm cho người ta có ấn tượng bạn là người tự phụ, bướng bỉnh, rất chủ quan; hơn nữa, đối với mọi việc, bạn có ý thức thao túng rất mạnh." .
        "\r\n tuy quan hệ hôn nhân thành lập rất mau lẹ, tuy nhiên sau khi kết hôn quan hệ của hai người sẽ dễ trở nên căng thẳng, sống với nhau cũng thường biến thành nỗi sợ hãi, thiếu hạnh phúc." .
        "\r\n trước khi giải quyết 1 vấn đề nào đó, bạn sẽ suy nghĩ đi suy nghĩ lại nhiều lần, có nhiều sáng kiến. Do đó, bạn thích hợp làm các công việc như lặp kế hoạch, thư kí, tham mưu, phụ tá, cố vấn." .
        "\r\n Bạn có tư duy nhạy bén, ham muốn kiếm tiền lớn nhưng lại gặp nhiều khó khăn trong việc kiếm tiền, nên trạng thái tâm lí lúc kiếm tiền phần nhiều là phiền muộn, lo lắng bất an " .
        "\r\n Bạn thường được cử đi xa, mà phần nhiều lúc cứ đi xa là đàm đương nhiệm vụ quan trọng";

    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Điền Trạch"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn rất muốn có bất động sản, tử đó sẽ có ý muốn mua nhà rất mạnh, khiến bạn tất bật làm việc, miệt mài giành dụm tiền đế mua bất động sản, luôn luôn có tâm lí muốn có nhiều. " .
        "\r\n Bạn khá hợp với công việc buôn bán bất động sản" .
        "\r\n Đến đại vận cung tật ách, bạn sẽ lộ rõ tính hấp tấp, dễ bị kích động, vội vàng, dê có thái độ bức bách người khác, lúc bình thường phần nhiều họ sẽ rất thích làm chủ đạo, thích ép buộc người khác." .
        "\r\n lúc bạn đối mặt với bệnh tật, sẽ rất tích cực chữa trị, cũng thường hay đối bác sĩ, thầy thuốc, hoặc thử nhiều liệu pháp khác nhau; nhưng phân tích tỉ mỉ thì thấy thái độ của bạn không lạc quan, mà lại dễ bị kích động, vội vàng khi đối diện với mọi vấn đề." .
        "\r\n Bạn là người hay lo lắng không yên, khó tâm bình khí hòa" .
        "\r\n Bạn là người nắm quyền trong nhà, đối với chuyện lớn nhỏ trong nhà đều là người quyết định, có sức ảnh hưởng tuyệt đối. Bạn và người nhà cư xử với nhau tốt đẹp, nhưng lại thiếu cảm giác ấm áp." .
        "\r\n Bạn ít ở nhà, thường hay ở bên ngoài. Xét ở góc độ khác, khách đến nhà bạn cũng nhiều" .
        "\r\n Trong dòng họ con cái luôn hướng tâm chung sức gánh vác công việc của dòng họ" .
        "\r\n nhà cửa bạn ở thì khang trang chỉnh tề, nội thất thì nhiều thết bị cứng" .
        "\r\n Bạn có sở thích thích thu thập hoặc sưu tập món đồ gì đó" .
        "\r\n Nơi bạn làm việc rất có lực cạnh tranh trong giới làm ăn, Nhưng xem xét ở góc độ khác, bạn ở trong môi trường làm việc cũng thường xuyên phải đối diện với sự khiêu chiến và cạnh tranh, công nhân viên chức của nội bộ công ti cũng sẽ thay đổi liên tục.";

    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Quan Lộc"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Trong công việc, Bạn sẽ rất nỗ lực vì muốn nâng bản thân lên, bạn cũng tin vào năng lực của mình, rất kì vọng vào bản thân." .
                    "\r\n trong quá trình học tập bạn có thể học nhiều thứ, nhưng mục đích chỉ có một mục tiêu tiến tới như ban đầu bạn đặt ra cho mình" .
                    "\r\n những sự việc mà bạn cảm thấy hứng thú, bạn sẽ nghiên cứu tìm hiểu rất sâu." .
                    "\r\n tuy nội tâm có tính vội vàng, trong lòng hay lo lắng, không yên, nhưng bạn phần nhiều đều sống theo nguyên tắc, tuân thủ quy củ." .
                    "\r\n Bạn còn có tính ép buộc, tính cầu toàn,vì tính cầu toàn mà làm việc vất vả, là đại biểu cho mẫu bạo gan, làm việc nỗ lực tâm huyết, cũng nhờ vậy mà bạn rất phù hợp làm các chức vụ quản lý trở lên.";
                if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hỏa Tinh")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Linh Tinh"))
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . " \r\n Người khác đôi khi cảm thấy bạn là người có tính hấp tấp, dễ bị kích động, thích ra oai thể hiện bản thân quá đà";
                }
                if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hóa Kỵ"))
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . " \r\n Đôi khi bạn sẽ bị mất phương hướng, con đường học vấn sẽ dễ thấy tình trạng lúc nóng lúc lạnh, tâm tình cũng sẽ bị ảnh hưởng lớn.";
                }
                $luanTuHoaNamSinh = $luanTuHoaNamSinh . " \r\n Bạn là người biết vạch ra kế hoạch rồi mới hành động; không lãng phí tiền bạc, phần nhiều lúc dùng tiền đều có cân nhắc tính toán";
    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Nô Bộc"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn dễ mất động lực để đạt được ước mơ của bạn và thay vào đó, bạn mong đợi người khác giúp bạn." .
        "\r\n Bạn có nhiều bạn bè có quyền thế, trong số bạn bè có người năng lực chuyên môn cao" .
        "\r\n Bạn có nhiều bạn bè, bạn là người mà có thể làm quen người khác khá nhanh, luôn tỏ ra gần gũi nhiệt tình, tuy nhiên tính chất việc giao du bạn bè của bạn ở khía cạnh có qua có lại" .
        "\r\n Dù nhận được sự giúp đỡ từ bạn bè, nhưng về mặt tâm lí,bạn vẫn bị áp lực ở mức độ nào đó" .
        "\r\n Cha mẹ là người có học thức, thấu tình đạt lý, tuy nhiên mối quan hệ giữa bạn và cha mẹ nhiều khi vẫn có những bất đồng quan điểm, tranh cãi" .
        "\r\n Con của bạn là người có sở thích nghiên cứu sâu về một lĩnh vực cụ thể và thể hiện sự xuất sắc trong lĩnh vực đó. Nó cũng là người làm việc chăm chỉ và kiên nhẫn";
    
    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Thiên Di"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn đi ra ngoài làm việc có khá nhiều cơ hội đến với bạn hơn là làm việc tại nhà, tuy nhiên cơ hội thường vụt qua rất nhanh khiến bạn khó nắm bắt" .
                    "\r\n Khi thấy 1 mục tiêu lớn, bạn sẽ không tránh khỏi những áp lực vì vậy mà vạch ra một viễn trình, một mục tiêu trường kì, cũng sẽ đốc thúc bản thân gấp rút, nỗ lực để đạt thành.";
                if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hóa Kỵ"))
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . " \r\n Đi làm ăn xa nhớ cẩn thận đi lại, phải phòng tai nạn giao thông ";
                }
                $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Người phối ngẫu có thể nổi bật trong môi trường công việc, trong khi bạn có thể gặp khó khăn trong việc kiếm tiền, nhưng điều đó sẽ là động lực để thúc đẩy bản thân bạn tốt hơn." .
                    "\r\n Người phối ngẫu có cá tính mạnh mẽ, thúc đẩy bạn phải nỗ lực để tốt lên" .
                    "\r\n Bạn không thích 1 thứ gì đó quá lâu, thường dễ thay đổi. quan niệm của bạn về nghỉ ngơi, giải trí cùng khác với người ta; quá trình hưởng thụ, nghỉ ngơi của bạn thường không được nhẹ nhàng, thường là bồi dưỡng kiến thức, hay học tập một nghề mới.";

                
    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Tật Ách"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . " tư tưởng của bạn chuyển biến rất nhanh, phản ứng cũng nhanh, IQ của bạn khá cao, là người giỏi vận dụng đầu óc để suy nghĩ tính toán." .
        "\r\n dễ có hiện tượng dùng đầu óc quá độ, tâm trạng của bạn rất dễ hưng phấn, và có lối suy nghĩ tính toán theo kiểu nhảy vọt.Bạn thích học cái mới" .
        "\r\n Bạn có thể trải qua trạng thái mất động lực và sự thực hiện, dẫn đến thiếu thực tế và khó khăn trong việc bắt đầu hành động. Do đó, bạn thường không mơ mộng mà thiên về mặt suy nghĩ và phát triển ý kiến, phù hợp với những nghề nghiệp yêu cầu suy nghĩ sâu sắc và phán đoán." .
        "\r\n Với tư duy với tốc độ nhanh, mà thiếu hành động, có lúc vì cách diễn đạt nhảy vọt, hoặc không diễn tả được cách suy nghĩ của mình, nên bạn dễ bị tình trạng giống như trong và ngoài bất nhất, thân tâm mất quân bình, tâm trạng cũng dễ ưu uất" .
        "\r\n Người phối ngẫu có nhu cầu cao về chuyện riêng tư vợ chồng, trước khi kết hôn có thể đã từng có một tình yêu say đắm." .
        "\r\n Bạn làm việc ở công ti có lực cạnh tranh khá mạnh, và bạn thường phải ứng phó với những biến động trong môi trường làm việc." .
        "\r\n cha của bạn có thể vì bận rộn công việc mà thường không có ở nhà, dẫn đến tình trạng có khoảng cách với bạn";
                
    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Tài Bạch"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn khiến cho người ta cảm thấy có quyền uy và có tính tích cực, lời nói chậm rãi, sắc bén, chính xác, mà còn có sức thuyết phục." .
        "\r\n Bạn khá nghiêm túc trong công việc, và cũng tốn khá nhiều thời gian cho công việc, bản thân còn có tính thực hiện rất cao đối với các kế hoạch đà đề ra" .
        "\r\n khi làm việc bạn luôn đặt nặng vấn đề hiệu suất, cũng dễ có tâm trạng nóng lòng, dễ bị kích động." .
        "\r\n Bạn là người nỗ lực thực hiện mục tiêu đề ra, hành động mau lẹ, nghĩ là làm, thuộc loại người thực tế." .
        "\r\n Bạn có tính chuyên nghiệp, có quyền uy trong môi trường làm việc, và biểu hiện rất tốt năng lực của mình. Nhờ vậy bạn thường được cấp trên xem trọng, có nhiều cơ hội thăng tiến hơn người bình thường." .
        "\r\n Bạn vì kiếm tiền mà rất tích cực, rất muốn kiếm tiền và cũng kiếm được tiền, tuy nhiên công việc kiếm tiền không được nhẹ nhàng, thường thường là hai phần sức thì được một phần tiền, và phải luôn nỗ lực, nhưng điều đó lại đem đến cho bạn cảm giác mạnh mẽ và thỏa mãn, đó là một kiểu sống trong công việc" .
        "\r\n Thái độ dùng tiền của bạn là hay suy đi tính lại, tiêu xài tiền dè xẻn, thường có kế hoạch sử dụng tiền rất rõ ràng, tuyệt đối không lãng phí một xu." .
        "\r\n Tuy thái độ xử sự của bạn rất thân thiện, nhưng duyên với người chung quanh lại không được tốt, vì bạn lấy uy tín để kiến lập quan hệ, phàm chuyện gì cũng làm cho người ta có cảm giác bạn có tính toán, còn thái độ làm việc thì quá nguyên tắc.";
    
    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Tử Tức"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Trong gia đình con cái là người có tiếng nói, Có thể nói 1 câu là 1 gia đình mà con hơn cha" .
        "\r\n Trước khi có con có thể bạn là người thiếu động lực và sự toan tính nhưng sau kho có con sẽ làm cho bạn tích cực hơn, vì muốn con cái có cuộc sống tốt hơn mà phải cố gắng, bạn còn có tâm lí rất kì vọng con cái thành đạt." .
        "\r\n Việc giáo dục con cái bạn phải nên cẩn trọng, tránh giáo dục sai lần khiến con cái phát triển \"cái tôi\" một cách thái quá, dễ có trạng thái tâm lí lúc nào cũng cho mình là đúng, bạn sẽ cảm thấy con cái ngỗ nghịch, khó dạy dỗ, và cũng có khoảng cách giữa hai đời." .
        "\r\n bình thường bạn ở nhà không đi đâu, cũng sẽ thành thường hay ra bên ngoài, phần nhiều nguyên nhân ra bên ngoài là vì công việc, hoặc vì giao tế thù tạc" .
        "\r\n Bạn muốn đầu tư và hợp tác làm ăn, tham gia nhiều công việc làm ăn, người hợp tác sẽ chuyên nghiệp và có năng lực hơn bạn, nhưng trong quá trình hợp tác dễ xảy ra tình trạng tranh chấp trong các cổ đông bầu không khí hợp tác không được tốt." .
        "\r\n Khi tâm trạng, bạn là người tiêu xài khá phung phí";
        if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hỏa Tinh")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Linh Tinh")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Kình Dương")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Đà La")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Không Kiếp")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Địa Kiếp"))
        {
            $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Chuyện sinh đẻ phải cẩn trọng, dễ sảy ra trường hợp khó sinh, hoặc có khả năng phải sinh mổ.";
        }


    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Phu Thê"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Trong cuộc sống hôn nhân, bạn xem người phối ngẫu là đầu tàu, quyết định lớn nhỏ trong nhà là do \"một nửa kia\" tính toán." .
        "\r\n Bạn tiến đến hôn nhân nhanh chóng với người bạn đời qua thời gian tìm hiểu nhau ngắn, lấy nhau về bạn là người khá phụ thuộc vào người phối ngẫu, tuy nhiên có lúc bạn k tránh được hụt hẫng do phát hiện đối tượng hôn phối của mình không như trong tưởng tượng" .
        "\r\n Sau khi kết hôn có thể người phối ngẫu có giúp được gì cho bạn, nhưng cuộc hôn nhân này vẫn khiến bạn có nhân sinh quan đúng đắn hơn và cải thiện nâng bản thân lên, hoặc bạn bị buộc phái đối mặt với hiện thực, bắt buộc phải nỗ lực." .
        "\r\n Người phối ngẫu của bạn là người khá bận rộn với công việc mà hai người gần nhau ít mà xa nhau nhiều, những cuộc trò chuyện trao đổi giữa vợ chồng vì vậy mà giảm dần" .
        "\r\n Bạn nhiều khi phải đi xa vì công việc làm ăn, bạn dễ thấy căng thẳng trong môi trường làm việc. Bạn là người không thích 1 thứ gì đó quá lâu, dễ thay đổi sở thích của mình";
    

    }else if($tenSao == "Hóa Quyền"&&$cungChuaSao=="Huynh Đệ"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Trong gia đình, hầu như các quyết định lớn nhỏ đều do anh chị trong gia đình quyết định, bạn ít khi phải tham gia" .
        "\r\n Bạn là người có quan hệ xã hội khá tốt, bạn bè của bạn phần nhiều đều là những người có học vấn hay địa vị cao hơn bạn trong xã hội" .
        "\r\n Bạn có anh trai hoặc chị gái, mà không phải là trưởng nam hay trưởng nữ (có thể là anh hay chị yếu mạng)." .
        "\r\n Mối quan hệ giữa bạn và anh em trong gia đình rất tốt, có việc tất sẽ giúp đỡ nhau" .
        "\r\n Sức khỏe thể chất và tinh thần của bạn khá tốt và ổn định" .
        "\r\n Những tâm tư sâu kín của bạn vận tác một cách mau lẹ, trong đó bạn còn có kì vọng tự lập tự cường, và ý đồ phấn đấu đi lên tuy nhiên những ý đồ này của bạn sẽ khó thực hiện hoặc gặp nhiều khó khăn";
        if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Địa Không")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Địa Kiếp")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Kình Dương")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Đà La")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hỏa Tinh")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Linh Tinh"))
        {
            $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Dòng tiền kiếm ra có sự luân chuyển chậm chạp, nhiều khi khó khăn";
        }
        else
        {
            $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Có thời gian dòng tiền của bạn có sự luân chuyển khá nhanh, lúc nào cũng có tiền ra tiền vào";
        }
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn vì tình yêu mà phải bôn ba ngược xuôi, gia đình nhà phối ngẫu thường xen vào cuộc sống hôn nhân của bạn, vì vậy mà cuộc sống hôn nhân của bạn thường không được yên ổn" .
            "\r\n Đối với bạn, mẹ bạn có sức ảnh hưởng với bạn khá nhiều" .
            "\r\n Đối với việc trang hoàng trong nhà bạn có phong cách, gia cụ trong nhà hay thay đổi hoặc dời chuyển, và thường có khách đến thăm; vả lại, không loại trừ trường hợp chính bản thân bạn cũng thường hay dời chuyển.";

    }

    //luận hóa lộc
    if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Mệnh"){
        $luanTuHoaNamSinh =  $luanTuHoaNamSinh . "\r\n Tính cách của bạn thường thiên về việc ưu tiên lợi ích cá nhân và quan điểm riêng của mình." .
                    "\r\n khi đối mặt với các vấn đề cần giải quyết, cách suy nghĩ và cách nhìn của bạn đều đứng từ góc độ cảm tính, cho nên sẽ khiến người ngoài có cảm giác bạn là người giàu tình cảm" .
                    "\r\n Bạn là người có tính đa sầu đa cảm, xử lý mọi chuyện theo phương diện tình cảm là chính, bạn có nhiều tâm trạng khác nhau, muốn biết nhiều chuyện, muốn bản thân thành công";
                if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hóa Kỵ")||$cungAnSaoHoaKy == "Quan lộc"||$cungAnSaoHoaKy == "Tài bạch")
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người lạc quan dễ hòa đồng, khi mục tiêu nào đó bạn đặt ra bị đổ vỡ thì bạn sẽ nỗ lực để thực hiện được mục tiêu đó, cơ hội thành công cũng nhiều";
                }
                else
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người lạc quan dễ hòa đồng, khi mục tiêu nào đó bạn đặt ra bị đổ vỡ thì bạn sẽ tìm ra được mục tiêu mới";
                }
                $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người ưa nịnh, thích được người khác nâng niu chiều chuộng" .
                    "\r\n vì bạn có sự cảm thụ trong tình yêu khá cao nên dễ vướng vào con đường tình sớm, tuy nhiên đường tình thường sớm nở chóng tàn hoặc tình đơn phương" .
                    "\r\n Con cái của bạn là người sống tình cảm và biết hướng về gia đình";

    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Phụ Mẫu"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người hiếu thuận,muốn phụng dưỡng cha mẹ, cũng muốn cha mẹ có một cuộc sống đầy đủ";
    
        if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Thiên Diêu")|| kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Tả Phù") || kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hữu Bật") || (kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Thiên Diêu") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Tả Phù") && kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hữu Bật")) || $cungAnSaoHuuBat == "Huynh đệ" || $cungAnSaoTaPhu == "Huynh đệ" || ($cungAnSaoTaPhu == "Huynh đệ" && $cungAnSaoHuuBat == "Huynh đệ") || $cungAnSaoDaoHoa == "Tật ách")
        {
            $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Cha mẹ là người sống thiên về tình cảm, tuy nhiên có thể cha hoặc mẹ dễ có người khác ở bên ngoài";
        }
        else
        {
            $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Cha mẹ là người sống thiên về tình cảm, ";
        }
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Đôi lúc bạn cũng có suy nghĩ ỷ lại vào cha mẹ, trông mong ở phía cha mẹ" .
            "\r\n Bạn là người được lòng cấp trên của mình, trong môi trườn làm việc bạn cũng thường được cấp trên quan tâm giúp đỡ" .
            "\r\n Bạn là người luôn quan tâm lo lắng và đối sử rất tốt với con cái" .
            "\r\n Bạn ở trong một giai đoạn nào đó của đời người, sẽ làm công việc đầu tư nhiều hướng, bạn có tính đến việc hợp tác làm ăn, rất có lòng tin về khả năng kiếm lời trong việc hợp tác này, mà đối tượng hợp tác cũng có vốn liếng hùng hậu.";
        

    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Phúc Đức"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người lạc quan và khi giải quyết mọi việc đều có cách suy nghĩ đầy cảm tính, tuy nhiên loại cảm tính này không vì bản thân mình, mà là vì người khác" .
        "\r\n Bạn thường không quá lưu tâm đối với những sự tình trái với ý của mình, phần nhiều đều có thể cho qua. nên lúc gặp khó khăn vẫn có cái nhìn thoáng, giữ được tâm trạng lạc quan để đối mặt" .
        "\r\n Bạn thích sống những ngày ung dung nhàn nhã, vì thế rất dễ sinh ra lười biếng, làm việc hay trì hoãn" .
        "\r\n Bạn dễ làm quen bạn bè mới, khá hòa hợp với mọi người, bạn bè, sếp ở công ty cũng khá có thiện cảm với bạn, bạn cũng là người khá đa tình, trước kết hôn có thể có đôi ba mối tình";

    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Điền Trạch"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn sinh ra trong 1 gia đình có hoàn cảnh tốt,được bố mẹ chăm lo tốt cho chuyện học hành" .
                    "\r\n Bạn là người có thể bộc lộ toàn bộ tính đa tình và hòa hợp của mình, đối với cách xử lí mọi việc đều cầu hài hòa." .
                    "\r\n Trong căn nhà bạn ở trông rất thẩm mỹ, có nhiều đồ đạc, đồ trang trí" .
                    "\r\n nơi bạn đặt kì vọng là gia đình, và bạn cũng trông mong người nhà đối xử với nhau hòa hợp, hi vọng có thể \"hòa khí sinh tài\"." .
                    "\r\n Con cái hướng về gia đình, gia đình là nơi con cái có cảm giác ấm áp, ngọt ngào";
                if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao,  "Hóa Kỵ")&&!kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao,  "Hóa Quyền")||(kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao,  "Hóa Kỵ") && !kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao,  "Hóa Khoa"))||kiemTraSaoTrongCungTheoDinhDanh($laSoData, $cungChuaSao, "Hóa Kỵ"))
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Hoàn cảnh gia đình tuy khá giả, từng có nhiều bất động sản, nhưng vẫn không giữ được hết, vẫn phải bán đi";
                }
                $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn thường có thói quen thu thập, sưu tầm mọi thứ" .
                    "\r\n Trong môi trường làm việc thì mối quan hệ giữa bạn và đồng nghiệp khá tốt. Bạn đã từng làm nhiều công ti hoặc thay đổi công việc nhiều lần" .
                    "\r\n Lúc bạn mắc bệnh thì thường đi nhiều nơi để chữa trị, giữ được thái độ lạc quan";
    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Quan Lộc"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn là người trong lòng muốn nâng cao và đa dạng hóa năng lực của mình, mà mục tiêu bạn muốn nỗ lực theo đuổi cũng nhiều." .
                    "\r\n nội tâm của bạn không thích cô đơn và im lặng; về cách suy nghĩ của bạn không phải là quá cứng nhắc, bạn có khả năng thích ứng với hoàn cảnh và đối xử với mọi người một cách tự nhiên và tình cảm." .
                    "\r\n Bạn là người hứng thù học tập tìm hiểu cái mới, đối với bạn học tập hay biết 1 cái gì mới cũng là 1 kiểu hưởng thụ.";
                if($cungAnSaoHoaKy == "Phu thê"|| $cungAnSaoHoaKy == "Tử tức"|| $cungAnSaoHoaKy == "Nô bộc")
                {
                    $luanTuHoaNamSinh = $luanTuHoaNamSinh . " Tuy nhiên lúc bắt đầu học tập bạn rất nhiệt tình, nhưng mau nguội; hơn nữa, ưa học nhiều thứ, phạm vi quá rộng, nên phần nhiều là học nhiều mà ít tinh.";
                }
                $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Đôi khi để chuyện tình cảm ảnh hưởng đến công việc, học hành" .
                    "\r\n Trong công việc bạn thường không vì sự đãi ngộ đặc biệt mới nỗ lực làm việc, mà chỉ mong hoàn cảnh làm việc có sự hòa hợp, biểu hiện của bạn cũng hòa hợp với mọi người." .
                    "\r\n Trong mắt người phối ngẫu thì bạn là người biết chăm lo cho họ và gia đình, đáng tin cậy";

    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Nô Bộc"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn có bạn bè qua lại nhiều, bản thân bạn cũng thích giao du bạn bè. Nói một cách khác, bạn có cá tính hướng ngoại." .
                    "\r\n  Về tình cảm, bản thân bạn đối xử khá tốt với bạn bè, cũng sẽ đơn phương cho rằng bạn bè sẽ đối xử tốt giống như mình." .
                    "\r\n bạn bè đối với bạn, nếu muốn ra tay trợ giúp, tất sẽ bao gồm cả tiền bạc, chớ không phải chỉ ủng hộ về mặt tinh thần." .
                    "\r\n bản thân bạn không giỏi biểu đạt sự nhiệt tình, hoặc ngôn từ không đạt ý, nhưng đối với bạn bè cũng thường âm thầm trợ giúp." .
                    "\r\n Cá tính của bạn, thì việc học hành thường không được chuyên tâm, rất dễ để chuyện tình cảm làm ảnh hưởng đến học hành" .
                    "\r\n Trong môi trường làm việc, cấp trên của bạn không tệ, có chiếu cố cho bạn." .
                    "\r\n Trong gia đình bạn khá hợp với 1 trong những người con của mình";
    
    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Thiên Di"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Số bạn đào hoa, đi ra ngoài được nhiều người yêu quý. Cơ hội đến với bạn trong làm ăn cũng nhiều" .
                    "\r\n Bạn thường đển lại ấn tượng với người khác là người sống tình cảm, mối quan hệ với mọi người xung qunh khá tốt đẹp" .
                    "\r\n Bạn thường muốn khám phá nhiều điều và không dễ dàng hài lòng. bạn tìm kiếm sự hoàn hảo, nhưng đôi khi tâm trạng của bạn không ổn định và suy nghĩ có thể không luôn thực tế" .
                    "\r\n Người phối ngẫu của bạn là người có nặng lực, kiếm được tiền, người phối ngẫu cũng đối sử khá tốt với bạn";
                
    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Tật Ách"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Có 1 khoảng thời gian Cuộc sống của bạn mất đi mục tiêu, bôn ba bận rộn mà không biết mình đang tìm cái gì." .
                    "\r\n Bạn là người có trí tưởng tượng phong phú, có rất nhiều ý tưởng, tuy nhiên suy nghĩ nhiều nhưng ít hành động." .
                    "\r\n Bạn có suy nghĩ xu hướng muốn hết hôn muộn, tuy bạn là người giàu tình cảm nhưng lại không giỏi biểu đạt tình cảm ra ngoài" .
                    "\r\n bạn là mẫu người có bề ngoài lạnh nhạt, trầm tĩnh, ít nói nhưng nội tâm phong phú, dễ bị đối tượng tình yêu của mình xỏ mũi dắt đi." .
                    "\r\n Hồi đi học, thành tích học tập của bạn không ổn định, lúc lên lúc xuống" .
                    "\r\n Trước hôn nhân, tình yêu của bạn với người phối ngẫu thường được giấu kín, ít người biết đến" .
                    "\r\n Cha của bạn rất có duyên với người bên ngoài, đặc biệt là người khác giới";
                
    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Tài Bạch"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n bản thân rất có hứng thú kiếm tiền, trạng thái tâm lý kì vọng vào việc kiếm tiền thể hiện rõ rệt hơn người bình thường, bạn sẽ suy nghĩ tính toán nhiều phương thức kiếm tiền và con đường kiếm tiền khác nhau, và cũng sẽ biến nó thành hành động thực tiễn" .
                    "\r\n Bạn có nhiều cơ hội kiếm tiền đến với bạn tuy nhiên bạn cũng không giỏi giữ tiền, tiêu sài tiền rất nhanh, mặt khác dù bạn không có tiền cũng sẽ biểu hiện là người có tiền và đối đãi bạn bè rất khẳng khái và rộng rãi" .
                    "\r\n Bạn là người năng động, bạn không chỉ làm 1 nghề mà còn có suy nghĩ làm thêm 1 vài nghề nữa để kiếm thêm thu nhập" .
                    "\r\n Người ngoài tiếp xúc với bạn sẽ cảm thấy bạn là người sống tình cảm, mà cách xử sự cũng dùng tình cảm rất tế nhị, nên rất thu hút người khác giới" .
                    "\r\n người phối ngẫu sẽ cho rằng bạn đa tình hơn họ, do đó dễ bị người phối ngẫu hiểu lầm là đa tình hào hoa" .
                    "\r\n Khi bước vào xã hội để làm việc và kiếm tiền, sau một thời gian, quan niệm về tình cảm của bạn sẽ thay đổi. Vì tâm tính dễ xúc động, bạn dễ dàng thay đổi trong các mối quan hệ tình cảm. Người ngoài sẽ cho rằng bạn là người không kiên định trong chuyện tình cảm.";
                
    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Tử Tức"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn là người khá yêu thương và chiều chuộng con cái nhưng cũng kỳ vọng vào con cái nhiều, mong con cái thành công, thành ông này bà nọ" .
                    "\r\n Trong thâm tâm luôn mong muốn nhà có đông con nhiều cháu" .
                    "\r\n Bạn là người thích sự lãng mạn trong tình yêu, hồi trẻ cũng có dăm ba mối tình vắt vai, người yêu của bạn cũng là người có kinh nghiệm phong phú trong tình yêu" .
                    "\r\n Khi tâm trạng bạn rất dễ vì cảm xúc nhất thời đấy mà tiêu sài bạt mạng" .
                    "\r\n Bạn thường tích cực tìm kiếm cơ hội hợp tác bên ngoài và dành nhiều thời gian cho các mối quan hệ xã giao. Sự hợp tác trong sự nghiệp của bạn rất đa dạng" .
                    "\r\n Bạn rất quan tâm gia đình, nhưng lại thu xếp không hợp lí, vì đây chỉ là quan tâm bằng tình cảm, chớ không có hành động thực tế.";


    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Phu Thê"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "\r\n Bạn cảm nhận thấy người phối ngẫu là người đa tình và có duyên với người khác giới hơn bạn" .
                    "\r\n Bản thân có khát vọng tình yêu,cũng có duyên với người khác giới, còn chủ về rất mong muốn có cuộc sống hai người, bạn rất giàu cảm xúc và có cảm thụ khá cao đối với tình cảm, bạn sẽ vì tình yêu mà kết hôn." .
                    "\r\n Bạn theo đuổi tình yêu lãng mạn, thích lý tưởng hóa tình yêu của 2 người nhưng khi quay về đối mặt vối hiện thực thường cảm thấy hụt hẫng vì mọi thứ không như kỳ vọng. Bạn cũng có kỳ vọng cao đối với nguồi phối ngẫu" .
                    "\r\n Bạn có cách tiêu khiển, giải trí rất tinh tế, có hứng thú và sở thích đa dạng, mà những sở thích này thường phải tiêu xài không ít tiền";
                

    }else if($tenSao == "Hóa Lộc"&&$cungChuaSao=="Huynh Đệ"){
        $luanTuHoaNamSinh = $luanTuHoaNamSinh . "Bạn không phải con một trong gia đình, bạn có anh chị em, các anh chị em trong nhà được đánh giá là đa tình hơn bạn" .
                    "\r\n Tình cảm của bạn với anh chị em trong nhà khá tốt, còn có qua lại về tiền bạc" .
                    "\r\n Thể chất của bạn không khỏe mạnh như vẻ bề ngoài, mỗi khi mắc bệnh bạn sẽ thử nhiều cách trị liệu khác nhau(Đông y, Tây y, thậm chí liệu pháp kinh nghiệm dân gian)" .
                    "\r\n Bất động sản của bạn có giá trị không nhỏ, quan hệ vợ chồng tốt đẹp, vợ chồng có tài sản chung" .
                    "\r\n Mối quan hệ giữa bạn với bố mẹ người phối ngẫu khá tốt, được quan tâm nhiều";
    }
    return $luanTuHoaNamSinh;
}

function luanGiaiTuHoaPhai($laSoData, $gt) {
    $output = '';
    $luanCungMenh = '';
    $luanHuynhDe = '';
    $luanPhuThe = '';
    $luanTuTuc = '';
    $luanTaiBach = '';
    $luanTatAch = '';
    $luanThienDi = '';
    $luanNoBoc = '';
    $luanQuanLoc = '';
    $luanDienTrach = '';
    $luanPhucDuc = '';
    $luanPhuMau = '';
    foreach ($laSoData as $cung) {
        $tenCung = $cung['cung'] ?? 'Không xác định';
        $tuHoaPhai = $cung['tu_hoa_phai'] ?? [];
        if (!empty($tuHoaPhai)) {
            foreach ($tuHoaPhai as $sao => $lienQuan) {
                // Bạn có thể thay dòng dưới bằng nội dung luận giải thực tế
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Mệnh"){
                    $luanCungMenh .= "\r\n Bạn là người không bền trí, mọi việc nhiệt huyết chỉ được lúc ban đầu" .
                    "\r\n Hay tha thứ cho bản thân, luôn tìm ra cho mình được cái cớ để thoái thác" .
                    "\r\n Bạn là người nói chuyện logic, trắng đen gì nói cũng có lí, biết cách ứng phó, không bao giờ xúc phạm hay làm người khác phiền lòng" .
                    "\r\n Bạn là người hành xử mọi chuyện đều có chừng mực, có duyên với người chung quanh, không thô tục; có khiếu thường thức nghệ thuật, thông minh lanh lợi, khá đa tình, dễ bị cảm động, dễ khóc" .
                    "\r\n Bạn tiêu xài tiền rất nhanh, làm việc không chuyên tâm, thường hay quên những điều mình hứa hẹn với người khác";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Mệnh"){
                    $luanHuynhDe .= "\r\n Tình cảm với các anh chị em trong gia đình khá tốt, có thể nhờ anh em giúp đỡ mà thành công" ;
                    $luanThienDi .= "\r\n Có duyên với người xung quanh, đối với mọi người rất nhiệt tình, nhưng sẽ không nỗ lực vì người ta, cho người ta lợi ích nhưng không cầu báo đáp, rộng lượng và hào phóng; hướng ngoại, có nhiều cơ hội kiếm tiền, muốn làm ăn ở nơi đông đúc" .
                    "\r\n Cẩn thận dễ bị người khác lừa tiền, giật tiền";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Mệnh"){
                    $luanPhuThe .= "\r\n Người Phối ngẫu là người thông minh, có trợ giúp cho sự nghiệp của bạn, sau kết hôn sự nghiệp của bạn khá thuận lợi; bạn trưởng thành sớm, rất duyên với người khác, nên cũng dê kết hôn sớm, có tình thâm với người phối ngẫu";
                    if(kiemTraTuHoaPhai($laSoData,"Mệnh","Hóa Kỵ","Phúc Đức")){
                        $luanPhuThe .= "\r\n Chuyện tình cảm tuy muốn kết hôn nhưng không được thuận lợi, gặp nhiều chở ngại, khó khăn";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Mệnh"){
                    $luanTuTuc .= "\r\n Bạn nặng quan niệm gia đình, phần nhiều sau khi sinh con sẽ dễ kiếm tiền hơn; quan hệ với con cái rất tốt, rất thương yêu con cái, có thể được quý tử." .
                    $luanQuanLoc .= "\r\n Bạn có thể mở tiệm kinh doanh hoặc kinh doanh bất động sản";
                    $luanTatAch .= "\r\n năng lực tình dục tốt";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Mệnh"){
                    $luanTaiBach .= "\r\n Bạn thích tiền, xem trọng tiền bạc, thích hưởng thụ, dễ kiếm tiền; có thành tựu trong việc sáng lập sự nghiệp" .
                    "\r\n Bạn xem trọng sự nghiệp, tay trắng làm nên, kinh doanh quy mô nhỏ"; 
                    $luanPhuThe .= "\r\n giỏi giao tế, chăm sóc, lo lắng cho người phối ngẫu, đa tình, xem trọng hôn nhân và tình cảm.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Mệnh"){
                    $luanTatAch .= "\r\n Bạn là người Lạc quan, dễ có tính lười biếng, dễ phát phì; lúc còn nhỏ có thể chất yếu, trân quý sức khỏe, tiêu xài nhiều tiền cho bản thân, thích dùng thuốc bổ, thực phẩm bổ dưỡng"; 
                    $luanCungMenh .= "\r\n hiếu động, có đầu óc, duyên với người chung quanh tốt, hiếu thảo với cha mẹ, được bậc trường bối yêu thích, đề bạt, nâng đỡ; có thể phát triển lớn sự nghiệp. ";
                    if(kiemTraTuHoaPhai($laSoData,"Mệnh","Hóa Kỵ","Phu Thê")){
                        if($gt == "true"){
                            $luanPhuThe .= "\r\n Bạn thích dựa dẫm phụ nữ, thích chuyện gần gũi, đeo dính người phối ngẫu, có thể hưởng lạc thú phòng the.";
                        }else{
                            $luanPhuThe .= "\r\n Bạn có duyên với người khác giới";
                        }
                    
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Mệnh"){
                    $luanThienDi .= "\r\n Hơi lười biếng, thích đi đó đi đây, vui chơi, có duyên với người chung quanh, được nhiều quý nhẫn trợ giúp, cho tiền để người phối ngâu tiêu xài; ở bên ngoài, có mối quan hệ giao tế rộng, có nhiều cơ hội kiếm tiền, được đắc ý nhưng tiêu tốn cũng nhiều, cần phải phòng tai nạn bất ngờ";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Mệnh"){
                    $luanNoBoc .= "\r\n Bạn là người Hướng ngoại, thích giao du bạn bè, có nhiều bạn, thù tạc giao tế cũng nhiều, thường tự giới thiệu mình với người lạ; thích hợp công tác quáng cáo, thúc đẩy tiêu thụ, quan hệ công cộng, ngoại giao"; 
                    $luanTaiBach .= "\r\n có thể kiếm tiền ở nơi đông đúc, náo nhiệt, như siêu thị bách hóa, nghệ thuật biểu diễn, ngành giải trí; có thể hợp tác vói bạn bè, làm cổ đông" .
                    "\r\n Bạn bè giúp đỡ nhiêu, nhưng cũng dễ vì bạn bè mà tổn thất tiền bạc; có quan hệ tốt đẹp với ông chủ và đồng sự"; 
                    $luanPhuThe .= "\r\n Bạn rất quan tâm sức khỏe của người phối ngẫu, đeo dính người phối ngẫu.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Mệnh"){
                    $luanQuanLoc .= "\r\n Bạn là người Nặng lòng vì sự nghiệp, nhưng không nhất thiết là có trách nhiệm với công việc, không cam tâm chỉ làm viên chức nhỏ; thường thay đổi công việc, có thể có thành tựu, được vui vẻ ở nơi làm việc; có hứng thú với nhiều lãnh vực, làm việc không chuyên nhất; đi làm có công việc vừa ý, lương cao, thăng tiến nhanh; tay trắng làm nên, nên kinh doanh quy mô nhỏ; lúc còn đi học, có thành tích tốt;sau khi kết hôn thì rất chăm sóc người phối ngẫu.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Mệnh"){
                    $luanDienTrach .= "\r\n Bạn là người biết chăm lo cho gia đình, cũng làm tiêu hao gia sản, tốn tiền vì nhà cửa, như mua tậu nhà, trang hoàng... ";
                    $luanQuanLoc .= "Sau khi bạn được sinh ra, gia vận chuyển biến theo hướng tốt, sinh hoạt gia đình hạnh phúc, có tiền; Bạn thích hợp kinh doanh bất động sản; dễ có đào hoa, lập gia đình khá sớm.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Mệnh"){
                    $luanPhucDuc .= "\r\n Bạn là người Được hưởng phước, xem trọng hưởng thụ, thích hưởng thụ những thứ thuộc loại cao cấp, không thích lao động vất vả, thích động não, có khẩu phúc, ưa ăn mà lười làm; có nhiều cơ hội kiếm tiền, dễ kiếm tiền, nhưng không nhất định là có tiền nhiều" .
                    "\r\n Là 1 người rộng lượng, chịu chi tiền để thỏa mãn sở thích cá nhân, có hứng thú với nhiều lãnh vực nhung không chuyên nhất; quan tâm lo lắng cho sự nghiệp của người phối ngẫu";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Mệnh"){
                    $luanPhuMau .= "\r\n Hiếu thảo với cha mẹ, sống chung vui vẻ với cha mẹ, được trưởng bối yêu thích, đề bạt, nâng đỡ";
                }
                // Hóa Quyền - Mệnh
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Mệnh"){
                    $luanCungMenh .= "\r\n Bạn là người cá tính mạnh, thích nắm quyền, thông minh tài cán, người có thể làm được nhiều việc; không tín nhiệm người khác, chuyên quyền, sợ quyền lực rơi vào tay người khác" .
                    "\r\n Bạn tuy thông minh, tài cán, nhưng phản ứng mẫn tiệp, tư tưởng ngoan cố, bướng binh, hay ra oai, phách lối, tranh cường hiếu thắng, thường tự cho mình là đúng, nên dê xảy ra phiền phức" .
                    "\r\n Bạn thích lãnh đạo người khác, làm việc thường vượt quá giới hạn của mình, nhiệt tình hay lo chuyện bao đồng" .
                    "\r\n Bạn có quyền nhưng không có thực chất, có cấp dưới nhưng không ra lệnh được, hoặc những người dưới quyền chỉ là làm thời vụ, ưa tạo sự chú ý, có lúc khoa trương quá sự thật. ";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Mệnh"){
                    $luanHuynhDe .= "\r\n Bạn thích can thiệp vào chuyện của anh chị em, có nhiều ý kiến, dễ xảy ra tranh chấp, tranh cãi, tranh quyền, nhưng giữa anh chị em vẫn ngồi bàn bạc nói chuyện được" ;
                    $luanNoBoc .= "\r\n đối với bạn bè thường có thái độ làm cao, quan hệ xã giao thường không lớn" .
                    $luanQuanLoc .= "\r\n Nếu hợp tác làm ăn vói anh chị em trong gia đình, khi giao quyền lực cho anh chị em, bản thân sẽ không có lợi";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Mệnh"){
                    $luanPhuThe .= "\r\n Bạn là người Thích quản thúc phối ngẫu, nhưng cũng nghe lời người phối ngâu; giao quyền cho người phối ngẫu, bản thân vẫn có quyền, nhưng giữa vợ chồng dễ xảy ra mâu thuẫn" .
                    "\r\n Hồi còn trẻ dễ có tình yêu theo kiểu tiểng sét ái tình, mới gặp đã yêu; cũng có thể vì gia đình hoặc nhân tố khác mà phái kết hôn, hôn nhân hơi bị ép buộc";
                    if($gt == "true"){
                        $luanPhuThe .= "\r\n Bạn có tính gia trưởng, hai người có nhiều ý kiến bất đồng, sẽ tranh giành quyền chủ đạo trong cuộc sống hôn nhân; làm việc rất siêng năng chăm chi, có thể tự lập sự nghiệp, sự nghiệp sáng sủa, thăng tiến và có lợi.";
                    }else{
                        $luanPhuThe .= "\r\n Bạn có tính cách mạnh mẽ, hai người có nhiều ý kiến bất đồng, sẽ tranh giành quyền chủ đạo trong cuộc sống hôn nhân; làm việc rất siêng năng chăm chi, có thể tự lập sự nghiệp, sự nghiệp sáng sủa, thăng tiến và có lợi.";
                    }
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Mệnh"){
                    $luanTuTuc .= "\r\n Ở nhà thường thể huyện cái uy nghiêm của bậc cha mẹ, dạy dỗ con cái nghiêm khắc, kiểu áp lực nặng, đôi khi không kiềm chế được cảm xúc mà động tay chân với con cái" .
                    "\r\n bạn có nhiều hơn 1 đứa con, con cái có tài, cá tính mạnh mẽ, sẽ có tiến bộ, phát huy được tài năng, học vấn" .
                    "\r\n lúc được phân chia gia sản bạn hơi so bì với các anh em trong nhà" ;
                    $luanQuanLoc .= "\r\n lúc hợp tác làm ăn với người khác, bạn sẽ góp 1 phần lớn, nhưng không nhất định bản thân sẽ ra mặt nắm quyền; sẽ có đào hoa, dễ vì đào hoa mà chuốc phiền phức, nhất là lúc không còn hợp tác.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Mệnh"){
                    $luanTaiBach .= "\r\n Bạn có cách quản lí tài chính khá linh hoạt không thích giữ tiền khư khư, mà mang tất cả tiền bạc ra xoay chuyển đầu tư, làm ăn, thích công việc có tính đầu cơ" .
                    "\r\n Bạn có số tay trắng gây dựng sự nghiệp, nếu đi làm hưởng lương có thể được nắm quyền tài vụ; thích hưởng thụ xa hoa, lúc xử sự với người khác" ;
                    $luanPhuThe .= "\r\n Bạn rất xem trọng thể diện, ưa tạo sự chú ý; bạn là người chủ đạo trong quan hệ giữa vợ chồng, sẽ là người bảo vệ hôn nhân của hai người.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Mệnh"){
                    $luanTatAch .= "\r\n Bạn hồi trẻ khá Hiếu động, nghịch ngợm, cá tính mạnh, dễ xung đột với người khác, cũng dễ xảy ra hành vi đánh nhau" .
                    "\r\n Khá vất vả, dễ bị thương vì té ngã; dễ nổi giận, không thích nghe lời của bậc trưởng bối, hay cãi lại, nhưng hiếu thảo với cha mẹ" ;
                    $luanQuanLoc .= "\r\n Bạn sẽ có sự nghiệp của riêng minh, nếu đi làm hưởng lương sẽ nắm quyền; tính dục mạnh, nhiều đào hoa.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Mệnh"){
                    $luanThienDi .= "\r\n Ở bên ngoài, bạn thích biểu hiện tài năng, rất thích ngồi ở địa vị lãnh đạo, rất thích được người ta kính trọng, cũng được người ta khẳng định, nhưng có lúc không được thực tế" .
                    "\r\n Thường hay cạnh tranh với người khác, tranh cường hiếu thắng; để tạo sự chú ý hoặc để phô trương thể diện, bạn sẽ có hành vi phóng đại năng lực của mình quá sự thật" .
                    "\r\n Tuy có thể đạt được mục đích, nhưng cũng dễ bị tiểu nhân xúi giục hoặc ngầm hại; bản thân xúc phạm hay làm phiền lòng người khác mà không biết" .
                    "\r\n Bạn khá chủ động trong hôn nhân và tình cảm.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Mệnh"){
                    $luanNoBoc .= "\r\n Bạn Không tùy tiện giao du bạn bè, mà rất chọn lựa, tiêu chuẩn hơi cao, nhưng đã giao du thì rất trung thành, không bao giờ bán đứng bạn bè" ;
                    $luanTaiBach .= "\r\n Bạn thích lãnh đạo người khác, sẽ được bạn bè hỗ trợ, thích hợp kiếm tiền ở những nởi náo nhiệt, như nghệ thuật biểu diễn";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Mệnh"){
                    $luanCungMenh .= "\r\n Bạn là người Có năng lực, phản ứng nhạy bén, có thể dựa vào tài năng và kĩ thuật chuyên môn của mình để mưu cầu lợi ích, dễ thành công; có tinh thần trách nhiệm, có năng lực lãnh đạo, mạng làm chủ, có thực quyền, sáng lập được cơ nghiệp, dễ được thăng tiến, ở trường có thành tích học tập tốt; sẽ quản thúc người phối ngẫu.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Mệnh"){
                    $luanCungMenh .= "\r\n Bạn là người Ở nhà nắm quyền chủ đạo, thích ra oai, hơi phách lối, đối với người nhà thường hay có ý kiến, dễ có hành động khinh suất, nhưng rất đoàn kết, lo liệu cho nhau, dạy dỗ con cái nghiêm khắc, thông thường là \"xếp\" trong nhà, tính dục khá mạnh"; 
                    $luanQuanLoc .= "\r\n nên làm nghề đầu tư bất động sản, nhất định sẽ có bất động sản, nhà ở lớn mà sang trọng.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Mệnh"){
                    $luanCungMenh .=  "\r\n Bạn là người Có tài học, sẽ có thành tựu, có thể là kĩ sư, thợ kĩ thuật chuyên môn; giỏi quản lí tài chính, chú trọng hưởng thụ, hưởng thụ có phong cách, xa hoa, rất xem trọng thể diện, thích phô trương; nên chú ý sức khỏe, dễ mắc bệnh, phải dùng thuốc mạnh; thích can thiệp vào sự nghiệp của người phối ngẫu.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Mệnh"){
                    $luanPhuMau .= "\r\n Hiếu thảo nhưng ưa cãi lí với cha mẹ, dễ xảy ra tranh chấp với trường bối";
                    $luanTatAch .= "\r\n sức khỏe không được tốt, thể chất kém, cơ thể nhiều nạn tai, dễ bị ngoại thương"; 
                    $luanQuanLoc .= "\r\n thi cử có thành tích tốt; dễ bỏ nghề nghiệp chính, làm nghề khác.";
                }
                // Hóa Khoa - mệnh
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Mệnh"){
                    $luanCungMenh .= "\r\n Bạn là người thông minh, hiền hòa, không tính toán so đo; cử chỉ có phong độ, nói năng nhã nhặn, lịch sự, nghiêm trang, phần nhiều là người hướng nội, có thanh danh" .
                    "\r\n Đôi khi bạn hơi khoe khoang, tự PR bản thân, tuyên dương ưu điểm của mình; nếu bản thân có thành tựu ba phần, bạn sẽ làm cho người khác cảm thấy mười phần ";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Mệnh"){
                    $luanHuynhDe .= "\r\n Bạn có anh chị em thông minh, có tài; ít anh em, bạn là quý nhân của anh em, hay giúp đõ anh em, anh em hòa thuận" ;
                    $luanQuanLoc .= "\r\n bạn đi làm công ti không lớn, nhưng phát triển tốt, thu nhập bình ổn, chi tiêu gia đình có kế hoạch, biết cân đối thu chi, bạn bè đều là người có học, hiền hòa";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Mệnh"){
                    $luanPhuThe .= "\r\n Người phối ngẫu của bạn là người thông minh, là người biết cảm thụ cái đẹp, thích thời trang, biết ăn diện" .
                    "\r\n Bạn có xu hướng muốn chăm sóc lo liệu cho người phối ngẫu, vợ chồng hòa hợp, kính nhau như khách" .
                    "\r\n dễ có đào hoa tình nhân ờ bên ngoài, nhưng người phối ngẫu sẽ phát hiện rất nhanh";
                    $luanQuanLoc .= "\r\n sự nghiệp, công việc phát triển bình thuận, không có sóng gió lớn, dễ được quý nhân tương trợ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Mệnh"){
                    $luanTuTuc .= "\r\n Con cái không nhiều, xinh đẹp dễ thương, hiền hòa lễ độ và có tài năng; bạn dạy con rất dân chủ, sẽ giao lưu thông cảm, giảng đạo lí, để chúng phát triển theo hướng của chúng" ;
                    $luanDienTrach .= "\r\n nhà cửa sạch sẽ, trang nhã"; 
                    $luanThienDi .= "\r\n Bạn có duyên với người chung quanh không tệ, có duyên với người khác giới, thích giao đu với người khác giới trẻ tuổi xinh đẹp hoặc tuấn tú; tiêu xài có kế hoạch.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Mệnh"){
                    $luanTaiBach .= "\r\n Thu nhập bình ổn, có tiền tiêu xài bình ổn, tuy không nhiêu, nhưng cũng không thiếu, cân đối thu chi, tiền có được có thể dự kiên, như tiền lương; lúc thiếu tiền dùng thì có thể xoay sờ được" .
                    "\r\n thường thường lúc quan trọng tiền mới được điều đên, phần nhiều là tiên lương của công việc kĩ thuật chuyên môn; bạn sẽ tiêu tiền vì tình, vì thể diện; thái độ xử sự hiền hòa lễ độ, có lí tính; đối với người phối ngẫu, người yêu sẽ tạo ra bầu không khí lãng mạn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Mệnh"){
                    $luanTatAch .= "\r\n Cơ thể cân đối, không cao to lắm, thể chất tốt, ít sinh bệnh, mắc bệnh cũng dễ chữa" ;
                    $luanCungMenh .= "\r\n Bạn có quý khí, gặp nạn tai có thể được quý nhân giúp đỡ; tính khí tốt, hiền hòa lễ độ, đối với cha mẹ, trưởng bối đều rất tôn kính" ;
                    $luanDienTrach .= "\r\n Bạn thích bầu không khí lãng mạn; phòng khách trong nhà sửa sang sạch sẽ, ngăn nắp, ấm cúng; mở tiệm nhỏ gọn mà đẹp, văn phòng làm việc hay công ti đều sạch sẽ nhưng không lớn"; 
                    $luanNoBoc .= "\r\n Bạn có thái độ đối với người khác giới hiền hòa lễ độ, không tùy tiện.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Mệnh"){
                    $luanThienDi .= "\r\n Ở bên ngoài có danh tiếng tốt, có quý nhân, dễ được người khác giúp đỡ, thái độ xử sự hiền hòa, có thể phùng hung hóa cát"; 
                    $luanTaiBach .= "\r\n thu nhập binh ổn, nhưng không nhiều lắm, có lợi nhỏ";
                    $luanPhuThe .= "\r\n Bạn luôn biết cách xử sự trong hôn nhân, tình cảm rất lí tính.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Mệnh"){
                    $luanNoBoc .= "\r\n Đối với bạn bè, bạn luôn có thái độ đối sử tốt, không so đo tính toán, không cố gây sự chú ý; có thể được bạn bè tương trợ, bạn bè là quý nhân, bạn bè hiền hòa lễ độ; có sở học chuyên môn, anh chị em có tài năng, danh tiếng; quan hệ tốt với chủ, bạn bè, đồng sự, xử sự hòa hợp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Mệnh"){
                    $luanQuanLoc .= "\r\n Hồi trẻ bạn khá chuyên tâm học hành, hòa hợp với bạn cùng trang lứa, khi đi làm cũng vậy hòa hữu với đồng nghiệp" .
                    "\r\n Bạn nên đi làm hường lương, công việc thuận lợi, nên theo làm việc trong lãnh vực văn hóa, giáo dục, nghề nghiệp có tính phục vụ; theo con đường học vấn dễ có danh tiếng, việc học phát triển đều, thành tích bình ổn" .
                    "\r\n Nếu tự sáng lập cơ nghiệp nên làm với quy mô nhỏ, không nên làm vói quy mô lớn, việc làm ăn bình thuận, không có lời to, nhưng cũng không thiếu hụt, chỉ không có trở ngại mà thôi; nêu đi làm hường lương, dễ được cấp trên trọng dụng, đề bạt, nâng đỡ"; 
                    $luanPhuThe .= "\r\n Bạn có nghĩ đến chuyện đào hoa bên ngoài, nhưng thông thường chỉ muốn mà thôi, không có hành động.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Mệnh"){
                    $luanDienTrach .= "\r\n Nhà ở không lớn nhưng đẹp và sạch sẽ, trang trí thanh nhã và ấm cúng, phong cách trí thức, thích cuộc sống có ý vị tình cảm"; 
                    $luanPhucDuc .= "\r\ngia lộc đối xử với nhau hòa hợp, có gia giáo" ;
                    $luanDienTrach .= "\r\n Nếu mua tậu nhà cừa không gặp rắc rối gì, có thể mua nhà trà góp hoặc vay tiền mua nhà; gia trạch bình an, ít có tai ách"; 
                    $luanThienDi .= "\r\n Bạn ra ngoài rất có duyên với người khác giới, đối xử với người khác giới lịch sự, hiền hòa lễ độ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Mệnh"){
                    $luanTaiBach .= "\r\n Nguồn tiền bình thuận, ổn định; lúc hường thụ sẽ cân đối thu chi, không tiêu xài loạn xạ, sử dụng đồng tiền có kế hoạch, suy nghĩ và tính toán tỉ mỉ, làm ăn thường không bị thiếu hụt tiền, không làm lỗ vốn, tính toán cân nhắc rõ ràng" ;
                    $luanCungMenh .= "\r\n Bạn có thị hiếu thanh nhã, tốt đẹp; việc học hành cũng không tệ, có tiếng tăm, thường thường sẽ có biệu hiệu hoặc được xưng tụng; cuộc sống hôn nhân êm ấm, ít có sóng gió.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Mệnh"){
                    $luanPhuMau .=  "\r\n Rất quan tâm chằm sóc cha mẹ; hiếu thuận, vâng lời, không gây chuyện phiền phức, dễ được trường bối nâng đỡ"; 
                    $luanQuanLoc .= "\r\n Bạn nên đi làm hường lương, dễ được đề bạt, nâng đỡ; lợi về thi cử, nhưng không nhất thiết là đứng đầu, nhưng sẽ vừa ý, không gặp trở ngại"; 
                    $luanCungMenh .= "\r\nBạn là người có phong thái nhã nhặn, tú lệ đoan trang, hiền hòa lễ độ, lịch sự.";
                }
                // Hóa Kỵ - mệnh
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Mệnh"){
                    $luanCungMenh .=  "\r\n Bạn là người có tính khí thất thường, cố chấp, ít tín nhiệm người khác, nặng tâm lí nghi kị; thiếu tự tin, hành sự trù trừ, bất định, không giỏi nắm bắt cơ hội" .
                    "\r\n Bạn dễ bị hoàn cảnh bên ngoài gây ành hưởng đến tâm trạng, duyên với người chung quanh hơi kém, bôn ba bên ngoài không được thuận lợi, hành hạn nhiều sóng gió trắc trờ" .
                    "\r\n tính tình thằng thắn, quý trọng tình người, không chiếm lợi ích của người khác, không thích mắc nợ ân tình; bản thân vất vá mà vẫn chăm lo người khác, chuyện gì đã cho là đúng thì kiên trì chấp hành, khó thay đổi" .
                    "\r\n Bạn đi ra ngoài dễ gặp tai họa hoặc sự cố giao thông; đời sống tình cảm không được êm ấm, vợ chồng duyên bạc. ";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Mệnh"){
                    $luanHuynhDe .= "\r\n Bạn là người có tình nghĩa với các anh chị em trong nhà, nhưng anh chị em không có giúp đỡ giúp đỡ, có tiền thì đến, dễ xảy ra phiền phức, cư xừ không tốt" ;
                    $luanNoBoc .= "\r\n  ít có bạn bè tri kỉ, thường xảy ra tranh chấp thị phi với người khác, tiền bạc qua lại sẽ có phiền phức" ;
                    $luanPhuThe .= "\r\n tình cảm vợ chồng khó giao lưu thông cảm, dễ xảy ra chuyện li hôn" ;
                    $luanTatAch .= "\r\n tình trạng sức khỏe không được tốt";
                    $luanQuanLoc .= "\r\n chi tiêu gia đình thắt chặt thu không bằng chi; nếu có kinh doanh buôn bán thì cửa hiệu, công ti nhỏ mà lộn xộn, thiếu tổ chức, phát triển không thuận lợi.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Mệnh"){
                    $luanPhuThe .= "\r\n Bạn và người phối ngẫu có nợ duyên từ kiếp trước, kiếp này lấy nhau để trả nợ." .
                    "\r\n Bạn sẽ rất yêu thương người phối ngẫu, nhưng cũng có nhiều lời than oán, vợ chồng ý kiến khó hòa hợp, không được người phối ngẫu giúp đỡ" .
                    "\r\n Nếu kết hôn sớm, khó sống với nhau đến đầu bạc, thường hay càm ràm, yêu mà rất đau khổ" ;
                    $luanQuanLoc .= "\r\n Bạn không nặng tinh thần sự nghiệp, gia đình và sự nghiệp khó lưỡng toàn; sự nghiệp không thuận lợi, thường hay thay đổi việc làm, nên đi làm hưởng lương, không muốn sáng lập cơ nghiệp " .
                    "\r\n tốt nhất là nên có công việc ổn định rồi hãy kết hôn, dễ vì vấn đề tình cảm hoặc gia đình mà dẫn đến tinh trạng công việc không được thuận lợi; nặng bệnh nghi ngờ, hay ghen tuông";
                    // Tìm xem trong cùng cung có Tự Hóa Lộc không
                    if (kiemTraTuHoaPhai($laSoData,"Mệnh","Tự Hóa Lộc","Chính cung")) {
                        $luanPhuThe .= "\r\n Vợ chồng dễ sống li thân";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Mệnh"){
                    $luanTuTuc .=  "\r\n Con cái không nhiều, rất yêu thương con cái nhưng luôn có tâm lý quản thúc con cái, dễ xây ra tình trạng đè nén không gian tự phát triển của con cái" ;
                    $luanThienDi .= "\r\n Bất kể có thích ở nhà hay không, bạn cũng sẽ thường đi ra ngoài một thời gian, ở bên ngoài thời gian dài, dễ lang bạt tha hương, khó có bất động sản, lúc chưa mua được nhà phải dời chuyển chỗ ở liên tục; dễ bị tổn thất tiền bạc, hao tài, khó tích lũy" .
                    "\r\n trong cuộc đời rất dễ gặp đại kiếp số; nếu hợp tác với người khác phần nhiều sẽ gặp sóng gió trắc trở, về sau hối hận";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Mệnh"){
                    $luanTaiBach .= "\r\n Kiếm tiền vất vả khổ sở, nỗ lực kiếm tiền, không đành tiêu tiền trong chuyện hưởng thụ, nhưng vẫn dễ bị thấu chi, không giữ được tiền" .
                    "\r\n thái độ của bạn đối với tiền bạc có nguyên tắc riêng của mình, nhưng tính cả nể tình cảm, hoặc lúc bất đắc dĩ cũng rất hào phóng, nhưng sau khi tiêu tiền lại hối hận; lúc đầu tư cho sự nghiệp thường không đủ vốn, còn dễ bị hao hụt" .
                    "\r\n hường vì tiền bạc mà chuốc thị phi, vì tiền mà hành động mạo hiểm"; 
                    $luanPhuThe .= "\r\n vợ chồng không hòa hợp, quản không được người phối ngẫu; ờ bên ngoài, thường tự gây ra thị phi.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Mệnh"){
                    $luanTatAch .= "\r\n Lúc còn nhỏ cơ thể ốm nhỏ, sức khỏe kém, thể chất yếu, dù không mắc bệnh nặng cũng ít khi rời lọ thuốc; tình cảm mong manh, tâm trạng hóa, tâm địa tốt, không có tâm cơ" .
                    "\r\n bản thân vất vả mà vẫn chăm lo người khác, thà bản thân chịu thiệt chớ không chiếm lợi ích của người khác" ;
                    $luanQuanLoc .= "\r\n Bạn thuộc tầng lớp lao động, có khuynh hướng làm việc như điên, không sợ thất nghiệp; nếu sáng lập cơ nghiệp sẽ khó chống đõ, không có \"tài khí\"; không có duyên với cha mẹ, lúc còn nhỏ dễ gặp tai kiếp bất ngờ; sinh hoạt tình dục có độ hòa hợp kém; vận thi cử không tốt, khó thi đậu.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Mệnh"){
                    $luanThienDi .= "\r\n Tâm chí bạn luôn ở bên ngoài, thường đi ra ngoài; tuy đi ra ngoài không được thuận lợi, nhưng vẫn phải đi, hơn nữa, xa quê hương càng xa càng tốt, thường hay bôn ba, không có quý nhân tương trợ" .
                    "\r\n có thế vì người ở bên ngoài làm cho cuộc đời gặp nhiều thăng trầm bất định, làm việc nhiều mà thành tựu ít; dễ có nạn tai bất ngờ, khó có đào hoa, tình cảm không được thuận lợi";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Mệnh"){
                    $luanNoBoc .= "\r\n Bạn có ít bạn bè, bạn bè thường không lâu dài, nhiều phiền phức; bạn đối với bạn bè rất trọng tình nghĩa, quan tâm lo lắng cho bạn bè, nhưng bạn bè lại không tốt hoặc không giúp đỡ; không nên cho bạn bè vay mượn, dễ có đi mà không trả lại.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Mệnh"){
                    $luanCungMenh .= "\r\n Bạn là người Dốc toàn bộ tinh thần vào công việc, có tính chuyên nghiệp, chuyện gì cũng đích thân làm" ;
                    $luanQuanLoc .= "\r\n Bạn nên đi làm hưởng lương, theo nghề buôn bán sẽ không ổn định, khó sáng lập cơ nghiệp, thời gian làm việc nhiều mà thu nhập ít" ;
                    $luanPhuThe .= "\r\n Lá số này thuộc cách cục chậm kết hôn, lập nên sự nghiệp trước rồi mới lập gia đình, vì công việc mà sơ sót chuyện tình cảm, công việc cũng không thuận lợi gặp nhiều sóng gió" ;
                    $luanQuanLoc .= "\r\n Hồi trẻ học hành vất và khổ sở, không nắm được mấu chốt sự việc, thành tích có thể đứng chót, dễ bỏ học, nghỉ học, chuyển trường.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Mệnh"){
                    $luanTaiBach .= "\r\n Bạn là người Biết kiếm tiền, xem tiền bạc như mạng sống, không dám tiêu xài, chỉ chi tiêu cho sinh hoạt gia đình" ;
                    $luanPhuThe .= "\r\n Bạn không thích đi xa, dựa vào cảm tính để xử lí chuyện gia đình, trong nhà thường không được yên ổn, hay cãi vả, náo loạn, không hòa hợp, thường hay xảy ra tranh chấp" ;
                    $luanQuanLoc .= "\r\n không nên hợp tác làm ăn, tay trắng làm nên, không tham tổ nghiệp; lúc mua tậu nhà cừa, không đủ tiền; sẽ có đào hoa, đối tượng phần nhiều là người đã có hôn nhân.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Mệnh"){
                    $luanCungMenh .= "\r\n Bạn là người dễ nổi nóng,không cởi mở, suy nghĩ cố chấp và tính khí không ổn định. " .
                    "\r\n hay đâm đầu vào những chuyện không giải quyết được; túi tiền thường trống rỗng, kiếm không ra tiên, ít được hường thụ, không có tiền cũng phải tiêu xài, kiêm được tiền cũng không giữ được, dễ vì tâm trạng không tốt mà tiêu tiền, dễ bị mất ngủ" ;
                    $luanPhuThe .= "\r\n Giữa vợ chồng thường xảy ra cãi vã vì chuyện tiền bạc; mỗi người đều có sở thích cố chấp riêng.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Mệnh"){
                    $luanPhuMau .=  "\r\n Duyên phận với cha mẹ mỏng manh, nhưng giữa hai thế hệ vẫn có sự giao lưu và cảm thông" .
                    "\r\n Bạn hiếu thuận, quan tâm chăm sóc cha mẹ, nhưng không cách nào sum họp lâu dài, sau khi lập gia đình sẽ không ở chung với cha mẹ, thường khắc khẩu với cha mẹ" ;
                    $luanCungMenh .= "\r\n Bạn có tâm địa tốt, không có tâm cơ, bụng dạ thẳng thắn, mau mồm mau miệng, dễ chuốc thị phi, hay xúc phạm hoặc làm phiền lòng người khác" ;
                    $luanTatAch .= "\r\n cơ thể suy nhược, cơ thể phần nhiều dễ bị nạn tai, dễ bị ngoại thương" ;
                    $luanQuanLoc .= "\r\n Trong công việc, hay xảy ra tình trạng không hợp ý kiến với cấp trên, khó được người ta đề bạt, nâng đỡ, công việc cũng hay biến động, hoặc thích hợp với công việc phải bôn ba nhiều ở bên ngoài" .
                    "\r\n Hồi trẻ vận thi cử không được tốt mặc dù học hnahf rất vất vả";
                } 
                // hóa lộc - huynh đệ
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Trong anh chị em, có người rất thông minh, có khẩu tài, rất có duyên với người chung quanh, đi xa cát lợi, vận trình tot đẹp" ;
                    $luanCungMenh .= "\r\n Bạn rất hiếu khách, tính tình rộng rãi, quan hệ giữa bạn với anh em hoặc bạn bè thân cận rất vui vẻ, chỉ cần trong tay đang có tiền, bạn bè cần giúp đỡ, bạn không bao giờ từ chối, mà cũng chẳng để ý đến chuyện viết giấy nợ hoặc thế chấp.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em đối xử rất hòa hợp với bạn, có phúc sẽ chia sẻ với bạn, có tiền cùng xài chung, rất quan tâm bạn.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Chị em dâu sống hòa thuận với nhau, gia đình vui vẻ hạnh phúc, anh chị em giúp đỡ lẫn nhau"; 
                    $luanQuanLoc .= "\r\n sau kết hôn tài vận tốt; người phối ngẫu được trưởng bối trông nom, có thể được hưởng tiền của hoặc di sản của cha mẹ.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Chị em dâu sống hòa thuận với nhau, gia đình vui vẻ hạnh phúc, anh chị em bận rộn trong sự nghiệp, thường đi xa, ra bên ngoài thuận lợi, có thể thu được lợi; anh chị em đi làm ăn, sẽ mang tiền của gia đình đi đầu tư."; 
                    $luanTaiBach .= "\r\n Cuộc sống gia đình sung túc, chi tiêu trong gia đình thoải mái không đến nỗi quá túng thiếu, nhưng cũng khó dành dụm";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em giúp đỡ lẫn nhau về tiền bạc, quan hệ giao tế của anh chị em rất tốt đẹp, có duyên với người khác giới, con cái của họ thông minh lanh lợi"; 
                    $luanTaiBach .= "\r\n Tài vận của bạn hưng vượng, làm ăn kiếm được tiên, tiền do bản thân bạn kiếm được cũng có thể là tiền trong ngành giải trí.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em có quan hệ xã hội không tệ, giao du với người ngoài khá hòa hợp, hòa khí sinh tài; bản thân bạn cũng được hưởng lợi từ các mối quan hệ này"; 
                    $luanTatAch .= "\r\n lúc còn nhỏ thể chất của bạn không được tốt, nhiều nạn tai bệnh tật, được cha mẹ trông nom chăm sóc"; 
                    $luanCungMenh .= "\r\n Bạn không keo kiệt với bản thân, ăn mặc dùng toàn đồ đắt tiền.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em là người lạc quan, ở bên ngoài có duyên với người chung quanh, rất quan tâm chăm lo cho bạn, sự nghiệp của bạn có anh em giúp đỡ, kiếm được tiền"; 
                    $luanThienDi .= "\r\n bạn ra ngoài có nhiều bạn bè, quan hệ giao tế tốt,biết chi tiêu nhiều cho gia đình.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em phần lớn đều vui vẻ, được quý nhân giúp đỡ, hướng ngoại, thường đi xa và kiếm tiền từ bên ngoài; sự nghiệp có cơ hội phát triển. bạn bè của bạn có thể giúp đỡ bạn kiếm tiền, tuy nhiên bạn lại chi tiêu khá nhiều tiền cho gia đình";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Huynh Đệ"){
                    $luanQuanLoc .= "\r\n Có vốn liếng làm ăn, sự nghiệp cũng từ đó mà vững vàng hơn";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Huynh Đệ"){
                    $luanQuanLoc .= "\r\n Có thể mờ tiệm kinh doanh, hoặc làm ăn liên quan đến bất động sản; tiền kiếm được sẽ lo cho gia đình hoặc dùng vào chuyện hợp lí; anh em có thể ở chung với nhau, chị em dâu hòa thuận.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .=  "\r\n Anh chị em có tiền, giúp đỡ lẫn nhau, con cái của họ có tài"; 
                    $luanTaiBach .= "\r\n Tiền bạn kiếm được phần nhiều tiêu vào sở thích cá nhân";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Huynh Đệ"){
                    $luanPhuMau .= "\r\n Tình cảm của cha mẹ tốt đẹp, anh chị em hiếu thảo với cha mẹ, Trong công việc được trưởng bối đề bạt, nâng đỡ; tài vận của bạn hưng vượng, có thể dựa vào trưởng bối, được bạn bè giúp đỡ kiếm tiền, nhưng cũng dễ người ta lừa đảo.";
                }
                // hóa quyền - huynh đệ
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Trong gia đình bạn thường hay xảy ra tranh chấp với anh chị em, anh chị em trong nhà tuy có năng lực nhưng tính tình thì bướng bỉnh" .
                    "\r\n Người bạn của bạn thường là những người có cá tính mạnh, có tài nhưng cũng ưa tạo sự chú ý" .
                    "\r\n Lúc ra bên ngoài tốt nhất bạn không nên tranh quyền với bạn bè, nếu không sẽ có nhiều thị phi; thường phải chi một khoản tiền lớn cho chi tiêu trong gia đình; cơ thể dễ bị tổn thương bất ngờ. ";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em có cá tính cương cường, cố chấp với ý kiến của mình, có tài năng, khá tự phụ, cũng thích tạo sự chú ý; ra ngoài gặp nhiều thị phi; có nhiều quan điểm khác với bạn, nhưng cũng sẽ hỗ trợ bạn; công việc của bạn khá bận rộn, nặng tinh thần trách nhiệm.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n giữa chị em dâu có nhiều ý kiến không hợp nhau; anh chị em trong nhà cũng cung cấp nhiều ý kiến cho sự nghiệp của bạn, sẽ có trợ giúp.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Chị em dâu có nhiều ý kiến khó hợp nhau, anh chị em trong nhà cũng khá phách lối, hay ra oai, lúc cha mẹ phân chia tài sản sẽ có tình trạng so bì" ;
                    $luanCungMenh .= "\r\n Bạn có năng lực sáng lập cơ nghiệp, tiền bạn kiếm được thường phải tiêu một khoản tiền lớn, tuy không bị thiếu hụt, nhưng cũng khó dành dụm.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Huynh Đệ"){
                    $luanTaiBach .= "\r\n Bạn biết vận dụng đồng vốn rất linh hoạt, lấy tiền đẻ ra tiền, lấy tiền dành dụm ra đầu tư, sáng lập cơ nghiệp" .
                    "\r\n Khi hợp tác với anh chị em sẽ xuất nhiều vốn hơn, là cổ đông lớn; lúc giao du sẽ có tác phong ở thế mạnh.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em phần nhiều đều có cá tính mạnh, thể lực tốt, sức hoạt động khá mạnh, cũng dễ bị ngoại thương" ;
                    $luanPhuMau .= "\r\n Bạn hiếu thảo với cha mẹ, nhưng cũng có mâu thuẫn; bạn thường mang tiền kiếm được tiêu xài cho việc giữ gìn sức khỏe, mua thực phẩm bổ dưỡng.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em thường áp đảo, gây nhiều áp lực và có nhiều ý kiến đối với bạn" .
                    "\r\n anh chị em của bạn lúc còn nhò rất nghịch ngợm, dễ bị tốn thương, nhưng có tài năng, được người ta xem trọng";
                    $luanCungMenh .= "\r\n bản thân bạn cũng hiếu cường, ờ bên ngoài ưa cạnh tranh với người khác; tiền bạn kiếm được sẽ bị người phối ngẫu quản lí, phải chi ra một khoản tiền lớn cho việc chi dụng trong gia đình.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em rất có năng lực, thích tranh cường hiếu thắng, dễ chuốc thị phi" ;
                    $luanQuanLoc .= "\r\n Bạn cũng có năng lực làm việc, sự nghiệp có cạnh tranh, có thể được khẳng định; chi tiêu trong gia đình tuy nhiều, nhưng không đến nồi mất kiểm soát.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Huynh Đệ"){
                    $luanQuanLoc .= "\r\n Bạn sẽ mang tiền dành dụm ra đâu tư, họp tác với anh chị em hoặc bạn bè sẽ chiếm nhiêu vốn hơn, nhưng không làm người gánh trách nhiệm; sự nghiệp có phát triển, có thể kiếm tiền, sẽ mở rộng.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Huynh Đệ"){
                    $luanDienTrach .= "\r\n Anh chị em nắm quyền trong gia đình, lúc cha mẹ phân chia tài sản sẽ có tranh chấp, chị em dâu ở chung không hòa hợp; bạn có thể mở tiệm kinh doanh hoặc mua bán bất động sản; tiền kiếm được sẽ chi dụng trong gia đình, hoặc mua tậu bất động sản hay dùng cho việc hợp lí khác.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .=  "\r\n Anh chị em có năng lực quản lí tài chính, hợp tác làm ăn với người khác vừa xuất vốn vừa ra sức, cũng chú trọng chuyện phô trương; ";
                    $luanCungMenh .= "\r\n Năng lực kiếm tiền của bạn không tệ, nhưng cũng dám tiêu tiền cho việc hưởng thụ, cho sở thích hoặc dùng PR bản thân";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Huynh Đệ"){
                    $luanPhuMau .= "\r\n Cha mẹ là người có cá tính mạnh, thương có cãi vã, việc kết hôn của họ là quyết định đột ngột kiểu tiếng sét ái tình"; 
                    $luanHuynhDe .= "\r\n Anh chị em hiếu thảo với cha mẹ, nhưng hay cãi lí"; 
                    $luanCungMenh .= "\r\n Bạn rất biết kiếm tiền, thường kiếm được tiền trong tình hình cạnh tranh.";
                }
                // Hóa Khoa - huynh đệ
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em trong nhà có tính khí tốt, hiền hòa lễ độ, thanh tú, đoan trang, có mối quan hệ khá tốt với bạn" ;
                    $luanThienDi .= "\r\n Biểu hiện bề ngoài của bạn khá ôn hòa nho nhã, cũng thường giao du với bạn bè có tu dưỡng tính tình, có học thức, giao du với bạn bè khá hòa hợp, ít có xung đột" ;
                    $luanTatAch .= "\r\n cơ thể của bạn ít có tai ách nghiêm trọng"; 
                    $luanTaiBach .= "\r\ntình hình chi tiêu trong gia đình bình ổn, không thiếu thốn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em trong nhà sống hòa thuận với nhau, gặp chuyện cấp bách cũng sẽ trợ giúp bạn" ;
                    $luanQuanLoc .= "\r\n công việc, sự nghiệp của bạn đêu bình ổn, kiếm được tiền nhưng cũng không nhiều lắm; có thể dựa vào tài năng và kĩ thuật chuyên môn để kiếm tiền; sinh hoạt gia đình bình ổn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Huynh Đệ"){
                    $luanPhuThe .= "\r\n Người phối ngẫu của bạn sống khá hòa thuận với các anh chị em trong gia đình, chị em dâu sống hòa thuận với nhau" ;
                    $luanHuynhDe .= "\r\n Đối với sự nghiệp của bạn, anh chị em trong gia đình cũng có góp ý và giúp đỡ bạn" ;
                    $luanPhuThe .= "\r\n Trong công việc của người phối ngẫu, phần nhiều được cấp trên quan tâm, đề bạt giúp đỡ, thích hợp làm công chức, theo sự nghiệp nhà giáo";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em dâu rể phần nhiều đều có gia thế thanh bạch, hiền hòa lễ độ, khiêm tốn; chị em dâu hòa thuận" ;
                    $luanTaiBach .= "\r\n Bạn cũng là người biết chi tiêu hợp lý, tiền tích lũy được tiêu hao không nhiều, không lo thiếu thốn tiền chi dụng trong sinh hoạt.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em trong gia đình quản lí tài chính có kế hoạch, nguồn tiền bình thuận"; 
                    $luanTaiBach .= "\r\n bạn cũng là người giỏi sử dụng đồng vốn cân đối thu chi, dùng tiền đẻ ra tiền, nhưng không đầu cơ mạo hiểm, vận dụng rất ổn thỏa.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Đa số anh chị em đều khỏe mạnh, ít bệnh đau, gặp nạn sẽ có có quý nhân giúp đỡ"; 
                    $luanTatAch .= "\r\nbản thân bạn tuy không thấy cường tráng nhung cũng ít bệnh đau, có thể phùng hung hóa cát, dù có nạn tai bệnh tật, cũng dễ chữa khỏi, lúc bình thường cũng biết trông nom sức khỏe.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em của bạn ra ngoài phần nhiều đều được quý nhân xem trọng, cũng rất quan tâm và có thể trợ giúp bản thân bạn"; 
                    $luanPhuThe .= "người phối ngẫu của bạn biết quản lí tài chính có kế hoạch, không cố theo đuổi chuyện gì, cũng không lãng phí, cuộc sống gia đình hòa hợp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em của bạn có tài năng, ở bên ngoài có thanh danh, thích hợp làm việc trong lãnh vực văn giáo, không giao du bạn xấu, mà có bạn bè quan tâm lẫn nhau"; 
                    $luanQuanLoc .= "\r\ntrong sự nghiệp và công việc, bạn có biểu hiện khá tốt, có nhiều sự giúp đỡ từ cấp trên đồng nghiệp, cuộc sống gia đình cũng bình an.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em phần nhiều đều có sự nghiệp bình ổn, được nhiều quý nhân giúp đỡ, thích hợp làm việc trong lãnh vực văn giáo và làm công chức"; 
                    $luanQuanLoc .= "\r\nbạn giỏi đầu tư kiểu có nguy cơ thấp nhất; anh em và bạn bè có giúp đỡ bạn trong sự nghiệp và công việc; việc làm thuận lợi, nhậm chức trong công ty khá ổn định.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em trong nhà cư xừ tốt với nhau, phần lớn thuộc nhóm người đi làm hưởng lương, nghề nghiệp có khuynh hướng làm việc trong văn phòng"; 
                    $luanTaiBach .= "\r\nbạn tiều xài tiền không nhiều, dành dụm hợp lí, sừ dụng tiền có kế hoạch, cuộc sống gia đình bình yên thuận lợi, không lãng phí.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em bạn là người tiêu tiền có kế hoạch, tiết kiệm, cách hành sự phần nhiều là làm từng bước vững chắc" ;
                    $luanTaiBach .= "\r\n Bạn có thói quen dành dụm tiền bạc theo kế hoạch, cũng hơi so đo tính toán";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .=  "\r\n Anh chị em phần nhiều đều quan tâm và hiếu thảo với cha mẹ, có thị hiếu thanh nhã, có tài nghệ; tình cảm của cha mẹ rất tốt"; 
                    $luanTaiBach .= "\r\nbạn với bạn bè có tình nghĩa qua lại tiền bạc, tiền chi tiêu cho cuộc sống bình ổn.";
                }
                // Hóa Kỵ - huynh đệ
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .=  "\r\n Trong số anh chị em của bạn có thể có người yểu mệnh do mẹ sinh non hoặc do thể chất yếu từ khi sinh ra" .
                    "\r\n Trong số anh chị em có người tính tình kỳ quặc, thiếu tự tin, vận đào hoa không thuận lợi, thường gặp phải rắc rối và tai tiếng.";
                    $luanTaiBach .= "\r\n Tiền bạn kiếm được thường không giữ được, kiếm được bao nhiêu tiêu xài hết bấy nhiêu, bản thân bạn tiêu xài tiền của mình, tốt nhất là đừng qua lại tiền bạc với bạn bè, dễ chuốc phiền phức" .
                    "\r\n cơ cấu sự nghiệp, công việc hoặc chỗ làm việc thường có biến động, không ổn định; nếu giao dịch bất động sàn sẽ dễ bị lỗ, hoặc chỗ ờ thường là phải thuê.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em thường bôn ba vất vả, làm ăn không được thuận lợi, phài dựa vào bạn giúp đỡ, hoặc bạn không có duyên với anh chị em, ít sum họp";
                    $luanThienDi .= "\r\n Quan hệ xã giao của bạn không được tốt đẹp đi xa, ra bên ngoài sẽ gặp nhiều sóng gió, làm nhiều mà hưởng ít, tiền khó tụ, đừng cho ai mượn tiền để kiếm lời, trong cuộc đời rất dê gặp tai ách, kiếp số; không gian làm việc khó phát huy.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em trong nhà thường kết hôm muộn hoặc hôn nhân không hạnh phúc";
                    $luanPhuThe .= "\r\n người phối ngẫu của bạn xử sự không tốt, không giúp đỡ được cho sự nghiệp cùa bạn";
                    $luanQuanLoc .= "\r\n bạn không nên hợp tác hoặc đầu tư, cũng không nên tự sáng lập cơ nghiệp, sự nghiệp nhiều thăng trầm, dễ có nguy cơ phá sản.Nên làm công ăn lương";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .=  "\r\n Cuộc đời của anh chị em trong nhà được đánh giá là khá kém, hôn nhân khó hạnh phúc, bôn ba ở bên ngoài rất sớm, sẽ rời quê hương đi xa để tìm hướng phát triển" .
                    "\r\n dễ vì chuyện phân chia gia sản mà xảy ra tranh chấp, phiền phức, rắc rối" ;
                    $luanTaiBach .= "\r\n bạn phải tiêu tốn nhiều tiền cho sinh hoạt gia đình, bạn nên chú ý vấn đề dạy dỗ con cái.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em phần nhiều đều dễ xảy ra vấn đề tài chính, thậm chí liên lụy đến bạn" ;
                    $luanNoBoc .= "\r\n bạn qua lại tiền bạc với bạn bè cũng dễ bị thua thiệt; bạn có khuynh hướng tiêu xài tiền không ngừng, phải biết tiết chế mới tốt";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .=  "\r\n Hầu hết anh chị em có sức khỏe yếu, không hợp ý kiến với cha mẹ, ít khi gặp gỡ nhau. Các anh chị em có khuynh hướng muốn nhờ cậy bạn";
                    $luanPhuMau .= "\r\n Bạn ít gần gũi với bố mẹ hoặc là hay bất đồng quan điểm với cha mẹ" ;
                    $luanTaiBach .= "\r\n Bạn kiếm tiền khá vất và khổ sờ, dễ có nguy cơ về tài chính; nội bộ trong sự nghiệp hoặc ờ nơi làm việc (công ty...) dễ xảy ra vấn đề; bạn cũng sẽ tiêu tiền nhiều cho việc giữ gìn sức khỏe.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em thường đi xa, có thể rời xa gia đình rất sớm để tìm hướng phát triển, không có duyên với bạn, ít gặp nhau thì tốt hơn, nếu không sẽ dễ xảy ra tranh cãi; hoặc bạn ít anh em, cũng có thể không có anh em" ;
                    $luanThienDi .= "\r\n Khi ở bên ngoài, bạn thường chi tiêu nhiều cho các mục đích xa hoa và dễ bị tiêu tốn vốn. Tốt nhất là không nên hợp tác, tránh vay mượn và nên dành tiền kiếm được cho người phối ngẫu quản lý" ;
                    $luanQuanLoc .= "\r\n sự nghiệp của bạn nhiều thăng trầm lớn, không ổn định, đi xa dễ bị bệnh đau, dễ mệt mỏi, ờ bên ngoài phải cẩn thận, dễ có tai kiếp lớn";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em là người hướng ngoại, thường đi xa, phần nhiều đêu rời xa gia đình, có cách sống riêng, anh em ít qua lại với nhau, ở bên ngoài bôn ba nhiều" ;
                    $luanTaiBach .= "\r\n Bạn không giữ được tiền, dễ bị tổn that vì anh em bạn bè, dẫn đến khủng hoảng tài chính, có nguy cơ phải xoay sở tiền bạc, công việc hay sự nghiệp vì vậy mà gặp cảnh khó khăn.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Hôn nhân của anh chị em trong gia đình phần nhiều không được êm ấm,công việc hay sự nghiệp không ổn định, thu nhập cũng không được nhiều, các anh em cũng không giúp đỡ bạn nhiều trong lúc khó khắn" ;
                    $luanQuanLoc .= "\r\n công việc hay sự nghiệp của bạn phát triển không thuận lợi, vất vả mà thu nhập ít, đầu tư làm ăn thì khó thu hồi vốn. ";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n Anh chị em tuy xem trọng quan niệm gia đình, thương yêu gia đình, nhưng thường bôn ba ở bên ngoài, có khuynh hướng thích làm việc trong văn phòng, hoàn cảnh sống của anh chị em lúc còn nhỏ khá chật vật, thường hay gây gổ nhau, không hòa thuận" ;
                    $luanTaiBach .= "\r\n tiền bạn kiếm được phần nhiều chi dụng trong sinh hoạt gia đình, hoặc mua tậu bất động sản, cho vào kết sắt" .
                    "\r\n có thể bạn là người giữ tiền rất nghiêm ngặt, nhưng thường vì tình trạng bất dắc dĩ mà phải chi ra, không dành dụm được"; 
                    $luanTuTuc .= "\r\nbạn cần phải chú ý vấn đề dạy dỗ con cái, con cái có thể hư hỏng.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .= "\r\n \"Tài khí\" của anh chị em không vượng, còn có thể gầy lụy đến bạn, trong lòng họ thường thầm lo lắng không yên" ;
                    $luanTaiBach .= "\r\n Bạn dễ bị tổn thất tiền bạc, hợp tác làm ăn với người khác hoặc sự nghiệp của bạn đều rơi vào tình trạng không phát triển, đầu tư lỗ vốn, lúc tâm trạng không được tốt, bạn sẽ có hiện tượng tiêu xài tiền loạn xạ.Mượn không được tiền hoặc khó mượn tiền của bạn bè để xoay sở";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Huynh Đệ"){
                    $luanHuynhDe .=  "\r\n Sức khỏe của hầu hết anh chị em không tốt, có người yếu mạng, mâu thuẫn với cha mẹ, sau khi lập gia đình có thể vẫn sống chung với cha mẹ tuy nhiên hay bất đồng quan điểm với cha mẹ và ít khi có ý kiến chung nhau." ;
                    $luanTaiBach .= "\r\n thu nhập của bạn không được ổn định, công việc hay sự nghiệp dễ có nguy cơ đi xuống; bạn qua lại tiền bạc với bạn bè sẽ không được như ý, lúc muốn mượn tiền của bạn bè để xoay sở sẽ không được, mà cho bạn bè mượn tiền thì khó đòi.";
                } 
                // hóa lộc - phu thê
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có tính tình hiền hòa, có duyên với người chung quanh, duyên vợ chồng tình thâm, ân ái hạnh phúc, trông nom cho nhau, có giúp đỡ" ;
                    $luanQuanLoc .= "\r\n sau khi kết hôn sự nghiệp của bạn khá thuận lợi, kiếm được nhiều tiền; nếu bạn tự sáng lập cơ nghiệp, người phối ngẫu sẽ giúp vốn, giúp sức; hôn nhân của bạn là do yêu nhau mà kết hợp";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Bạn rất có duyên với người khác giới, có duyên vợ chồng với người phối ngẫu; người phối ngẫu có tình yêu sâu đậm với bạn, trợ giúp bạn về sự nghiệp, làm việc không ngại gian khổ, không oán không trách, tình cảm vợ chồng rất tốt đẹp, có thể sống với nhau đến đầu bạc" ;
                    $luanTaiBach .= "\r\n sau khi kết hôn bạn có tài vận tốt, công việc hay sự nghiệp có phát triển.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với sếp, trưởng bối, có thể được đề bạt, nâng đỡ, sự nghiệp có thành tựu, có thể gánh vác sinh kế gia đình" .
                    "\r\n Người phối ngẫu có thể thường vui vẻ khi giao tiếp với anh em bạn bè của bạn" ;
                    $luanQuanLoc .= "\r\n sau kết hôn, công việc hay sự nghiệp của bạn sẽ phát triển thuận lợi.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất yêu thương và quan tâm chăm sóc cho con cái, nhưng khi ra ngoài thường chi tiêu nhiều và gặp nhiều rắc rối." .
                    "\r\n Sau khi kết hôn, vì công việc hoặc sự nghiệp, bạn cũng thường tham gia và chi tiêu nhiều cho các hoạt động xã giao. Cả vợ lẫn chồng đều có sức hút với người khác giới, và sau khi kết hôn vẫn duy trì quan hệ bạn bè với người khác giới, dễ dẫn đến việc phát triển mối quan hệ ngoài luồng hoặc ngoại tình. Người phối ngẫu thường thân thiện và hòa thuận với anh em bạn bè của bạn";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có tài vận tốt và sở hữu nhiều tài sản hơn bạn, sau khi kết hôn, bạn có thể nhờ người phối ngẫu hỗ trợ để gia tăng tài lộc" .
                    "\r\n Tình cảm vợ chồng hòa thuận, người phối ngẫu giỏi trong giao tiếp và hỗ trợ bạn phát triển sự nghiệp." .
                    "\r\n Người phối ngẫu thường thành công trong việc kiếm tiền, có xu hướng tự lập nghiệp và thích thú với việc hưởng thụ cuộc sống.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu tính tình lạc quan, dễ phát phì, năng lực tính dục mạnh, sẽ đeo dính bạn, nhưng không tạo áp lực cho bạn, có nhiều con cái và cũng gánh vác nhiều trách nhiệm.. Bạn có hứng thú với chính giới, rất có duyên với người khác giới, dễ có cơ hội đào hoa,bạn có lòng thương người, quan tâm nhiều về sức khỏe của mình.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có tính hướng ngoại, duyên ở bên ngoài khá tốt, đi xa vui vẻ, có nhiều quý nhân trợ giúp, thường thích đi du lịch" .
                    "\r\n Bạn có duyên với người khác giới, hôn nhân đến sớm, được nửa kia quan tâm, chăm lo, trợ giúp kiếm tiền, có điều kiện để hôn nhân hạnh phúc.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu đối xử rất tốt với bạn bè của bạn, có tính cách hướng ngoại, nhiều bạn bè, giỏi giao tiếp, lạc quan, dễ tăng cân, và có duyên với các bậc trưởng bối" ;
                    $luanTaiBach .= "\r\n Bạn biết kiếm tiền, nhưng cũng dễ bị người lừa tiền; công việc hay sự nghiệp của bạn gặp nhiêu co hội lốt, có không gian để phát triển, có thể làm ăn mua bán.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với người chung quanh, quan tâm sự nghiệp của bạn, có giúp đỡ cho sự nghiệp của bạn" .
                    "\r\n Người phối ngẫu nặng tinh thần sự nghiệp, có hứng thú với nhiều lãnh vực, công việc hay sự nghiệp đêu vừa ý, vùi vẻ trong công việc, làm việc bận rộn, đi làm có lương cao, thăng tiến nhanh.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất chăm lo cho gia đình, rất có duyên với người khác giới, nhiều bạn bè, cuộc sống vợ chồng khá hạnh phúc.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Phu Thê"){
                    $luanPhuThe .=  "\r\n Người phối ngẫu có phúc khí, được hường thụ, cuộc sống vợ chồng khá hạnh phúc, người phối ngẫu có sự nghiệp riêng của mình, còn có thể chi viện tiền bạc cho bạn.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có mối quan hệ tốt đẹp với cha mẹ và bậc trưởng bối của bạn, là người thông minh, biết kiếm tiền, quan tâm chăm sóc gia đình của bạn, có tiền mang về nhà.";
                }
                // hóa quyền - phu thê
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có cá tính mạnh, tính bướng bỉnh, có năng lực, có tài năng, có thể giúp bạn sáng lập cơ nghiệp, thích can dự vào sự nghiệp của bạn." .
                    "\r\n Hôn nhân của bạn là kiểu tiếng sét ái tình, mới gặp đã yêu; cũng có thể vì người nhà ép buộc hoặc lỡ có con mà phải kết hôn; vợ chồng không nhường nhịn nhau, dễ xảy ra tranh chấp, cãi vã. ";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có thế mạnh, sẽ quản thúc bạn, giữa vợ chồng dễ có tranh chấp; lúc người phối ngẫu ra bên ngoài, đều có mục tiêu rõ ràng, khá chủ động, tích cực; về sự nghiệp hay công việc đều có tác phong hành sự quả quyết, rõ ràng và mau lẹ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu nắm quyền về sinh kế gia đình, thường có nhiều ý kiến về anh chị em của bạn, nhưng thái độ xử sự rất tốt đẹp, có thể cùng nhau bàn bạc nói chuyện" ;
                    $luanQuanLoc .= "\r\n  Công việc hay sự nghiệp của bạn cũng tiến hành thuận lợi, có dục vọng về quyền lực, thường xảy ra tranh chấp với cấp trên.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu dạy dỗ con cái khá nghiêm khắc, có nhiều ý kiến và yêu cầu cao đối với gia đình, nhung cũng rất quan tâm chăm lo cho gia đình; người phối ngẫu là người có nhu cầu cao trong chuyện chăn gối; mong muốn có nhiều bất động sản.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu giỏi quản lí tài chính, nắm quyền về kinh tế, có thể giúp bạn kiếm tiền; thái độ xử sự với người khác ở thế mạnh, nặng phô trương, xem trọng thể diện, chú trọng hưởng thụ, sẽ quản thúc bạn nhưng cũng chăm lo cho bạn.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có thân thể cường tráng, làm việc chăm chỉ, cá tính mạnh, không dễ nghe lời khuyên của người khác; sẽ đeo dính bạn, nhưng cũng bắt bạn làm việc. bạn có hứng thú với chính giới.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu ở bên ngoài thích được người ta khẳng định, ưa tranh cường hiếu thắng, ưa ra lệnh; cũng rất thích quản thúc bạn, nhất là hoạt động giao tế ở bên ngoài. ";
                    $luanQuanLoc .= "\r\nbạn thường vì đi làm việc ở bên ngoài mà phải bôn ba vất vả, thích hợp công tác ngoại vụ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu không tùy tiện giao du bạn bè, mà có chọn lựa, rất trung thành với bạn bè; nhưng cũng có cạnh tranh, có nhiều ý kiến tranh luận với bạn bè; đối với vấn đề kiếm tiền nuôi gia đình thì không được vừa ý, nhưng cũng có thể gánh vác; đối với anh em bạn bè của bạn tuy có nhiều ý kiến, nhưng vẫn xừ sự tốt.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu hành sự tích cực, tràn đầy năng lượng, có giúp đỡ cho sự nghiệp của bạn, phàm chuyện gì cũng ưa chiếm thượng phong, giữa vợ chồng thường xảy ra tranh chấp; thích sáng lập cơ nghiệp, có thể nắm quyền, tài năng và năng lực đều có thể được khẳng định.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu ờ nhà hay ra oai, nắm quyền, có sức ảnh hường, ưa ra lệnh, nghiêm khắc với con cái. bạn có thể nhờ người phối ngẫu trợ giúp mở tiệm làm ăn, kiếm tiền mua tậu nhà cửa.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Phu Thê"){
                    $luanPhuThe .=  "\r\n Người phối ngẫu có vận sự nghiệp tốt, cũng có thể giúp bạn phát triển, chú trọng hưởng thụ, vì thể diện có thể tiêu xài một khoản tiền lớn, nhưng cũng có năng lực quản lí tài chính rất tốt.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có sức ảnh hưởng trong gia đình của hai người, có nhiều ý kiến không hợp với cha mẹ của bạn, nhưng vẫn cư xử phải đạo, lại rất quan tâm chăm lo cho bạn.";
                }
                // Hóa Khoa - phu thê
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu là người lịch sự và nhã nhặn, đẹp trai/đẹp gái, quý trọng hình ảnh cá nhân, được nhiều người yêu mến và có năng khiếu văn nghệ. Họ có gia thế thanh cao và thường là con của gia đình trí thức" .
                    "\r\n Hôn nhân của bạn thường được hình thành thông qua các đồng sự, đồng hương hoặc giới thiệu từ người khác. Hôn nhân của bạn dễ bị can thiệp bởi người thứ ba và có thể bị phát hiện nhanh chóng. ";
                    $luanQuanLoc .= "\r\n Công việc của bạn thường suôn sẻ, ít gặp phải khó khăn, và bạn có thể thành công và có danh tiếng trong sự nghiệp mà không mở rộng quá nhiều.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Vợ chồng hai người đều có sức hút với người khác giới, dễ có người thứ ba xen vào, mặc dù có thể gặp khó khăn và sóng gió, tình cảm giữa hai người vẫn sẽ không mất đi và có thể sống bên nhau đến cuối đời.";
                    $luanQuanLoc .= "\r\n Về sự nghiệp, người phối ngẫu có thể giúp bạn xoay sở vốn làm ăn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu đối xừ hiên hòa với anh em bạn bè của bạn; công việc hoặc sự nghiệp của họ luôn ổn định, có kế hoạch rõ ràng để bảo đảm cuộc sống gia đình.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu dạy dỗ con cái rất sáng suốt, đúng cách, khiến cho chúng có không gian tự phát triển. Vợ chồng hai người đều rất có duyên với người khác giới, sau kết hôn vẫn còn có người theo đuổi.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất giỏi trong việc quản lý tài chính và lập kế hoạch, tính toán rõ ràng, và có thể hỗ trợ bạn trong việc di chuyển vốn lượng. Họ xử sự rất lịch sự, hiền hòa và thông minh. Vợ chồng họ tôn trọng lẫn nhau và luôn giữ hình ảnh mới mẻ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu hiền hòa, sáng suốt, có lý lẽ, tôn trọng và quan tâm chăm sóc bạn, biết nghe lời bậc trưởng bối và đối xử rất tốt với anh em bạn bè của bạn.";
                    $luanQuanLoc .= "\r\n bạn có niềm đam mê với hoạt động trong lĩnh vực chính trị.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu ra bên ngoài khá thuận lợi, gặp nhiều quý nhân, có hình tượng tốt, có tiền riêng, quan tâm chăm sóc bạn, thái độ hiền hòa, nhã nhặn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Bạn bè của người phối ngẫu phần lớn đều là người có địa vị hoặc là người có thái độ hiền hòa lễ độ, xử sự hòa hợp, có thể giúp đỡ lẫn nhau; về công việc hay sự nghiệp, người phối ngẫu có thể được cấp trên hay đồng nghiệp giúp đỡ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với người chung quanh, có giúp đỡ cho sự nghiệp công danh cùa bạn; về phương diện sự nghiệp hay công việc cùa họ, đêu có thể phát huy tài năng, được người khác công nhận.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với người khác giới, biết trông nom lo liệu cho gia đình, sinh hoạt gia đình thư thả và thoải mái.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có ngoại hình ưa nhìn, có gu thẩm mỹ tinh tế, làm việc có kế hoạch, chú trọng đạo đức sống, biết cân đối thu chi. Công việc và sự nghiệp của họ đều ổn định, có thể giúp bạn lập kế hoạch tài chính và hỗ trợ trong sự nghiệp";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Phu Thê"){
                    $luanPhuThe .=  "\r\n Người phối ngẫu là con nhà có gia giáo, đối xử vui vẻ với cha mẹ của bạn, quan hệ rất tốt với bậc trưởng bối, có thể được cấp trên đề bạt, nâng đỡ trong công việc";
                }

                // Hóa Kỵ - huynh đệ
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Phu Thê"){
                    $luanPhuThe .=  "\r\n Người phối ngẫu có tính tình thẳng thắn , nhưng thiếu tự tin, hơi bi quan , dễ chuốc oán với tiểu nhân, thể chất yếu, nhiều bệnh đau, không giúp đỡ được cho công việc hay sự nghiệp của bạn" .
                    "\r\n bạn thường khó tìm người để kết hôn. Có thể không có cơ hội kết hôn và sau khi kết hôn, có nguy cơ gặp phải xung đột, thường vì những vấn đề nhỏ nhặt mà tranh cãi, dẫn đến hôn nhân không hạnh phúc.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Tốt nhất là bạn nên kết hôn muộn, kết hôn sớm thường thường sẽ khó sống với nhau đến đầu bạc";
                    if($gt == "false"){
                        $luanPhuThe .= "\r\n Bạn rất dễ vó 1 hôn  nhân không toại ý, vợ chồng có nhiều lời oán trách nhau";
                    }
                    $luanPhuThe .= "\r\n Sau hôn nhân, công việc của bạn phát triển không mấy thuận lợi";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu khó hòa hợp với anh em bạn bè của bạn, con đường giao lưu cảm thông giữa vợ chồng dễ bị đứt đoạn giữa chừng" ;
                    $luanQuanLoc .= "\r\n sau kết hôn sinh kế gia đình dễ bị rơi vào cảnh khó khăn; trước khi kết hôn cũng gặp nhiều sóng gió trắc trở, hoặc khó có hôn nhân; sự nghiệp làm ăn không thuận lợi.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Phu Thê"){
                    $luanPhuThe .=  "\r\n Người phối ngẫu quàn tâm lo liệu cho con cái thái quá, gây áp lực lớn cho con cái, tạo ra sự ngăn cách giữa hai bên, cách xử sự không được tốt đẹp" .
                    "\r\n người phối ngẫu không thích ngồi đợi ở nhà, thường ra ngoài, đi xa." ;
                    $luanThienDi .= "\r\n Lúc đi ra ngoài bạn nên cẩn thận, dễ xảy ra sự cố giao thông." ;
                    $luanPhuThe .=  "\r\n Sau kết hôn gia đình không yên ổn, dễ có ngườithứ ba xen vào, có thể sẽ có hôn nhân lần thứ hai, quan hệ xã hội không được tốt.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Trước hôn nhân bạn đã từng xừ lí vấn đề tình cảm với bạn khác giới không thỏa đáng, nên đánh mất tình yêu" .
                    "\r\n Sau hôn nhân người phối ngẫu xem trọng tiền bạc, nhưng không giỏi quản lí tài chính, thường không giữ được tiền; tình càm vợ chồng không được tốt đẹp, sẽ vì tiền bạc mà cãi vả.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Phu Thê"){
                    $luanPhuThe .=  "\r\n Người phối ngẫu cá tính thẳng thắn, thể chất yếu, hay ghen tuông, sẽ đeo dính bạn, gây áp lực lớn cho bạn, dễ đánh mất tình yêu" .
                    "\r\n Lá số cho thấy thiếu duyên vợ chồng, dễ có tình hình sống chung như vợ chồng mà không kết hôn chính thức, nhưng nếu có kết hôn chính thức thì khó li hôn; sinh hoạt tính giao của vợ chồng không hòa điệu; sau kết hôn sẽ ra riêng, nhưng cuộc sống dễ xảy ra sóng gió.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu không giữ được tiền, thường bôn ba ở bên ngoài, ra ngoài dễ có tai ách; trước khi kết hôn phần nhiều người phối ngẫu đã từng có tình yêu." .
                    "\r\n bạn nên kết hôn muộn, vợ chồng duyên phận bạc, dễ bị tình trạng gặp nhau ít mà xa nhau nhiều" .
                    "\r\n nếu phối ngẫu là người xứ khác hoặc lớn hơn trên bảy tuổi, thì có thể sống với nhau lâu dài. " .
                    "\r\n Hôn nhân khó hạnh phúc, trước khi kết hôn tuy có đào hoa, nhưng cũng khó kết hôn.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có thể chất kém, khó mập; ít bạn bè, đối với bạn bè rất trọng tình nghĩa, nhưng thường gặp thị phi phiền phức, nếu có qua lại tiền bạc thì dễ bị thua thiệt" ;
                    $luanTaiBach .= "\r\n Sau kết hôn sinh kế gia đình dễ bị khủng hoảng, tiền giành dụm cũng dễ bị bạn bè lừa gạt; sự nghiệp gặp sóng gió, phát triển không thuận lợi.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có tính hướng ngoại, thường bôn ba ở bên ngoài, không giúp đỡ cho sự nghiệp của bạn" .
                    "\r\n  bạn sẽ sổng với người phối ngẫu đến già, và cũng cãi nhau đến già. " .
                    "\r\n Người phối ngẫu dốc toàn bộ tinh thần vào công việc, có tính chuyên nghiện, nhưng công việc hay sự nghiệp không ổn định, thường có tình trạng bò dở nửa chừng. " ;
                    $luanThienDi .= "\r\n Lúc ra ngoài bạn nên cẩn thận, phòng sự cố giao thông; nên kết hôn muộn.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n bạn thường kết hôn muộn, muộn thì có con trai. Người phối ngẫu tuy chăm lo cho gia đình, nhưng thường bôn ba ở bên ngoài." .
                    "\r\n  Tình cảm vợ chồng không được hòa hợp, hôn nhân dễ có người thứ ba xen vào, nhưng dù có tình nhân ở bên ngoài vẫn khó li hôn"; 
                    $luanThienDi .= "\r\nbạn ra ngoài phải cẩn thận phòng sự cố giao thông.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Phu Thê"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có tính cách hay thay đổi,  hay đâm đầu vào những chuyện không giải quyết được, tư tưởng dễ bị xung động, đời sống hôn nhân của hai người khồng được hạnh phúc, vợ chồng dễ vì tiền bạc mà cãi vả; người phối ngẫu không giữ được tiền, thường vì tâm trạng không tốt mà tiêu tiền để giải khuây.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Phu Thê"){
                    $luanPhuThe .=  "\r\n Người phối ngẫu có gia thế không tốt, thể chất yếu, xử sự không hòa hợp với cha mẹ của bạn. Vợ chồng duyên bạc, gần nhau ít mà xa nhau nhiều, dễ xảy ra tình trạng li hôn. Công việc hay sự nghiệp của người phối ngẫu không thuận lợi, khó được đề bạt, nâng đỡ; sau kết hôn sự nghiệp thường xuống dốc.";
                } 
                // hóa lộc - tử tức
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái thông minh lanh lợi, hiếu thảo; con cái có duyên phận rất sâu với bạn" ;
                    $luanPhuThe .= "\r\n bạn rất có duyên với người khác giới, dễ có đào hoa, giao du thân mật với bạn bè khác giới, dễ có hành vi vượt quá tình bạn, có thể kết hôn hơi sớm; cơ năng tính dục của bạn rất tốt; cần phải lưu ý, sinh con gái đầu dễ bị lưu sản" ;
                    $luanThienDi .= "\r\n bạn có quan hệ xã giao rất tốt, giao du nhiều; có nhiều cơ hội hợp tác sự nghiệp với người khác";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Tử Tức"){
                    $luanPhuThe .= "\r\n Bạn có số đào hoa, có duyên với người khác giới, nhiệt tình, ưa phong hoa tuyết nguyệt";
                    $luanTuTuc .= "\r\n Đối với con cái, bạn đối với con cái rất tốt, tình cảm thân mật";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Điền Trạch")){
                        $luanPhuThe .= "\r\n Tuy nhiên có tồn tại mối quan hệ ở chung như vợ chồng nhưng không muốn sinh con";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Phúc Đức")){
                        $luanPhuThe .= "\r\n Vợ chồng thường hay cãi vã nhau";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái của bạn có duyên với người chung quanh, nhiệt tình với mọi người, tình cảm giữa anh chị em rất tốt, xem trọng hường thụ, hơi lười biếng, kiếm tiền dễ, thích làm việc bằng đầu óc";
                    $luanTaiBach .= "\r\n Để kiếm được tiền bạn cần phải xây dựng mối quan hệ và giao tiếp xã hội, thích hưởng thụ cuộc sống, có nhiều mối quan hệ tình cảm, có thể làm việc trong ngành giải trí. bạn này ưa chuộng sự hào nhoáng, tâm lý không ổn định, có tính cách phong lưu và thanh lịch.";
                    if($gt == "false"){
                        if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Quan Lộc")){
                            $luanPhuThe .= "\r\n Bạn dễ bị cuốn vào cuôc sống phù hoa, bạn dễ là vợ lẽ";
                        }
                    }else{
                        if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Quan Lộc")){
                            $luanPhuThe .= "\r\n Bạn dễ kết duyên với phụ nữ đã li hôn hoặc đã từng có chồng";
                        } 
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái rất có duyên với người khác giới, trưởng thành sớm, tình cảm với cha me rất tốt đẹp, hiếu thuận, sự nghiệp thuận lợi, có tiền để hưởng thụ" .
                    "\r\n nhưng thể chất yếu, cần lưu ý thói quen ăn uống của chúng" ;
                    $luanPhuThe .= "\r\n Sau kết hôn, bạn vẫn rất có duyên với người khác giới, bạn dễ có tình nhân ở bên ngoài, sống chung như vợ chồng với người khác bên ngoài, hoặc vợ chồng bạn không có danh phận chính thức" .
                    "\r\n Cũng có người theo đuổi, dụ dỗ người phối ngẫu. Nếu sinh con gái đầu lòng, chú ý sức khỏe con cái";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Mệnh")){
                        $luanPhuThe .= "\r\n Bạn kết hôn muộn, khó kết hôn, trước khi kết hôn phần nhiều đã có ở chung như vợ chồng với người khác";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Tật Ách")){
                        $luanPhuThe .= "\r\n bạn có quan hệ ở chung như vợ chồng khá tốt đẹp mà không cần danh phận với người khác giới";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Nô Bộc")){
                        $luanPhuThe .= "\r\n vợ chồng thường hay cãi vã";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái rất quý trọng tiền bạc, thích hưởng thụ cuộc sống, có kỹ năng giao tiếp tốt, có nhiều cơ hội để khởi nghiệp, dễ dàng kiếm tiền, và có vận may tài chính tốt." ;
                    $luanTaiBach .= "\r\n Bạn rất có duyên với mọi người xung quanh, nên hợp tác làm ăn để kiếm tiền, và tài sản của bạn thường vượt trội hơn so với anh chị em. ";
                    $luanCungMenh .= "\r\n Bạn có xu hướng lấy \"cái tôi\" làm trung tâm. Bạn thích hợp làm thầy giáo hoặc làm việc trong ngành giải trí. Về già, con cái sẽ chăm sóc và phụng dưỡng bạn." .
                    $luanPhuThe .= "\r\n Nếu bạn có tình nhân ở bên ngoài sẽ phải tiêu xài nhiều tiền; có thể đây là đào hoa theo kiểu mua bằng tiền.";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Thiên Di")){
                        $luanQuanLoc .= "\r\n Bạn không nên làm ăn hợp tác với người khác giới";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Huynh Đệ")){
                        $luanQuanLoc .= "\r\n vợ chồng thương hay cãi vã, oán trách nhau";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái tính tình lạc quan, dễ phát phì, thê chất yếu nhưng đầu óc lại mạnh, rất có duyên với người chung quanh, hiếu thuận, dễ được bậc trưởng bối hay cấp trên yêu mến, sự nghiệp dễ có phát triển. " ;
                    $luanCungMenh .= "\r\n bạn trưởng thành khá sớm, sức khỏe tốt, cơ thể cường tráng, đào hoa bám vào người đối tượng phần nhiều là người đã kết hôn" ;
                    $luanTaiBach .= "\r\n Mở tiệm buôn bán thì thường có lộc";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Mệnh")){
                        $luanPhuThe .= "\r\n  trước khi kết hôn phần nhiều đã có quan hệ ở chung như vợ chồng, dễ có hai lần đò; cơ thể khỏe mạnh, xuất ngoại vất vả";
                        if($gt == "true"){
                            $luanPhuThe .= "\r\n dễ có 2 đời vợ";
                        }else{
                            $luanPhuThe .= "\r\n dễ có 2 đời chồng";
                        }
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái của bạn là người có tính lười biếng, hướng ngoại, thích đi đó đi đây, ra ngoài cát lợi, rất có duyên với người chung quanh, gặp nhiều quý nhân, thường có cơ hội đi du lịch, phần nhiều sẽ đi xa kiếm tiền." ;
                    $luanThienDi .= "\r\n Bạn cũng có duyên với người khác giới";
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, "Tử Tức", "Đào Hoa") && kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Quan Lộc")){
                        $luanPhuThe .= "\r\n Bạn là người thích hưởng thụ tinh thần, ưa phong hoa tuyết nguyệt, vợ chồng của bạn đối xử khá tốt vối bạn. Cẩn thận có đối tượng thứ 3 xem vào cuộc sống hôn nhân, dễ li hôn";
                    }
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, "Tử Tức", "Đào Hoa") && kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Điền Trạch")){
                        $luanPhuThe .= "\r\n Bạn là người thích hưởng thụ tinh thần, ưa phong hoa tuyết nguyệt, sống chung với người khác giới như vợ chồng nhưng không muốn sinh con";
                    }
                    $luanDienTrach .= "\r\n Bạn là người có mệnh cách tự lập, được hưởng ít hoặc không được hưởng tài sản của cha mẹ ông bà để lại";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái của bạn có tính hướng ngoại, rất dễ thân thiện với người khác, có nhiều bạn bè, đối đãi bạn bè rất tốt, nhưng cũng dễ bị người ta lừa gạt tiền, giật nợ; rất thích hợp làm công việc quan hệ công cộng, ngoại giao, cũng thích hợp buôn bán bách hóa, nghệ thuật biểu diễn." ;
                    $luanQuanLoc .= "\r\n bạn là người thích vui chơi giải trí, thích hường lạc, muốn kiếm tiền phải kết giao, thiết lập mối quan hệ trước, có thể làm việc trong ngành giải trí, bạn là người có duyên với người khác giới";
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, "Tử Tức", "Đào Hoa") && kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Quan Lộc")){
                        $luanPhuThe .= "\r\n Bạn là người thích hưởng thụ tinh thần, ưa phong hoa tuyết nguyệt, vợ chồng của bạn đối xử khá tốt vối bạn. Cẩn thận có đối tượng thứ 3 xem vào cuộc sống hôn nhân, dễ li hôn";
                    }
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData, "Tử Tức", "Đào Hoa") && kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Điền Trạch")){
                        $luanPhuThe .= "\r\n gặp dịp thì vui chơi đào hoa, do đó vợ chồng hay cãi vã; có hành vi luyến ái với đối tượng chưa kết hôn, tình nhân ở bên ngoài của mệnh tạo nặng quan hệ tính dục.";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái thông minh, xem trọng sự nghiệp, lúc còn đi học có thành tích khá tốt, lúc đi làm việc cũng có nhiều cơ hội, vừa ý, lương cao, thăng tiến nhanh, cũng có thể tay trắng làm nên, tự lập cơ nghiệp, nhưng cũng thường hay thay đổi việc làm" ;
                    $luanQuanLoc .= "\r\n Bạn phù hợp làm các công việc liên quan đến dịch vụ du lịch, ăn uống, cà phê....tuy nhiên sức khỏe kém, cần chú ý thói quen ăn uống không đúng cách" ;
                    $luanPhuThe .= "\r\n Bạn ở bên ngoài có thể có quan hệ ở chung như vợ chồng, hoặc là vợ chồng không có danh phận chính thức, nhưng đối xừ tốt với người phối ngẫu, bạn cũng hay chú ý sức khỏe con cái";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Tự Hóa Kỵ","Chính cung")){
                        $luanPhuThe .= "\r\n Vợ chồng bạn hay cãi vã nhưng không li hôn";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Thiên Di")){
                        $luanPhuThe .= "\r\n vợ chồng không hợp nhau, trước khi kết hôn đã ở chung như vợ chồng với người khác";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái biết lo toan cho gia đình, sinh hoạt gia đình vui vẻ, xem trong tiền bạc, sẽ dành dụm tiền vì gia đình, mua bất động sản" ;
                    $luanQuanLoc .= "\r\n bạn thích hợp công việc liên quan đến bất động sản, nên hợp tác hoặc đi xa làm ăn" .
                    "\r\n sau khi có con, gia vận sẽ tốt hơn; bạn trưởng thành sớm, có duyên với người khác giới,  coi trọng việc giao tiếp và các mối quan hệ xã hội hơn là coi trọng tiền bạc";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Phu Thê")){
                        $luanPhuThe .= "\r\n Có quan hệ ở chung như vợ chồng ở bên ngoài, hơn nữa phần nhiều đối tượng là những người đã có gia đình";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Con bạn là người thích hưởng thụ, ưa làm việc bằng đầu óc, không thích lao động chân tay, tuy nhiên việc kiếm tiền lại khá hanh thông" ;
                    $luanPhuThe .= "\r\n Bạn có duyên với người khác giới, được người khác giới để ý nên dễ có tình nhân bên ngoài, và cũng hay tiêu tốn tiền về những chuyện đào hoa bên ngoài";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Phụ Mẫu") && $gt == "false"){
                        $luanPhuThe .= "\r\n Có thể làm những công việc nhạy cảm để kiếm tiền hoặc sinh hoạt nam nữ không điều độ dẫn đến sức khỏe bị ảnh hưởng";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái hiếu thuận, quan hệ tốt với bậc trường bối, cấp trên, có thể được họ yếu quý, quan tâm, lúc còn đi học có thành tích tốt; là cách cục làm doanh nhân" ;
                    $luanPhuThe .= "\r\n bạn rất có duyên với người khác giới, ở bên ngoài giao du nhiều bạn bè khác giới" ;
                    $luanQuanLoc .= "\r\n Bạn Có thể mở tiệm, kinh doanh và hợp tác được nhưng phải cẩn thận đề phòng tiểu nhân.";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Điền Trạch")){
                        $luanPhuMau .= "\r\n  cha mẹ đa tài; bạn ở bên ngoài có nhiều đào hoa, có duyên với người khác giới";
                    }
                }
                // hóa quyền - tử tức
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái sinh ra dễ có hiện tượng khó sinh hoặc phải sinh mổ, có tính cách mạnh mẽ, năng động, khó dạy, dễ bị tổn thương từ bên ngoài, dễ dẫn đến phản ứng dị ứng, có tài năng, thích tranh cãi và thắng thế, thường không giữ được bình tĩnh, dễ bị kích động, nhưng thiếu sự quyết đoán." ;
                    $luanPhuThe .= "\r\n Khi bạn có quan hệ yêu đương với người khác giới, việc chấm dứt mối quan hệ đó thường có thị phi";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con bạn là 1 thông minh, có tài năng và mang trong mình những ước mơ lớn, có tiêu chuẩn nghiêm khắc với bản thân. Tuy nhiên nó cũng thích kiểm soát và cứng đầu, thường tỏ ra quyết đoán và đòi hỏi sự tôn trọng. Con bạn đặc biệt coi trọng sự nghiệp, có trách nhiệm cao và tiềm năng để đạt thành tựu. Tuy nhiên, cũng dễ gây xung đột và mâu thuẫn với người khác." ;
                    $luanPhuThe .= "\r\n  bạn có nhiều đào hoa, thường dẫn đến tình trạng rắc rối, khó xử về tình cảm";
                    if($gt == "false"){
                        $luanPhuThe .= "\r\n Cẩn thận đi chơi khuya, dễ bị trêu ghẹo hay quấy rối";
                    }
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Trong những đứa con, có đứa có thái độ kiêu căng, thường tỏ ra là người quan trọng hơn bạn bè và có kỹ năng giao tiếp hạn chế. Nó có mối quan hệ xã giao hẹp và hay can thiệp vào chuyện của anh chị em. Điều này có thể dẫn đến tranh cãi giữa các thành viên trong gia đình, tuy nhiên, mối quan hệ anh em vẫn giữ được sự gắn kết." ;
                    $luanQuanLoc .= "\r\n Công việc hợp tác làm ăn của bạn đòi hỏi phải cần mối quan hệ xã nhiều, kiếm tiền thuận lợi, lúc hợp tác thường sẽ là người được nắm quyền";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái thường khá ương bướng, khó bảo hay cãi lại, không dễ tiếp nhận ý kiến của người khác, hơi khó dạy nhưng hiếu thảo, cần lưu ý sức khỏe và cơ thể của chúng, dễ bị ngoại thương, cũng dễ bị bệnh đau eo lưng. " ;
                    $luanPhuThe .= "\r\n bạn có tình yêu theo kiểu sét đánh, mới gặp đã yêu, kết hôn chớp nhoáng; sau kết hôn, sẽ có người thứ ba xen vào hôn nhân của hai người, xảy ra nhiều thị phi rắc rối, có người quyến rũ hay dụ dỗ người phối ngẫu" ;
                    $luanQuanLoc .= "\r\n bạn có thể tự sáng lập cơ nghiệp, nếu đi làm hưởng lương sẽ thăng tiến dễ dàng; làm cổ đông, hoặc hợp tác vói người sẽ dễ xảy ra tranh chấp."; 
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái giao du với người khác sẽ có tác phong của người bề trên";
                    $luanTaiBach .= "\r\n bạn có năng lực kinh doanh tài chính tốt, thích vận dụng tiền bạc một cách linh hoạt, không thích gởi tiết kiệm để kiếm lời, ưa đầu tư sáng lập cơ nghiệp, cũng thích đầu cơ; nếu đi làm hường lương, cũng có thể nắm quyền về tài vụ; nên hợp tác với người khác, vừa ra vốn vừa ra sức lực; nếu bạn hợp tác làm ăn với người khác, thường sẽ nắm về kĩ thuật và ra vốn nhiều hơn, không ngừng mở rộng đầu tư.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con bạn khá bướng bỉnh, nghịch ngợm hiếu động, không chịu nghe lời khuyên của người khác, hay cãi lời bạn. Có sức khỏe tốt nhưng cũng dễ xảy ra sự cố té ngã bị thương";
                    $luanPhuThe .= "\r\n Bạn là người dậy thì sớm, có duyên với người khác giới cũng là người có sự ham muốn tình dục cao";
                    $luanQuanLoc .= "\r\n Bạn thích hợp mở tiệm hay buôn bán làm ăn, không nhất định phải cùng hợp tác với người khác";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Tự Hóa Kỵ","Chính cung")){
                        $luanTuTuc .= "\r\n Cơ thể con cái dễ bị tổn thương, khó dạy bảo con cái";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Tự Hóa Kỵ","Chính cung")&& $gt == "false"){
                        $luanCungMenh .= "\r\n Bạn nên hạn chế đi chơi khuya, cẩn thị bị trêu ghẹo, quấy rối. Sinh con có thể bị khó sinh hoặc phải sinh mổ";
                    }
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái ra ngoài ưa biểu hiện \"cái tôi\", thích cạnh tranh với người khác, cũng thích làm nhân vật lãnh đạo, được người ta kính trọng. Tuy nhiên cũng vì quá thể hiện cái tôi nên dễ chuốc lấy thị phi" .
                    "\r\n Bạn với con hay bất đồng quan điểm dẫn đến cãi cọ nhưng con cái vẫn hiếu thảo" ;
                    $luanQuanLoc .= "\r\n bạn bôn ba ở bên ngoài, gặp nhiều cạnh tranh, trải gió dầm sương, cũng thường phải dời chuyển chỗ ở; thích hợp với công việc ngoại vụ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái bạn sẽ phát triển tính cách mạnh mẽ, tích cực, thích cạnh tranh và lãnh đạo người khác. Chúng sẽ chọn bạn bè cẩn thận, ưa thích những người có năng lực và ở tầng lớp cao, có thể giúp nâng cao địa vị của mình. " .
                    "\r\n Đối với bạn bè, con bạn sống rất thành tâm, thích kiếm tiền, có tiền sẽ sáng lập cơ nghiệp mà không tính đến hậu quả, thích hợp với những nghề nghiệp ở nơi đông đúc, náo nhiệt, ví dụ cửa hàng bách hóa, khu resort, khu vui chơi giải trí, nghệ thuật biểu diễn... " ;
                    $luanQuanLoc .= "\r\n Công việc làm ăn của bản thân bạn, muốn thành công bạn thường phải giao tiếp và xây dựng mối quan hệ xã hội.";
                    $luanPhuThe .= "\r\n Bạn có nhu cầu tình dục mạnh mẽ và thường chủ động trong các mối quan hệ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái thích cạnh tranh, không chịu thua, phản ứng nhanh nhạy và có năng lực. Chúng học giỏi, làm việc tích cực, nỗ lực và đặt yêu cầu cao cho bản thân. Khi đi làm, chúng dễ thăng tiến và cũng có thể tự lập và sáng lập cơ nghiệp riêng." .
                    "\r\n Bạn nên chú ý sức khỏe của con cái";
                    $luanQuanLoc .= "\r\n Nhân viên của bạn phần nhiều đều là người có tài".
                    "\r\n Bạn cũng có thể hợp tác làm ăn, thuê người làm, sự nghiệp sẽ thành tựu.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái ở nhà thích phách lối và hiếu động; sau này sẽ có quyền thế và khả năng mua tậu bất động sản. Chúng cũng có thể kinh doanh trong lĩnh vực bất động sản." ;
                    $luanPhuThe .= "\r\n Bạn rất có duyên với người khác giới, đối sử khá tốt với vợ chồng nhưng bị áp lực" ;
                    $luanQuanLoc .= "\r\n Bạn ra ngoài nỗ lực làm việc sẽ được công nhận công sức, nếu hợp tác làm ăn sẽ gặp cạnh tranh nhưng có thành quả, có thể xuất ngoại tung hoành";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Con cái có tài quản lý tài chính, biết kiếm tiền và thích đầu tư. Chúng có kiến thức chuyên sâu, xem trọng hưởng thụ, khá phô trương và tiêu tiền rộng rãi. Sau khi kết hôn, chúng đòi hỏi nhiều về chuyện tình dục, dễ xung động, nên cẩn thận để tránh tạo áp lực cho người phối ngẫu. Con cái có khả năng sinh con trai ít, con gái nhiều." ;
                    $luanTaiBach .= "\r\n Bạn có thể hợp tác làm ăn với người khác để kiếm tiền";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Kỵ","Phụ Mẫu") && $gt == "false"){
                        $luanTaiBach .= "\r\n Có thể làm những công việc nhạy cảm để kiếm tiền hoặc sinh hoạt nam nữ không điều độ dẫn đến sức khỏe bị ảnh hưởng";
                    }
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái hiếu động, thường ra ngoài, có thể chất kém và dễ bị thương. Chúng thích cãi lý và thường có ý kiến không hợp với cha mẹ và các bậc trưởng bối, nhưng vẫn hiếu thảo và được người lớn công nhận. Chúng rất nỗ lực trong học hành và thi cử, đạt thành tích tốt." ;
                    $luanQuanLoc .= "\r\n Nếu hợp tác làm ăn với người khác, họ sẽ khá vất vả và chịu nhiều áp lực, nhưng vẫn có thể phát triển và đạt được thành công.";
                    $luanPhuThe .= "\r\n Bạn dễ bị xung động về mặt tình dục.";
                }
                // Hóa Khoa - phu thê
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái thanh tú, thông minh, rất có duyên với mọi người xung quanh. Chúng có phong độ, hiền hòa, lễ độ, có khí chất và có tài năng trong lĩnh vực văn nghệ." ;
                    $luanThienDi .= "\r\n bạn rất có duyên với người khác giới, lúc giao du với bạn bè khác giới, rất ôn hòa, vui vẻ, chú trọng lễ nghĩa" ;
                    $luanCungMenh .= "\r\n Bạn tính hơi chuộng hư vinh, thích được người khác khen ngợi; nếu có hợp tác làm ăn cũng không lớn lắm.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Các con trong nhà đối sử vui vẻ với nhau, lý trí và hòa hợp, ít khi xảy ra tranh chấp. Chúng có thái độ tốt và dễ dạy bảo." ;
                    $luanThienDi .= "\r\n Bạn rất có duyên với người khác giới, khi giao du với bạn bè khác giới rất chú trọng tình cảm và thường là do được giới thiệu. Bạn là người phong nhã, có phong độ. Khi hợp tác làm ăn, bạn có thể kiếm tiền đều đặn, dù không nhiều.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Tử Tức"){
                    $luanTaiBach .= "\r\n Trong công việc kinh doanh, bạn thường dựa vào thương hiệu hoặc nhờ người khác giới thiệu và giúp đỡ. Bạn có thể kiếm được tiền mà không cần phải tham gia vào các mối quan hệ giao tế mạnh mẽ và cạnh tranh gay gắt." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu biết chăm lo sức khỏe cho bạn. ";
                    $luanHuynhDe .= "\r\nAnh em trong nhà khá phong lưu và có duyên với người khác giới" ;
                    $luanTuTuc .= "\r\n Con cái thì cũng đối sử vui vẻ, hài hòa, lễ phép với anh em bạn bè của bạn, biết dùng tiền có kế hoạch không lãng phí";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái với nửa kia của bạn có tình cảm khá tốt, không có khoảng cách thế hệ, công việc hay sự nghiệp của chúng bình ổn, hôn nhân thường là do người khác giới thiệu mà thành" ;
                    $luanPhuThe .= "\r\n  Người phối ngẫu cùa bạn rất có duyên với người khác. Sau kết hôn vẫn giao du với bạn bè khác giới." ;
                    $luanQuanLoc .= "\r\n bạn sau khi có con, công việc hay sự nghiệp đều thuận lợi hơn; cần chú ý sức khỏe của con cái";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái có kế hoạch cân đối thu chi khi sử dụng tiền bạc, tính toán rõ ràng. chúng có thu nhập ổn định và thường thuộc nhóm người đi làm và hưởng lương. Chúng có thái độ hiền hòa với mọi người xung quanh." ;
                    $luanThienDi .= "\r\n bạn rất có duyên với người khác giới, sẽ giao du với nhiều bạn bè khác giới, nhưng thiên về phương diện tinh thần, đào hoa chi ở cái miệng" ;
                    $luanQuanLoc .= "\r\n Bạn nên hợp tác làm ăn với người khác, mở tiệm làm ăn sẽ gặt hái được thành công";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Con cái có thể chất tốt, ít khi mắc bệnh nặng, có bệnh cũng dễ chữa" ;
                    $luanThienDi .= "\r\n Khi bạn ra ngoài hay gặp được quý nhân giúp đỡ nếu gặp chuyện khó khăn. khi giao du với người khác thì khá chú trọng cảm giác, thích lãng mạn; nặng duyên với người khác giới" ;
                    $luanQuanLoc .= "\r\n Bạn nên mờ tiệm, làm ăn buôn bán, không nhất định phải hợp tác, sẽ không đầu tư lớn, tuy không kiếm được nhiều tiền, nhưng rất bình ổn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái ra ngoài xã hội cát lợi, gặp nhiều quý nhân, có thanh danh." ;
                    $luanThienDi .= "\r\n Bạn ra ngoài cũng gặp được quý nhân khi gặp khó khăn, người dưới của bạn phần lớn đều giúp đỡ bạn, hợp tác làm ăn ở bên ngoài sẽ có tiếng tăm, có duyên với người khác giới";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái giao du hiền hòa với bạn bè, bạn bè không nhiều, nhưng có giúp đỡ, phần nhiều đều là người lễ độ" ;
                    $luanPhuThe .= "\r\n bạn sống với người phối ngẫu chú trọng bầu không khí tao nhã đầm ấm; hai người đều có duyên với người khác giới"; 
                    $luanQuanLoc .= "\r\n Bạn có thể họp tác, nhờ bạn bè giúp đỡ mà kiếm được tiền, mà không cần tốn tiền tạo các mối quan hệ xã hội.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái của bạn ưa yên tĩnh, văn nhã, hiếu học và có tài năng. Thành tích học tập của chúng rất tốt và khi lớn lên thường có tiếng tăm. Chúng chuyên tâm vào công việc và có thái độ hòa hợp với bạn bè, đồng nghiệp. Dễ dàng được trọng dụng và có thể làm việc trong lĩnh vực văn hóa, giáo dục hoặc các nghề nghiệp có tính phục vụ." ;
                    $luanQuanLoc .= "\r\n Bạn có thể hợp tác kinh doanh một cách thuận lợi nhưng thường không phát triển lớn. Sau khi kết hôn, bạn vẫn rất có duyên với người khác giới và có xu hướng làm việc ngoài";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Gia đình bạn là 1 gia đình gia giáo, con cái hiền hòa lễ độ biết lo cho gia đình" .
                    "\r\n Bạn ở bên ngoài luôn nghĩ về gia đình, đối xừ tốt với người phối ngẫu" .
                    "\r\n Bạn rất có duyên với người khác giới, hợp tác làm ăn kinh doanh bình ổn, nếu gặp trắc trở có thể ứng phó hợp cách, hoặc được gia đinh giúp sức chống đỡ, giải trừ nguy cơ";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái có tâm tình cởi mở và tư duy ổn định, được rèn luyện trong lĩnh vực văn nghệ và có sở thích thanh nhã. Chúng giỏi quản lý tài chính và lập kế hoạch, làm ăn ít khi thua lỗ lớn và có khả năng tích lũy tài sản hiệu quả." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu giỏi quản lý gia đình. bạn có thể hợp tác để tạo dựng sự nghiệp với kế hoạch chu đáo và vận kinh doanh thuận lợi. Sau khi kết hôn, bạn có thể sẽ có bạn tri kỷ, bạn tinh thần.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Gia đình vui vẻ, con cái hiếu thuận, dễ được bậc trưởng bối hoặc cấp trên đề bạt, nâng đỡ. Vận thi cử không tệ, học hành và đi làm đều có thành tích tốt, nên làm việc trong các cơ quan lớn, cơ quan công, hoặc trong lĩnh vực văn hóa giáo dục." ;
                    $luanTaiBach .= "\r\n bạn có thu nhập khá ổn định";
                }

                // Hóa Kỵ - tử tức
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Gia đình ít con cái, việc nuôi dưỡng con cái gặp nhiều khó khăn, sinh đẻ không thuận lợi và dễ bị sảy thai hoặc phải sinh mổ. Tâm trạng của con cái không ổn định và thiếu tự tin. Chúng thẳng thắn nên lời nói dễ làm người khác bị tổn thương, nhưng lại có tính thương người và quan tâm lo lắng cho người khác." ;
                    $luanPhuThe .= "\r\n Bạn hơi khó có người yêu, khó kết hôn, nếu giao du với người khác giới thường sẽ bị phá tài; cuộc sống gia đình không được yên ổn; sau kết hôn nếu có tình nhân ờ bên ngoài, thường không lâu, không giữ được, mà còn bị phá tài, gặp phiền phức, rắc rối.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Gia đình có con cháu nhưng không nhiều, dễ có sự ngăn cách giữa hai thế hệ. Tuy nhiên, con cái hiếu thuận và có tính tiết kiệm, nhưng lại có tính ỷ lại khá nhiều và thiếu tự tin." ;
                    $luanQuanLoc .= "\r\n Bạn thường phải bôn ba vất vả bên ngoài, nhưng không mấy thuận lợi và dễ gặp tai ương" .
                    "\r\n Khi hợp tác làm ăn, vận kinh doanh thường không tốt, dễ dùng tiền lãng phí " ;
                    $luanPhuThe .= "\r\n Cuộc đời tuy trải qua nhiều mối tình nhưng thuộc dạng bị động dễ gặp bất hòa, tranh chấp và rắc rối trong các mối quan hệ tình cảm";
                    if($gt == "false"){
                        $luanPhuThe .= "\r\n Bạn dễ bị dụ dỗ; có hiện tượng kết hôn muộn, khó kết hôn, ờ chung như vợ chồng mà không có danh phận.";
                    }else{
                        $luanPhuThe .= "\r\n Duyên đến rồi duyên đi, dễ có 2 lần hôn nhân nếu không thì ở rể";
                    }
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái thường có thái độ không tốt với anh em bạn bè, không có bạn tri kỷ và dễ xảy ra tranh chấp với người khác. Khi liên quan đến tiền bạc, chúng càng dễ gặp chuyện không hay." ;
                    $luanQuanLoc .= "\r\n Bạn trước 40 tuổi không nên hợp tác với người khác, tài chính dễ bị tổn thất" ;
                    $luanPhuThe .= "\r\n Giữa vợ chồng chuyện sinh hoạt không được đồng điệu, bạn có tính chuộng hư vinh, phong lưu tao nhã, thường ở bên ngoài ăn uống vui chơi hưởng thụ";
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Tử Tức","Đào Hoa")){
                        $luanPhuThe .= "\r\n Cẩn thận hao tán tiền của về chuyện nam nữ bên ngoài, đối tượng thường là người trẻ tuổi, chưa kết hôn, dễ hư thai.";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Vợ chồng bạn vì nguyên nhân sinh lí hoặc tâm lí mà không sinh con, hoặc là có sinh nhưng khó dưỡng" .
                    "\r\n Con cái có tính ỷ lại, khi nhỏ thường bám dính cha mẹ. Sự nghiệp của chúng phát triển không thuận lợi và thành tích học tập không cao. Tình cảm cũng gặp nhiều trắc trở, nên kết hôn muộn." ;
                    $luanPhuThe .= "\r\n bạn sau khi kết hôn dễ gặp rắc rối về tình cảm, có thể trải qua hai lần hôn nhân. Bạn dễ có tình nhân bên ngoài nhưng mối quan hệ này thường không có kết quả. Đối tượng tình cảm thường đã kết hôn hoặc thuộc tầng lớp không cao. Bạn khó kết hôn chính thức, thường có mối quan hệ sống chung như vợ chồng mà không có danh phận chính thức. Việc hợp tác làm ăn cũng không thuận lợi";
                    if($gt == "true"){
                        $luanPhuThe .= "\r\n Nếu bạn có nhân tình bên ngoài thì người phụ nữ đó đã có chồng hoặc là đồng nghiệp. Con cái khó nuôi";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái ít bạn bè, không giỏi giao tiếp với người khác. Tính thẳng thắn, nói không lựa lời nên dễ gây tranh cãi và ít có bạn bè thân thiết. Tình cảm không thuận lợi, thường theo đuổi tiền bạc, phải bôn ba vì tiền và ít được hưởng phước, tâm trạng không yên ổn." .
                    "\r\n Con cái sẽ phụng dưỡng bạn";
                    $luanQuanLoc .= "\r\n bạn cũng không nên hợp tác kinh doanh với người khác, vì thường sẽ gặp nhiều khó khăn. Đầu tư thường mất vốn và gặp những rắc rối không may, có thể bị ảnh hưởng bởi tai ách và tiểu nhân, gây tổn thất tài chính do bạn bè";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Con cái có thể chất yếu, dễ bị tâm trạng thay đổi nhanh, số phận khá vất vả. Chúng dễ có ý kiến không hợp với cấp trên, khó được đề bạt và nâng đỡ, vận may trong thi cử không cao. Đôi khi con cái có thái độ bất hiếu, có thể dịch chuyển, đi xa quê hương, không nên hợp tác với người khác vì có thể gặp tổn thất và rủi ro.";
                    $luanPhuThe .= "\r\n Vợ chồng có ham muốn cao trong hôn nhân nhưng sinh hoạt lại không được đồng điệu";
                    $luanTuTuc .= "\r\n Bạn có ít con trai, con sinh ra khó nuôi" ;
                    $luanQuanLoc .= "\r\n Khi bạn hợp tác làm ăn chung với người khác, sẽ có những công việc làm cho bạn khá vất vả, dễ gặp phiền phức, rắc rối, do đó mà gây ra tổn thất, xa sứ lập nghiệp sẽ ổn hơn";
                    if(kiemTraTuHoaPhai($laSoData,"Tật Ách","Hóa Kỵ","Tử Tức")){
                        $luanPhuThe .= "\r\n Có cuộc sống vất vả thiếu thốn, đối tượng kết hôn là người đã lớn tuổi hoặc là người đã kết hôn";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Tật Ách","Hóa Kỵ","Tài Bạch")){
                        $luanTaiBach .= "\r\n Bạn có thể kiếm được tiền liên quan đến ngành giải trí hoặc dùng tiền mua vui bên ngoài";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Tật Ách","Hóa Kỵ","Nô Bộc")){
                        $luanPhuThe .= "\r\n Sau kết hôn cẩn thận vướng đào hoa với người chưa kết hôn";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái nên rời gia đình sớm, ra ở riêng. Bạn cũng có ít con trai nhưng con thường khó nuôi và khi trưởng thành sẽ đi công tác xa, ít gặp bạn";
                    $luanPhuThe .= "\r\n Bạn là người khá chung thủy, không có suy nghĩ lăng nhăng ở bên ngoài. ";
                    $luanTaiBach .= "\r\n Bạn không nên hùn vốn chung trong làm ăn với bạn bè, hay chơi hụi, cũng không nên là người đứng ra đảm bảo vì sẽ rất dễ bị tổn thất lớn" ;
                    $luanDienTrach .= "\r\n Hồi trẻ thường phải bôn ba khắp nơi, phải thuê nhà ở, ít khi định cư 1 chỗ";
                    if(kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Lộc","Nô Bộc")||kiemTraTuHoaPhai($laSoData,"Tử Tức","Hóa Lộc","Phụ Mẫu")){
                        $luanTuTuc .= "\r\n Bạn không có duyên với đứa con trai của mình";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Phu Thê","Tự Hóa Kỵ","Chính cung")){
                        $luanTuTuc .= "\r\n Bạn sẽ có con gái nhiều hơn con trai";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Mệnh","Tự Hóa Kỵ","Chính cung")||kiemTraTuHoaPhai($laSoData,"Thiên Di","Tự Hóa Kỵ","Chính cung")){
                        $luanTuTuc .= "\r\n Bạn có con gái nhiều hơn, dễ đi được nước ngoài, đi lại nên đề phòng sự cố giao thông";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Con cái bạn có tính tự lập, có thể tự kiếm tiền tiêu sài, giữa bạn và các con thường bất đồng quan điểm và không hợp nhau" .
                    "\r\n Các con thường có ít bạn, có cũng không lâu dài, sống tình nghĩa với bạn bè, tuy nhiên bạn bè lại không có sự giúp đỡ khi con bạn gặp khó khăn, nếu qua lại tiền bạc dễ bị tổn thất" .
                    "\r\n Các con cũng không thích các anh em bạn bè của bạn, cũng không hòa hợp với các thành viên trong gia đình" ;
                    $luanNoBoc .= "\r\n Bạn cũng thường tụ tập anh em bạn bè ăn uống vui chơi, bạn cũng dễ đi ra nước ngoài. không nên hợp tác làm ăn khi còn trẻ, nếu muốn hợp tác làm ăn với người khác thì phải sau 40 tuổi sẽ tránh được các rủi do tổn thất về tài chính";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái khá ham chơi lười học nên thành tích không được cao, thường thường không nắm được mấu chốt của sự việc, thích đi làm hưởng lương" ;
                    $luanQuanLoc .= "\r\n Bạn không nên hợp tác làm ăn với người khác vì phần nhiều công việc phía bạn sẽ nhiều mà độ rủi do lại cao" ;
                    $luanTatAch .= "\r\n Bạn có nguy cơ mắc các bệnh phụ khoa, trong hôn nhân chuyện chăn gối vợ chồng khó viên mãn hòa hợp, dễ vướng bận đào hoa";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái tiêu xài nhiều tiền của bạn, có quan tâm gia đình, không thích ra ngoài, thường sảy ra mâu thuẫn với người nhà" .
                    "\r\n Bạn không có nhiều con, có thể nhận con nuôi" ;
                    $luanTaiBach .= "\r\n Bất kể bạn có thích hay không thì bạn cũng thường phải bôn ba bên ngoài, tiêu xài nhiều tiền ở bên ngoài, dễ bị trộm cắp";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Tử Tức"){
                    $luanTuTuc .= "\r\n Con cái có đứa có bụng dạ hẹp hòi, khó cân bằng cảm xúc, hay đâm đầu vào những chuyện khó giải quyết, bình thường lười biếng, tính tình không cởi mở nhưng cũng có thể vất vả vì người khác mà không oán than" .
                    "\r\n Con bạn có tính ưa hưởng thụ, cũng không biết giữ tiền, tài sản của bạn sẽ để lại cho con cái" ;
                    $luanQuanLoc .= "Bạn không nên hợp tác làm ăn với người khác vì khó nảy sinh lợi nhuận, bản thân bạn bè của bạn cũng sẽ bị bất lợi";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tử Tức"){
                    $luanTuTuc .=  "\r\n Con cái thiện lương, không làm chuyện xấu, trực tính, hiếu thuận với cha mẹ, tuy nhiên sức khỏe của con cái không được tốt dễ bị ốm đau, vận khí không được tốt lắm, ít được các bậc trưởng bối nâng đỡ";
                    if($gt == "false"){
                        $luanTuTuc .= "\r\n Khi sinh con bạn dễ bị khó sinh, hoặc sau khi sinh cơ thể con không được khỏe mạnh, ít con cái";
                        if((kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Phụ Mẫu","Văn Xương")&&kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Phụ Mẫu","Hóa Kỵ"))||(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Phụ Mẫu","Liêm Trinh")&&kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Phụ Mẫu","Hóa Kỵ"))){
                            $luanTuTuc .= "\r\n Dễ phải sinh mổ. có bệnh kín về bệnh tình dục";
                        }
                    }else{
                        if(kiemTraTuHoaPhai($laSoData,"Phụ Mẫu","Hóa Kỵ","Tử Tức")){
                            $luanTatAch .= "\r\n Bạn nên chú ý các bệnh nam khoa( như tinh trùng loãng...), sự cố về con cái hoặc xuất ngoại dễ gặp tai ách";
                        }
                    }
                } 

                // hóa lộc - tài bạch
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn là người không quá xem trọng tiền bạc, biết tiêu sài tiền, chú trọng hưởng thụ, có thể vì tình người mà tiêu tốn số tiền lớn" .
                    "\r\n Về mặt tiền bạc, bạn là người không giữ được tiền, bạn chi tiêu tuy không tiết giảm nhưng bạn lại là người biết kiếm tiền, tiền đi lại có tiền đến, tự kiếm được tiền tiêu xài" ;
                    $luanThienDi .= "\r\n Bạn là người đa tình, bạn bè khá nhiều, bạn bè khác giới cũng nhiều, giao tiếp xã hội khá nhiệt tình, vui vẻ";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn là người có thể tự kiếm được tiền, biết kiếm tiền và kiếm tiền khá tốt, thường sẽ đầu tư khá nhiều vốn vào công việc làm ăn";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn sẽ dùng phần lớn tiền kiếm được để lo cho cuộc sống gia đình, có tiền cũng sẽ trợ giúp các anh chị em trong nhà, khá rộng rãi với bản thân, đối với bạn bè tuy trọng tình nghĩa nhưng về mặt tiền bạc lại hơi keo kiệt";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Tài Bạch"){
                    $luanPhuThe .= "\r\n Bạn và người phối ngẫu tình cảm rất tốt, thường thì bạn sẽ là người yêu thương nhiều hơn, biết quan tâm chăm sóc người phối ngẫu, cho người phối ngẫu tiền để tiêu xài." .
                    "\r\n Người phối ngẫu cũng có thể trợ giúp bạn kiếm tiền, từ đó công việc sẽ thuận lợi hơn"; 
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn sẽ ra ngoài kiếm tiền, nhờ hợp tác làm ăn với người khác mà kiếm được tiền" ;
                    $luanPhuThe .= "\r\n Bạn rất có duyên với người khác giới, cẩn thận dễ sảy ra chuyện ngoại tình"; 
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn là người thích kiếm tiền, nếu dùng trí tuệ để kiếm tiền thì sẽ rất dễ dàng" .
                    "\r\n Bạn có khuynh hướng đầu tư vào cửa tiệm, công xưởng hoặc mẫu dịch, có thể làm công chức nhà nước hưởng lương. Thường chi tiêu số tiền lớn cho bản thân cho sức khỏe ăn mặc";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Muốn kiếm được tiềng thì phải đi xa, nên đi nơi khác để tìm hướng phát triển, bạn có số tự lập, phải tự dựa vào bản thân để kiếm tiền" ;
                    $luanThienDi .= "\r\n Ở bên ngoài rất có duyên với người khác giới, bụng dạ rộng dãi, cho tiền người phối ngẫu tiêu sài";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn sẽ đầu tư, hợp tác hùn vốn với bạn bè làm ăn, có thể dựa vào bạn bè để kiếm tiền, tốn nhiều tiền cho việc tạo dựng mối quan hệ xã hội, tuy nhiên cũng dễ bị người khác lừa tiền";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn là người có đầu óc, thích đầu tư và đâu tư có sinh lời, tiền kiếm được sẽ mang ra tái đầu tư để kiếm thêm tiền";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn là người biết quan tâm đến gia đình, biết cách sử lý số tiền kiếm được 1 cách hợp lý, biết dành dụm, có ý định sẽ dùng tiền kiếm được để mua bất động sản" +
                    "\r\n Bạn cũng có thể đầu tư bất động sản hoặc làm các nghề liên quan đến bất động sản thì rất có lộc";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Bạn là người thích hưởng thụ, sẽ tiêu 1 số tiền lớn để bồi dưỡng kiến thức hoắc học nghề" .
                    "\r\n Bạn cũng sẽ chi tiền cho người phối ngẫu để làm ăn" ;
                    $luanThienDi .= "\r\n Ra ngoài làm ăn thuận lợi, có bạn bè tương trợ";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn là người được bạn bè giúp đỡ trong công việc hoặc có thể dựa vào sự giúp đỡ của cha mẹ, trưởng bối để kiếm tiền. có thể đầu cơ để kiếm tiền" .
                    "\r\n Tuy nhiên kiếm được tiền cũng dễ bị người khác lừa gạt, vì vây cũng thường phải tiêu xài ít hơn nhưng không cam tâm nên hay oán trời trách người, có tâm lý giúp người thì người giúp lại, hiếu thảo với cha mẹ, có tiền sẽ cho cha mẹ";
                }
                // hóa quyền - tài bạch
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn là người Có dục vọng tiền bạc rất mạnh, thích nắm quyền tài chính, năng lực kiếm tiền không tệ, nhưng rất có tham vọng, hơi bị tình trạng \"mắt nhìn cao mà tay với không tới\", tóm lại phải trải qua sóng gió trắc trờ, nỗ lực phấn đấu, cạnh tranh với người khác mới có thành quả" .
                    "\r\n Bạn là người không biết giữ tiền, có cơ hội kiếm được số tiền lớn nhưng cũng dễ vì hưởng thụ, phô trương mà tiêu xài nhiều tiền" .
                    "\r\n Bạn cũng có tính ưa tranh hơn thua với người khác, với người phối ngẫu bạn cũng hơi phô trương, hơi thiếu tự tin";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Đối với tiền bạc, bạn có dục vọng vô cùng tận, không bao giờ cảm thấy thỏa mãn; tiền là lá gan của anh hùng, có tiền mới cảm thấy tự tin; đầu tư sáng lập cơ nghiệp là dựa vào bản thân, nhờ tài năng và nghề chuyên môn của mình để mưu cầu lợi ích; nếu hợp tác làm ăn cũng có thể chỉ lấy nghề chuyên môn ra hùn hạp.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Bạn là người biết kiếm tiền tuy nhiên khá lao tâm lao lực, phải cho ra trước, phấn đấu và cạnh tranh, mới kiếm được tiền" .
                    "\r\n Tiền bạn kiếm được thường dùng để lo toan cho gia đình, còn có thể giúp vốn cho anh chị em, thái độ đối xử với bạn bè hơi cao ngạo, còn xem thường cấp trên hay ông chủ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Thường không thòa mãn mức thu nhập hiện có, sẽ tìm cách khai mở \"tài lộ\", cho nên sẽ có nhiều sự nghiệp, có nhiều công việc. Có thể giao tiền cho người phối ngẫu quản lí";
                    $luanPhuThe .= "\r\n giữa bạn với người phối ngẫu thường có ý kiến nghịch nhau, nhưng vẫn có thể giao lưu cảm thông cho nhau; bạn sẽ quản thúc phối ngẫu, nhưng cũng sẽ quan tâm chăm lo và nghe theo kiến nghị của người phối ngẫu.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Có thể hợp tác làm ăn với người khác, cũng có thể đầu tư bất động sản; sau khi kiếm được tiền sẽ tái đầu tư, sẽ mua tậu nhà cửa, mua nhiều bất động sản. Tiêu xài nhiều tiền trong chuyện mở rộng mối quan hệ, hưởng thụ; cho con cái (đã lớn) tiền tiêu xài, có thể dùng danh nghĩa con cái để giữ tiền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Rất thích kiếm tiền, vì kiếm tiền mà luôn bận rộn, không từ vất vả cũng sẽ tiêu xài tiền cho việc hưởng thụ, rất rộng rãi với bản thân, sẽ phô trương nguồn lực tài chính của mình, toàn thân dùng toàn đồ cao cấp; sẽ đến nhưng nơi vui chơi giải trí, tửu sắc, để kiếm tiền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Ở bên ngoài được người ta hỗ trợ, khẳng định; có thể dựa vào tài năng, nghề chuyên môn để giành cơ hội kiếm tiền; có thể tự lập cơ nghiệp, có biểu hiện rất ưu tú, có nhiều cơ hội, ra ngoài kiếm được nhiều tiền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Muốn kiếm tiền phải cạnh tranh với bạn bè; có thể hợp tác với bạn bè, nhưng bạn bè nắm quyền về tài vụ, có thể phát triển; cẩn thận chọn bạn bè, dựa vào bạn bè mà kiếm tiền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Thích đầu tư, sau khi kiếm được tiền sẽ tái tăng vốn, sự nghiệp lớn, thành quả tốt đẹp, có thể phát triển nhiều hướng hoặc phát triển theo kiểu dây chuyền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Rất ham muốn đầu tư, có thể đầu tư bất động sản, kiếm được tiền sẽ tái đầu tư, vận dụng tiền bạc hợp lí, biết tích lũy, sẽ mua tậu nhà cửa, có nhiều bất động sản; nhưng cũng dễ bị người ta giật nợ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Thích hưởng thụ, ưa phô trương; vì tranh cường hiếu thắng mà tiêu xài một số tiền lớn; cũng sẽ vất vả kiếm tiền, đầu tư vào sự nghiệp của người phối ngẫu.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Biết kiếm tiền, có thể kinh doanh hoặc làm việc trong lãnh vực văn hóa giáo dục, sẽ có phát triển; lúc cấp bách cần dùng tiền, sẽ được bạn bè hoặc trưởng bối giúp đỡ.";
                }
                // Hóa Khoa - tài bạch
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Có kế hoạch rõ ràng về tiền bạc, không tiêu xài loạn xạ thiếu tiết chế, không cố truy cầu thứ gì, mà luôn có thái độ xử sự đầy lí tính" .
                    "\r\n người khác có cảm giác bạn là người rất có tiền.";
                    $luanPhuThe .= "\r\n Bạn đối xử với mọi người hiền hòa, sáng suốt, có lí lẽ; sống với người phối ngẫu cũng lấy lễ đãi nhau, quan hệ vui vẻ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Tiền bạc bình ổn, thuận lợi, không có sóng gió, có kế hoạch rõ ràng, ít nhưng đều đặn, không lo thiếu; nếu cần dùng tiền cấp bách, luôn luôn có người trợ giúp để vượt qua khó khăn. " .
                    "\r\n Không mấy chú ý tiền bạc lại có cái hay, có thể dùng tài năng và danh tiếng để kiếm tiền; nên đi làm hưởng lương, nếu hợp tác với người khác sẽ xuất vốn ít hơn. ";
                    $luanPhuThe .= "\r\nĐối xử hiền hòa với mọi người, vợ chồng hòa hợp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Tài vận binh ổn, có kế hoạch hợp lí đối với số tiền kiếm được, có thể lo cho sinh kế gia đình, cũng có thể trợ giúp anh chị em, anh chị em cũng có thể giúp đỡ cho bạn; đối đãi hòa mục với bạn bè.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Thích hợp đi làm hưởng lương; tiền bạc bình thuận; sự nghiệp hoặc công việc đều thuận lợi, làm ăn tính toán tỉ mỉ, khó xuất hiện nguy cơ. ";
                    $luanPhuThe .= "\r\nNgười phối ngẫu là quý nhân của bạn, có thể xoay sở tiền bạc cho bạn; vợ chồng đôi khi có cãi vã bất đồng quan điểm nhưng nhìn chung là hạnh phúc.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Sinh hoạt gia đình bình ổn, hưởng thụ nhưng không lãng mạn. Hợp tác với người khác bình thuận, ít có sóng gió, tiền không nhiều nhưng đều đặn;";
                    $luanQuanLoc .= "\r\n quan hệ xã hội khá tốt, có thể làm các nghề liên quan đến ngành giải trí, resort, hoặc có tính phục vụ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Thích hợp đi làm, cũng có thể mở tiệm nhỏ buôn bán, làm mậu dịch; có thể được trường bối giúp đỡ, kiếm tiền nhẹ nhàng, bình ổn thuận lợi, nhưng không nhiều, chỉ vừa đủ mà thôi.";
                    $luanThienDi .= "\r\n Đối xử với bạn bè bình hòa, vui vẻ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Tài Bạch"){
                    $luanThienDi .= "\r\n Ra ngoài kiếm tiền sẽ thuận lợi, có thể được quý nhân giúp đỡ. Xã giao bạn bè, hiền hòa lịch sự; vợ chồng sống với nhau hạnh phúc, bầu không khí lãng mạn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Tài Bạch"){
                    $luanNoBoc .= "\r\n Về phương diện tiền bạc, bạn và bạn bè có thể giúp đỡ lẫn nhau, xoay chuyển thuận lợi, không có sóng gió; có thể hợp tác làm ăn với bạn bè, tuy không kiếm được nhiều tiền, nhưng cũng ít gặp nguy cơ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Tài Bạch"){
                    $luanQuanLoc .= "\r\n Về công việc hay sự nghiệp, thích hợp đi làm hưởng lương, có biểu hiện bình ổn, dễ được đề bạt, nâng đỡ thăng tiến. Đối với việc đầu tư trong sự nghiệp, sẽ đi từng bước vững chắc, có kế hoạch tỉ mỉ, công việc vận hành bình ổn. Vợ chồng tôn trọng nhau.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Tài vận bình ổn, có kế hoạch hợp lí, cân đối thu chi, không lãng phí, sẽ dành dụm một phần nhỏ để phòng khi cần thiết. Có thể hợp tác làm ăn với người khác, có thể làm các nghề nghiệp liên quan đến bất động sản. Lúc mua tậu nhà cửa, sẽ chia ra nhiều kì để thanh toán; lúc thiếu tiền, có thể thế chấp nhà.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Biết tính toán tỉ mỉ, làm ăn hiếm khi bị lỗ nhiều, có thể thu lợi bình ổn. Biết hường thụ, nhưng có tiết chế, cân đối thu chi, sẽ tiêu tiên bồi dưỡng kiến thức cho thị hiếu lành mạnh. Có thể điều chuyển vốn liếng cho sự nghiệp của người phối ngẫu, đời sống hôn nhân ít sóng gió.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Thu nhập bình ổn, ổn định, thích hợp đi làm hưởng lương, có thể làm việc trong cơ cấu lớn, làm công chức hoặc trong lãnh vực văn hóa giáo dục.";
                    $luanPhuMau .= "\r\n Hiếu thào với cha mẹ, dễ được trưởng bối, cấp trên xem trọng; cũng quan tâm chăm lo cho gia đình của người phối ngẫu.";
                }

                // Hóa Kỵ - tài bạch
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Kiếm tiền không dễ, thường vì tiền mà phải bôn ba; tuy xem trọng tiền bạc, nhưng rất biết tiêu xài, thường lãng phí tiền bạc trong chuyện hưởng lạc" .
                        "\r\n bạn có quan niệm tiền bạc khác người, tự kiếm tiền tự tiêu xài, kiếm được nhiều thì xài nhiều, kiếm được ít thì xài ít, không để dành tiền; còn có hiện tượng bị người ta giật tiền hoặc lừa tiền. Cho nên tốt nhất là đừng tự mình quản lí tài chính, hoặc phải học hỏi thêm về quản lí tài chính." ;
                    $luanPhuThe .= "\r\n Giao du với người khác, thường hay nghi ngờ lung tung. Giữa vợ chồng cũng thường nghi kị, dễ vì tiền bạc mà xảy ra tranh chấp, làm cho cuộc sống hôn nhân không được vui vẻ.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Là người biết tiết kiệm tiền, khéo giữ của, hơi bảo thủ và không giỏi quản lí tài chính, nên nhiều khi vì tiền mà vất vả khổ sở" .
                    "\r\n Có nhiều cơ hội để kiếm tiền, thông thường đầu tư lần đầu sẽ thất bại, sau đó làm ăn từng bước, dần dần hanh thông" .
                    "\r\n Sẽ chiếm lợi ích của người khác, không chịu thua thiệt, tài vận không phải lúc nào cũng thuận buồm xuôi gió, dễ vì tiền bạc mà xảy ra sóng gió.";
                    $luanPhuThe .= "\r\n Cuộc sống giữa vợ chồng không được hòa hợp, người phối ngẫu hay ghen.";
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Anh chị em không rõ ràng về vấn đề tiền bạc, dễ vì anh chị em mà phá tài; bạn bè vay mượn sẽ không trả. Sinh kế gia đình dễ bị thiếu hụt; làm ăn đầu tư khó thu lợi; công việc hay sự nghiệp phát triển không thuận lợi; khó được cấp trên trọng dụng, đề bạt, nâng đõ; nên dựa vào nghề nghiệp chuyên môn mà mưu sinh hay kinh doanh, không nên hợp tác với người khác";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Thích hợp đi làm hưởng lương, làm ăn đầu tư khó thành, phải vay tiền mới đủ, sự nghiệp phát triển không thuận lợi, chỉ có thể gắng gượng chống đỡ, làm mà chẳng được gì. Dễ vì người phối ngẫu mà phá tài, làm được bao nhiêu phải nộp cho người phối ngẫu, quan hệ hôn nhân không được hạnh phúc, giữa vợ chồng hay oán trách nhau.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Dễ có tổn thất tiền bạc, không nên hợp tác với người khác, không nên làm ăn đầu tư bất động sản, khó mua được nhà cửa, muốn mua cũng không đủ tiền, sinh hoạt gia đình có nhiều áp lực. " .
                    "\r\n Con cái thường tiêu xài nhiều tiền, bạn cũng thường hay giao tế thù tạc, tiêu phí hường lạc không đúng, khiến gia đình không yên ổn; giữa vợ chồng gặp nhiều phiền phức, rắc rối. Có tiền sẽ muốn đầu tư hoặc hợp tác vói người khác, nhưng dễ bị lỗ vốn, tổn thất lớn.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Dễ vì tiền mà chuốc thị phi kiện tụng, không nên làm người bảo lãnh cho người khác, hoặc không nên cho vay kiếm lời; thích kiếm tiền, có nhiều cơ hội kiếm tiền, nhưng rất vất vả, phải lao lực; không nên làm ăn kinh doanh, tốt nhất nên dựa vào nghề nghiệp chuyên môn để mưa sinh." .
                    "\r\n Không nên cho bạn bè mượn tiền, họ sẽ trả tiền rất dây dưa, kéo dài và ngược lại có thể bạn cũng vậy" ;
                    $luanPhuThe .= "\r\n Giữa vợ chồng dễ có phiền phức, rắc rối, không mấy hoà hợp.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Thích hợp đi xa để kiếm tiền; khi bạn ở một nơi lâu mà kiếm tiền khó, chịu áp lực lớn, mọi sự đều không thuận lợi, đó chínhlà lúc nên đi xa. Tiền bạc của bạn không được thuận lợi, áp lực lớn, đầu tư dễ bị tổn thất, đi xa sẽ thuận lợi hơn." ;
                    $luanPhuThe .= "\r\n Bạn thường tốn nhiều tiền để tạo dựng các mối quan hệ bên ngoài tuy nhiên hiệu quả không rõ ràng. Vợ chồng sống với nhau không được hòa hợp, tốt nhất là nên kết hôn muộn.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Qua lại tiền bạc với bạn bè, thường thường là bạn bè được lợi, bản thân chịu thiệt, làm cho gia đình túng thiếu; bình thường hay cho bạn bè tiêu xài tiền chung, nhưng lúc muốn mượn tiền bạn bè thì không được." .
                    "\r\n Làm ăn khó kiếm được tiền, cũng không nên hợp tác với người khác, tốt nhất là nên đi làm hưởng lương, hoặc dựa vào nghề nghiệp chuyên môn để mưu sinh." .
                    "\r\n Bạn cũng khó mượn tiền được của bạn bè";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Thích đầu tư, lúc đầu tư không đủ tiền, sẽ đi mượn tiền để đầu tư nhưng đầu tư làm ăn nhất định sẽ kiếm được tiền, lúc gặp vận không tốt dễ bị phá sản." .
                    "\r\n không thích hợp kinh doanh mua bán, nhưng có thể làm cổ đông. Vì kiếm tiền mà bận rộn, đối xử không tốt với người phối ngẫu, thường vì công việc, vì tiền bạc mà cãi vã, hôn nhân khó hạnh phúc.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Tiền kiếm được sẽ dùng trong sinh hoạt gia đình hoặc mua bất động sản." .
                    "\r\n Tuy tiết kiệm nhưng không để dành được; nếu tích lũy được, nhưng cũng phải tích lũy từ từ, sẽ mang tiền tích lũy được ra đầu tư bất động sản, mua nhà trả góp hoặc vay tiền để mua" .
                    "\r\n bạn có quan niệm bảo thủ, sẽ không đầu cơ, tiền bạc sẽ tiêu hao đần cho đến lúc hết sạch.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .= "\r\n Thích hướng thụ, tiền kiếm được thường tiêu xài trong chuyện hường thụ, thị hiếu, không để dành được, có hành vi tiêu xài tiền sai lệch." .
                    "\r\n Làm ăn mua bán bằng tiền mặt, không nên làm sản xuất." .
                    " Kiếm tiền không nhiều"; 
                    $luanPhuThe .= "\r\n vợ chồng thường vì chuyện tiền bạc mà xày ra tranh chấp." .
                    "\r\n Sẽ đầu tư vào sự nghiệp cùa người phối ngẫu, thu hồi vốn chậm.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tài Bạch"){
                    $luanTaiBach .=  "\r\n Tài vận không tốt, xoay sở vốn liếng không được thuận lợi, dễ bị người ta lừa gạt tiền." .
                    "\r\n Dễ vì tiền bạc mà chuốc thị phi kiện tụng, không nên tự mở tiệm hoặc không nên làm người bảo lãnh, Tốt nhất là nên đi làm hường lương, hoặc dựa vào nghề nghiệp chuyên môn đế mưu sinh; đầu tư sự nghiệp, mở tiệm hay mở công xưởng đều dễ bị phá sản." ;
                    $luanPhuMau .= "\r\n Sức khỏe của cha mẹ không được tốt, bạn có tiền cũng hay cho cha mẹ tiền để tiêu xài";
                } 
                // hóa lộc - Tật ẤCH
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Tật Ách"){
                    $luanCungMenh .= "\r\n Bạn là người có tính khí tốt, tu dưỡng tốt, có độ lượng, không hay so đo tính toán, lạc quan." ;
                    $luanTatAch .= "\r\n hệ tiêu hóa không được tốt, ăn uống thiếu tiết chế, cơ thể dễ bị bệnh đau." ;
                    $luanNoBoc .= "\r\n Quan hệ tốt đẹp với cấp trên, trưởng bối; có bề ngoài ưa nhìn";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Tật Ách"){
                    $luanCungMenh .= "\r\n Có duyên với người chung quanh, lạc quan, dễ phát phì. Sức khỏe tốt, có tài ăn nói, thông minh lanh lợi, tình cảm phong phú, có lộc ăn, sẽ suy tính vì bản thân, sau kết hôn có thể hưởng hạnh phúc.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Tật Ách"){
                    $luanHuynhDe .= "\r\n Tình cảm anh chị em tốt đẹp, sẽ lấy tiền ra giúp vốn anh chị em, sinh kế gia đình sung túc; sự nghiệp hay công việc đều kiếm được tiền. ";
                    $luanPhuThe .= "\r\nNgười phối ngẫu dễ có đào hoa, tình nhân ở bên ngoài.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Tật Ách"){
                    $luanPhuThe .= "\r\n Sẽ rất yêu thương người phối ngẫu, tình cảm vợ chồng tốt đẹp, hạnh phúc. Sau kết hôn dễ phát phì, sự nghiệp làm ăn kiếm được tiền.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Tật Ách"){
                    $luanTuTuc .= "\r\n Bạn rất có duyên với người khác giới, nhiều đào hoa. Trong gia đình thì tình cảm với con cái khá tốt";
                    if($gt == "false"){
                        $luanTatAch .= "\r\n Sau khi sinh con bạn dễ bị phát phì";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Tật Ách"){
                    $luanQuanLoc .= "\r\n Hay được lòng cấp trên, trưởng bối mà được giúp đỡ trong công việc, kiếm tiền nhẹ nhàng" .
                    "\r\n Có thể tự thân lập nghiệp, ";
                    if($gt == "true"){
                        $luanQuanLoc .= "hợp tác, kiếm tiền với người khác giới khá là tốt. Bạn cũng là người thích hưởng thụ. Nếu đầu tư sáng lập cơ nghiệp khi thiếu vốn thì anh chị em trong nhà có thể giúp bạn";
                    }else{
                        $luanQuanLoc .= "thích hợp theo các nghề biểu diễn nghệ thuật. Bạn cũng là người thích hưởng thụ. Nếu đầu tư sáng lập cơ nghiệp khi thiếu vốn thì anh chị em trong nhà có thể giúp bạn";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Tật Ách"){
                    $luanThienDi .= "\r\n Bạn rất có duyên với người xung quanh, thích đi du lịch vui chơi cùng bạn bè" .
                    "\r\n bạn cũng thích đi ra ngoài, tâm tình vui vẻ, hoàn cảnh bên ngoài thuận lợi" ;
                    $luanPhuThe .= "\r\n Tình cảm vợ chồng khá hòa hợp";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Tật Ách"){
                    $luanThienDi .= "\r\n Bạn bè nhiều, có duyên với người chung quanh, rất có duyên với người khác giới, có nhiều bạn bè khác giới, dễ có đào hoa kì ngộ. ";
                    $luanPhuThe .= "\r\n Người phối ngẫu cũng dễ có tình nhân ở bên ngoài.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Tật Ách"){
                    $luanPhuThe .= "\r\n Vợ chồng đều rất có duyên với người khác giới, sau kết hôn vẫn có người theo đuổi, dễ xảy ra sự kiện đào hoa.";
                    $luanQuanLoc .= "\r\n Công việc nhẹ nhàng, sự nghiệp thuận lợi, kiếm được tiền; đồng sự trong công ti xử sự hòa hợp với nhau.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Tật Ách"){
                    $luanTatAch .=  "\r\n Sức khỏe tốt, tài vận tốt, rất có duyên với người khác giới, nhiều đào hoa.";
                    if($gt == "false"){
                        $luanTatAch .=  "\r\n Sau khi kết hôn bạn dễ phát phì";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Tật Ách"){
                    $luanTatAch .=  "\r\n Bạn là người lạc quan, sức khỏe tốt tuy nhiên dễ phát phì" ;
                    $luanThienDi .= "\r\n Các mối quan hệ xã hội phần nhiều đều tốt đẹp, bạn xem trọng hưởng thụ, ăn mặc thích dùng đồ cao cấp đắt tiền" ;
                    $luanPhuThe .= "\r\n Người phối ngẫu ra ngoài có duyên với người khác giới, cẩn thận có tình nhân bên ngoài";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tật Ách"){
                    $luanPhuMau .= "\r\n Cha mẹ có sản nghiệp để lại" .
                    "\r\n Bạn được trưởng bối, cấp trên yêu mến, dễ gặp được quý nhân trưởng bối giúp đỡ";
                    $luanTatAch .= "\r\nCơ thể mạnh khỏe nhưng dễ phát phì";
                }
                // hóa quyền - tật ách
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Tật Ách"){
                    $luanCungMenh .= "\r\n Bạn có cá tính mạnh mẽ nhưng đôi khi người khác cảm thấy bạn là người khá khó hiểu, tính tình cũng dễ bị kích động" .
                    "\r\n Bạn có thể lực tốt, chịu được vất vả. tuy nhiên cũng hãy so đo tính toán không thích bị thiệt thòi, không cam tâm để người khác lừa gạt" .
                    "\r\n Bạn dễ bị té ngã hoặc bị thương bất ngờ, ít khi mắc bệnh nhưng một khi mắc bệnh thì chữa lâu khỏi";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Tật Ách"){
                    $luanTatAch .= "\r\n Bạn có cá tính mạnh mẽ, không chịu thua ai, thể chất tuy yếu nhưng sức hoạt động lại mạnh, mắc bệnh thì chữa sẽ lâu khỏi" ;
                    $luanQuanLoc .= "\r\n Có tinh thần toan tính công việc hay sự nghiệp, nhưng phải nõ lực cạnh tranh mới đạt được mục tiêu, vì sự nghiệp gia đình mà vất vả";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Tật Ách"){
                    $luanHuynhDe .= "\r\n Thích tranh cường hiếu thắng với bạn bè, thường có ý kiến tranh chấp với anh chị em, hay cãi nhau và hay khiêu khích anh em.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Tật Ách"){
                    $luanPhuThe .= "\r\n Có xu hướng thích kiểm soát người bạn đời và có sức khỏe tốt hơn so với người đó. Nhu cầu tình dục khá mạnh mẽ và thường chủ động trong quan hệ nam nữ. Nhờ đó, có thể mang lại hạnh phúc cho người bạn đời và tình cảm vợ chồng thường rất tốt đẹp." .
                    "\r\n Vợ chồng dễ sảy ra tranh cãi vì vấn đề đào hoa bên ngoài" ;
                    $luanQuanLoc .= "\r\n Có yêu cầu nghiêm khắc đối với công việc hay sự nghiệp, nhưng người trong gia tộc đều làm nghề khác nhau.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Tật Ách"){
                    $luanTuTuc .= "\r\n Bạn đối sử rất tốt với con cái. tuy nhiên lại quản thúc con cái khá chặt" ;
                    $luanTatAch .= "\r\n Có khả năng tình dục mạnh mẽ, thường xuyên tham gia các hoạt động giao tiếp xã hội, và thiếu sự tiết chế." .
                    "\r\n Cần phải cẩn thận để tránh những tai nạn bất ngờ. Nếu mở xưởng hoặc cửa tiệm, sẽ có khả năng cạnh tranh mạnh mẽ với các đối thủ bên ngoài.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Tật Ách"){
                    $luanTaiBach .= "\r\n Có dục vọng mãnh liệt với tiền bạc và luôn lao tâm khổ tứ để kiếm tiền." .
                    "\r\n Đầu tư và khởi nghiệp sẽ có khả năng cạnh tranh cao, phải trải qua quá trình cạnh tranh mới có thể kiếm được tiền." .
                    "\r\n Rất chú trọng đến chất lượng hưởng thụ cuộc sống;cẩn thận có thể xảy ra tranh giành tiền bạc với anh chị em.";
                    if($gt=="false"){
                        $luanQuanLoc .= "\r\n Bạn có thể theo đuổi nghề biểu diễn nghệ thuật";
                    }else{
                        $luanQuanLoc .= "\r\n Bạn có thể kiếm tiền từ phụ nữ, hợp tác làm ăn hoặc buôn bán với phụ nữ";
                    }
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Tật Ách"){
                    $luanThienDi .= "\r\n Có duyên với những người xung quanh, rất bận rộn và thường xuyên phải đi lại nhiều." .
                    "\r\n Gặp nhiều cạnh tranh và dễ bị thị phi khi ở bên ngoài." ;
                    $luanTatAch .= "\r\n Dễ gặp phải tai nạn bất ngờ; vì bận rộn và vất vả làm việc, sức khỏe dần suy yếu và cơ thể bị tổn hại.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Tật Ách"){
                    $luanNoBoc .= "\r\n Bạn có duyên với bạn bè, có nhiều bạn bè, rất cẩn thận khi chọn bạn để chơi" .
                    "\r\n Công việc gặp khó khăn hay được bạn bè giúp đỡ" .
                    "\r\n Dễ vì tình cảm xung động mà vượt quá giới hạn tình bạn với người khác giới.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Tật Ách"){
                    $luanQuanLoc .= "\r\n Là người rất có tinh thần trách nhiệm và đặt yêu cầu cao đối với công việc hoặc sự nghiệp, thường xuyên phải lao tâm khổ tứ dẫn đến ảnh hưởng xấu đến sức khỏe";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Tật Ách"){
                    $luanQuanLoc .= "\r\n Bạn có thể kinh doanh hoặc mở tiệm tại nhà, trong nhà thì là người nắm quyền chủ đạo" .
                    "\r\n Bạn có dục vọng mãnh liệt đối với tài sản, có thể xảy ra tranh giành gia sản." .
                    "\r\n Khả năng tình dục mạnh mẽ; có thể dựa vào nghề chuyên môn để kiếm sống.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Tật Ách"){
                    $luanThienDi .=  "\r\n Quan hệ giao tiếp rất tốt, thích hưởng thụ rượu và sắc đẹp, sinh hoạt không có quy tắc." .
                    "\r\n Là người rất có năng lực, nhưng thường phải lao tâm lao lực và dễ gặp tai nạn.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tật Ách"){
                    $luanTatAch .=  "\r\n Bạn Có cơ thể cường tráng và hiếu động, nhưng dễ gặp phải tranh chấp và phiền phức, thường bị tổn thương bất ngờ." ;
                    $luanThienDi .= "\r\n Thường khoa trương và phóng đại về bản thân; có thể nhận được sự giúp đỡ từ những người lớn tuổi.";
                }
                // Hóa Khoa - tật ách
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Tật Ách"){
                    $luanCungMenh .= "\r\n Bạn là người tâm tính trung hậu, cừ chỉ có phong độ, khả năng tự kiềm chế rất tốt, không hùa theo người khác làm chuyện xấu." .
                    "\r\n Là người lễ độ, hiền hòa, lạc quan";
                    $luanTatAch .= "\r\n Thể chất khỏe mạnh, sức đề kháng cũng mạnh, khó mắc bệnh nặng, có bệnh sẽ gặp lương y, khỏi bệnh rất mau." ;
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Tật Ách"){
                    $luanTatAch .=  "\r\n Bạn là người Lịch sự, nhã nhặn, có phong độ, là người lạc quan, có duyên với người chung quanh; vóc người mảnh mai, cao gầy, ít khi mắc bệnh nặng." .
                    "\r\n Tâm tính bình hòa, tâm trạng ổn định không hay thay đổi thất thường";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Tật Ách"){
                    $luanThienDi .=  "\r\n Bạn cư sử với anh em trong gia đình khá bình hòa, sáng suốt, có lí lẽ, có tiết chế" ;
                    $luanTatAch .=  "\r\n sức khỏe bình ổn. có quý nhân giúp đỡ";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Tật Ách"){
                    $luanPhuThe .= "\r\n Người phối ngẫu là nhờ người khác giới thiệu mà quen biết vợ chồng tôn trọng nhau, dễ có đào hoa người thứ ba xen vào, gây ra thị phi phiền phức." ;
                    $luanQuanLoc .= "\r\n Công việc cũng bình ổn, không có quá nhiều sóng gió cạnh tranh";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Tật Ách"){
                    $luanTuTuc .= "\r\n Rất yêu thương con cái, có duyên sâu nặng với con cái, để cho con cái có không gian tự phát triển." ;
                    $luanThienDi .= "\r\n Ra ngoài ít gặp tai họa nếu có gặp thì cũng sẽ phùng hung hóa cát";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Tật Ách"){
                    $luanTaiBach .=  "\r\n Dùng tiền có kế hoạch rõ ràng, cân đối thu chi, tài chính thuận lợi, công việc hay sự nghiệp đều bình ổn." .
                    "\r\n Hưởng thụ sẽ có tiết chế, khá xem trọng hưởng thụ về phương diện tinh thần. Anh chị em trong nhà có thể giúp đỡ tiền bạc cho nhau";
                    if($gt=="false"){
                        $luanQuanLoc .= "\r\n Bạn có thể theo đuổi nghề biểu diễn nghệ thuật";
                    }else{
                        $luanQuanLoc .= "\r\n Bạn có thể kiếm tiền từ phụ nữ, hợp tác làm ăn hoặc buôn bán với phụ nữ";
                    }
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Tật Ách"){
                    $luanNoBoc .= "\r\n Các mối quan hệ xã hội khá tốt đẹp do cách cư sử của bạn với bạn bè khá sáng suốt, có lí lẽ, hiền hòa" ;
                    $luanThienDi .= "\r\n Ra ngoài bình thuận, gặp nhiều quý nhân, cơ thể ít bệnh đau.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Tật Ách"){
                    $luanNoBoc .= "\r\n Giao du bạn bè hiền hòa, lễ độ, có lí lẽ, có tài năng; không giao du lầm bạn xấu." ;
                    $luanTatAch .= "\r\n Cơ thể có quý khí, khó bị bệnh di truyền; nếu gặp tai ách, thường sẽ có quý nhân xuất hiện để giải quyết. hòa hợp với các đồng nghiệp trong chỗ làm";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Tật Ách"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp khá nhẹ nhàng, phát triển bình ổn, bản thân rất thỏa mãn với công việc." .
                    "\r\n Có thể chuyên tâm học hành hay làm việc, hòa hợp với bạn học, đồng sự, đồng nghiệp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Tật Ách"){
                    $luanThienDi .= "\r\n Bạn là người có tính tình hiền hòa, lễ độ, có thái độ cư sử khá tốt nên trong gia đình mọi người sống với nhau khá vui vẻ, ít sảy ra chanh chấp" .
                    "\r\n Bạn xử lý việc nhà rất ngăn nắp đâu ra đó";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Tật Ách"){
                    $luanTatAch .= "\r\n Rất chú trọng chất lượng của đời sống tinh thần." .
                    "\r\n Sức khỏe tốt; rất có duyên với người chung quanh, gặp nhiều quý nhân.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tật Ách"){
                    $luanTatAch .=  "\r\n Được thừa hưởng gene di truyền tốt; sống hòa hợp với trưởng bối, trong số trưởng bối có nhiều quý nhân.";
                }

                // Hóa Kỵ - tật ách
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Tật Ách"){
                    $luanTatAch .= "\r\n Cơ thể ốm yếu, dễ mắc bệnh mạn tính hoặc ám tật triền miên, nhưng không đột ngột phát bệnh nghiêm trọng, khó mắc bệnh truyền nhiễm hay bệnh ung thư.";
                    $luanCungMenh .= "\r\n Là người thẳng thắn, không có tầm cơ, đầu óc ứng biến không lanh lẹ,tuy nhiên tâm trạng không ổn định." .
                    "\r\n Tính tình hơi khó hiểu, không tin ai thái quá" .
                    "\r\n Bụng dạ không cởi mở, tự tư tự lợi, có lúc sẽ vì lợi ích của bản thân mà gây tổn hại cho người khác." .
                    "\r\n Khá vất vả, đừng để mắc nợ ai, sẽ khó trả được. Sinh hoạt vợ chồng không hòa điệu";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Tật Ách"){
                    $luanTatAch .= "\r\n Dễ mắc bệnh tiềm ẩn mà khó chữa khỏi, sẽ kéo dài rất lâu, nhưng không nguy hiểm đến tính mạng." ;
                    $luanCungMenh .= "\r\n Số vất vả, tính tốt, thẳng thắn, dứt khoát, không làm chuyện xấu, quan tâm người khác, thà bản thân chịu thiệt chớ không chiếm lợi ích của người khác." ;
                    $luanThienDi .= "\r\n Ít gặp quý nhân, lục thân không trợ giúp được nhiều; ra ngoài dễ có tai họa; làm việc vất vả khổ sở mà kiếm được ít tiền." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu hay ghen, theo dõi bạn chặt chẽ.";
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Tật Ách"){
                    $luanTatAch .= "\r\n Lúc còn nhỏ cơ thể suy nhược, thường hay bệnh đau" ;
                    $luanNoBoc .= "\r\n Không hòa hợp với anh chị em, gần nhau ít mà xa nhau nhiều." ;
                    $luanCungMenh .= "\r\n Lúc còn nhỏ có tính nghịch ngợm, hay phản kháng, dễ giao du bạn bè xấu. Khó có bạn bè tri kỷ" ;
                    $luanPhuThe .= "\r\n Vợ chồng không hợp nhau, ít gần nhau, cẩn thận có đào hoa bên ngoài";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Tật Ách"){
                    $luanPhuThe .=  "\r\n Sức khỏe của người phối ngẫu hơi kém" ;
                    $luanQuanLoc .= "\r\n Sự nghiệp kiếm không được nhiều tiền, việc làm không ổn định.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Tật Ách"){
                    $luanTuTuc .= "\r\n Bạn dạy dỗ con theo kiểu tâm trạng hóa, có lúc quá nghiêm khắc nhưng có lúc lại quá chiều chuộng" ;
                    $luanTatAch .= "\r\n Sinh hoạt về mặt tình dục khá nhiều, dễ có đào hoa, đào hoa theo kiểu nặng về nhục dục. " ;
                    $luanQuanLoc .= "\r\n Công việc khó phát triển, kiếm tiền khó khăn";
                    if($gt =="true"){
                        $luanTatAch .= " Cẩn trọng dễ mắc các bệnh liên quan đến đường sinh dục";
                    }else{
                        $luanTatAch .= " Cẩn thận dễ bị lưu sản, trụy thai.";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Tật Ách"){
                    $luanCungMenh .=  "\r\n Là người có tâm cơ khó lường, khá xem trọng tiền bạc, sẽ vì bản thân mà trù mưu tính kế.";
                    $luanTaiBach .= "\r\n Chi ra nhiều mà thu vào ít, tiền bạc không thuận lợi, nỗ lực kiếm tiền, vì kiếm tiền mà tổn hại sức khỏe.";
                    $luanTatAch .=  "\r\n Dễ mắc bệnh tiềm ẩn, thường vì sinh bệnh mà phá tài.";
                    if($gt == "false"){
                        $luanQuanLoc .= "\r\n kiếm tiền tốt trong ngành giải trí, có nhiều nhu cầu về tình dục.";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Tật Ách"){
                    $luanThienDi .= "\r\n Ra ngoài thường không hợp thủy thổ, dễ gặp tai ách bất ngờ; ở bên ngoài phần nhiều không thuận lợi, sẽ vì xung đột mà bị thương bất ngờ." ;
                    $luanTatAch .= "\r\n Sức khỏe không được tốt, năng lực tính dục không cao" ;
                    $luanTaiBach .= "\r\n Kiếm được tiền không nhiều, đi làm không được như ý, đi xa tìm hướng phát triển sẽ tốt hơn";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Tật Ách"){
                    $luanThienDi .=  "\r\n Lúc trẻ dễ giao du bạn xấu, dẫn đến nhiều thị phi, dễ xảy ra tranh chấp." ;
                    $luanTatAch .= "\r\n Lúc còn nhỏ, thể chất kém, dễ mắc bệnh lây nhiễm qua đường tính dục, cũng dễ mắc bệnh tiềm ẩn bẩm sinh, di truyền, khó chữa trị." ;
                    $luanQuanLoc .=  "\r\n Sự nghiệp đầu tư, vận kinh doanh không thuận lợi, dễ gặp nguy cơ về tài chính, khó xoay sở vốn liếng." ;
                    $luanThienDi .=  "\r\n Dễ vì tình cảm xung động mà làm việc thiệt thòi cho bản thân.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Tật Ách"){
                    $luanQuanLoc .= "\r\n Có tinh thần trách nhiệm, xem trọng sự nghiệp; làm việc hết lòng với chức trách của mình, phàm chuyện gì cũng đích thân làm." .
                    "\r\n Công việc hay sự nghiệp không thuận lợi, không ổn định, khá lao tâm lao lực; không hòa hợp với đồng sự." ;
                    $luanPhuThe .= "\r\n Lối suy nghĩ, tính tình đều không hợp với ngưòi phối ngẫu; sau kết hôn, người phối ngẫu bị sụt cân" ;
                    $luanNoBoc .= "\r\n Tài vận của anh chị em trong nhà không tốt";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Tật Ách"){
                    $luanTaiBach .= "\r\n Tài vận không thuận lợi, dễ phá tài; nhưng rất biết làm ăn, thường hay suy nghĩ làm thế nào để kiếm tiền, rất thích mua bất động sản, nhất định sẽ có cơ hội mua nhà." ;
                    $luanTatAch .= "\r\n Sức khỏe yếu, thể chất không tốt. Cuộc sống khá vất vả, gặp nhiều tai kiếp bất ngờ, cẩn thận phòng sự cố giao thông" ;
                    $luanThienDi .= "\r\n Không thích ra ngoài, ở nhà thường không yên ổn, không có duyên với con cái, dễ có khoảng cách thế hệ giữa con cái vưới cha mẹ.";
                    if($gt == "false"){
                        $luanTatAch .= "\r\n dễ bị lưu sản, hoặc phải sinh mổ, phần nhiều dễ mắc bệnh phụ khoa.";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Tật Ách"){
                    $luanCungMenh .= "\r\n Số mệnh vất vả, duyên với người xung quanh không được tốt, tâm trạng không yên ổn, dễ có sự thiên lệch, tư tường cực đoan." ;
                    $luanTatAch .= "\r\n Sức khỏe kém, dễ mắc bệnh, chữa trị gian nan, sẽ kéo dài nhiều năm." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp không thuận lợi, khó kiếm tiền." ;
                    $luanCungMenh .= "\r\n Nặng chủ quan, kiên trì với ý kiến của bản thân, dê thành người cố chấp.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Tật Ách"){
                    $luanTatAch .=  "\r\n Lúc còn nhỏ sức khỏe kém, dễ bị phát ướng." ;
                    $luanPhuMau .= "\r\n Quan hệ với trường bối không được tốt, phần nhiều rời xa cha mẹ rất sớm" ;
                    $luanThienDi .= "\r\n Ra ngoài không thuận lợi, thường không hợp thủy thổ." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp không thuận lợi, dễ gặp nguy cơ về tài chính và tình trạng văn thư thị phi kiện tụng, khó xoay sờ." ;
                    $luanTaiBach .= "\r\n Tài vận của anh chị em không thuận lợi, dễ phá tài, không giữ tiền được.";
                }
                // hóa lộc - thiên di
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Rất có duyên với người chung quanh, có tính độc lập và tự chủ; có thể gánh vác công việc một mình ở bên ngoài." .
                    "\r\n Thường bôn ba ở bên ngoài, thời gian ở bên ngoài nhiều và lâu dài, nhưng lúc ở ngoài sẽ nhớ nhà, cho nên thường cũng không đi xa lắm." .
                    "\r\n Đi xa bạn sẽ dễ kiếm tiền hơn và cũng phát triển hơn, việc kiếm tiền cũng không đến nỗi quá vất vả, độc lập về tài chính tự kiếm tiền tự tiêu xài" ;
                    $luanTaiBach .= "\r\n Có tiền cũng không mua nhà cửa, cảm thấy thỏa mãn với gia sản của tổ tiên. ";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Thiên Di"){
                    $luanCungMenh .= "\r\n Là người thông minh, ở bên ngoài có duyên với người xung quanh." .
                    "\r\n Kiếm được tiền thích hưởng thụ, ra ngoài vui vẻ, lộc ăn không thiếu." ;
                    $luanThienDi .= "\r\n Ra ngoài phải dựa vào bản thân, có nhiều cơ hội, kiếm được tiền.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Rất có duyên với người chung quanh, giao du rộng, hòa hợp với bạn bè, tình cảm anh chị em tốt đẹp, vui vẻ hạnh phúc." ;
                    $luanNoBoc .= "\r\n Có thể nhờ anh em hoặc bạn bè trợ giúp mà kiếm tiền, có tiền cũng mang về cho anh em bạn bè hường chung." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp phát triển thuận lợi, có nhiều cơ hội kiếm tiền.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Sự nghiệp có không gian để phát triển, có thể được người phối ngẫu trợ giúp, làm cho sự nghiệp phát triển thuận lợi." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với người chung quanh, có thể nhờ quý nhân khác giới tương trợ mà lập nên cơ nghiệp, có nhiều cơ hội kiếm tiền";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Thiên Di"){
                    $luanTuTuc .= "\r\n Con cái có duyên với người chung quanh, sống với nhau hòa thuận" ;
                    $luanThienDi .= "\r\n Ra ngoài nhiều giao tế thù tạc, thích nơi đông đúc, náo nhiệt quan hệ giao tế rất tốt." .
                    "\r\n Ra ngoài vui vẻ, nên đi xa, thường biến động; phần nhiều lúc xuất ngoại đều có kế hoạch tỉ mỉ." .
                    "\r\n Ở bên ngoài có nhiều cơ hội kiếm tiền";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Bạn sống khá thọ, tài vận tốt có nhiều cơ hội kiếm tiền" .
                    "\r\n Người phối ngẫu sẽ giúp vốn cho bạn làm ăn.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Là người lạc quan, tâm trạng bình hòa, có duyên với người chung quanh, có quan hệ tốt, ra ngoài thường được như ý, thân tâm vui vẻ." .
                    "\r\n Ở bên ngoài gặp nhiều đào hoa, cũng cần phải chú ý vấn dề ăn uống.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Rất có duyên với người chung quanh, giao du rộng, hòa hợp với bạn bè; có thể được anh em, bạn bè trợ giúp mà kiếm được tiền, có tiền cũng sẽ mang về cho anh em và bạn bè cùng hưởng.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp khá bôn ba vất vả, nhưng có nhiều cơ hội kiếm tiền, tài vận tốt." .
                    "\r\n Bạn sống rất thọ, Có thể được cấp trên xem trọng, đồng sự vui vẻ.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Thiên Di"){
                    $luanThienDi .=  "\r\n Ra ngoài có nhiều cơ hội kiếm tiền, có thể mua tậu nhà cửa, nuôi dưỡng gia đình, tuy nhiên cẩn thận phòng tai nạn bất ngờ";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Thiên Di"){
                    $luanThienDi .=  "\r\n Ra ngoài có khẩu phúc, tài vận tốt, được hưởng thụ." ;
                    $luanPhuThe .= "\r\n Sự nghiệp người phối ngẫu có phát triển, vợ chồng hòa thuận";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Ra ngoài tự lo liệu, không để người khác phải lo lắng; dễ được trường bối, quý nhân tương trợ." ;
                    $luanQuanLoc .= "\r\n Thích hợp làm công chức, cẩn thận phòng nạn tai bất ngờ." ;
                    $luanTatAch .= "\r\n Chú ý thói quen ăn uống, và vấn đề vệ sinh.";
                }
                // hóa quyền - thiên di
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Thiên Di"){
                    $luanCungMenh .= "\r\n Bạn Không quá tín nhiệm ai, trong ngoài phân biệt rất rõ ràng, khá chuyên quyền, sợ quyền lực rơi vào tay người khác." .
                    "\r\n Cá tính mạnh, sáng suốt tài cán và có kinh nghiệm, thích lãnh đạo người khác, nhưng lại không có thực quyền." .
                    "\r\n Thích đấu đá với người khác, dễ chuốc thị phi." .
                    "\r\n Tính tình bướng binh, ưa ra oai, phách lối, không câu nệ tiểu tiết." .
                    "\r\n Thích biểu hiện cái tôi, ưa được người ta kính trọng, bị tiểu nhân hãm hại." .
                    "\r\n Sức hoạt động khá mạnh, làm việc khó giữ giới hạn cùa mình,ưa can thiệp, xen vào chuyện cùa người khác.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Có năng lực và tài năng, là người có bản lãnh; ra ngoài biểu hiện ưu tú, được người ta tôn trọng và khẳng định." .
                    "\r\n Nhưng nếu bạn muốn nắm quyền hoặc tranh giành địa vị lãnh đạo với người khác, sẽ dễ xảy ra phiền phức, rắc rối." .
                    "\r\n Lực cạnh tranh khá mạnh, có phát triển, cần phải trải qua nỗ lực phấn đấu mới có thành tựu.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Bạn có nhiều mối quan hệ, có tác phong thương nghiệp đô thị; có tài năng, địa vị và danh vọng, ưa tranh cường hiếu thắng, thích làm nhân vật lãnh đạo, tranh giành địa vị, mà xúc phạm, làm người khác khó chịu, chuốc thị phi." .
                    "\r\n Nơi làm việc có lực canh tranh cao";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Thiên Di"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có địa vị, có thể nắm quyền, hay có nhiều ý kiến, dễ xảy ra tranh chấp." .
                    "\r\n Người phối ngẫu rất có năng lực, có tài năng, làm việc chăm chi hay cạnh tranh, nhiều thị phi.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Thích ra bên ngoài, ở bên ngoài phần nhiều đều có biểu hiện ưu tú; ở nhà hay ra oai, ưa khiêu khích." .
                    "\r\n Ở bên ngoài dễ có đào hoa theo kiểu nhục dục, dễ có hành vi khinh suất.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Thiên Di"){
                    $luanTaiBach .= "\r\n Thích kiếm tiền, cũng có nhiều cơ hội; khá bận rộn, vì công việc làm ăn mà phải giao tế tạo dựng mối quan hệ nhiều." .
                    "\r\n Kiếm tiền vất vả, gặp nhiều cạnh tranh." .
                    "\r\n Tiêu xài tiền rộng rãi cho việc hưởng thụ; có phong cách thương nghiệp đô thị." .
                    "\r\n Có thể dựa vào tài năng, nghề nghiệp chuyên môn để tự lực kiếm tiền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Thiên Di"){
                    $luanCungMenh .= "\r\n Cá tính mạnh, rất nóng nảy, sức hoạt động mạnh." .
                    "\r\n Có tài năng, sự nghiệp như ý, có tham vọng quyền lực, dễ xảy ra xung đột, sẽ gặp phiền phức, rắc rối." .
                    "\r\n Tâm trạng bất ổn, dễ có mâu thuẫn nội tâm.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Giao du rộng, tiêu xài tiền hào phóng, giỏi giao tiếp, có năng lực lãnh đạo, được nhiều trưởng bối và bạn bè giúp đỡ." .
                    "\r\n Giao du bạn bè có chọn lựa, bạn bè có năng lực, cạnh tranh nhau, dễ có tranh chấp.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Có tinh thần trách nhiệm, có nhiều cơ hội thăng tiến, sùng thượng địa vị và quyền lực, có xung lực sáng lập cơ nghiệp, sự nghiệp có phát triển.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Bất động sản thường sẽ tăng giá trị, không thỏa mãn với tổ nghiệp." .
                    "\r\n Thường ra ngoài, ờ bên ngoài không bị câu thúc." .
                    "\r\n Có suy nghĩ mua thêm bất động sản, hoặc cho thuê nhà.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Thiên Di"){
                    $luanCungMenh .=  "\r\n Thích hường thụ, sẽ phô trương, sang trọng, hào phóng, ưa thể diện, tạo sự chú ý." .
                    "\r\n Có năng lực quản lí tài chính, tài vận khá tốt";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Thiên Di"){
                    $luanThienDi .=  "\r\n Ra ngoài được trưởng bối, quý nhân đề bạt, nâng đỡ, trợ giúp. Tuy nhiên cẩn trọng dễ bị người ta ức hiếp bắt nạt khiến người nhà lo lắng";
                }
                // Hóa Khoa - thiên di
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Thiên Di"){
                    $luanCungMenh .= "\r\n Bạn là người Nói năng nhã nhặn, cử chỉ có phong độ, lịch sự, thông minh,hiền hòa, không tính toán so đo." .
                    "\r\n Nên đi xa để học tập, ròi xa gia đình tìm hướng phát triển sẽ dễ có thanh danh." ;
                    $luanThienDi .= "\r\n Ở bên ngoài cát lợi, làm việc sẽ có quý nhân giúp đỡ, giới thiệu, đề bạt, nâng đỡ; thích hợp làm công việc về văn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Thiên Di"){
                    $luanThienDi .=  "\r\n Rất có duyên với người chung quanh, có tiếng tăm ở bên ngoài, có thể nhờ văn hóa nghệ thuật mà danh tiếng vang xa." .
                    "\r\n Ra ngoài có quý nhân giúp đỡ";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Thiên Di"){
                    $luanNoBoc .=  "\r\n Bạn giúp đỡ cho các anh chị em trong nhà, bạn là quý nhân của họ" .
                    "\r\n Xã giao hòa hợp với bạn bè, bạn bè phần nhiều là người hiền hòa, lễ độ, \"quân tử chi giao\", bạn bè thường là người có tiếng tăm, phần nhiều có thể giúp đỡ lần nhau";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Sáng lập được cơ nghiệp, trong công việc có quý nhân giúp đỡ, sự nghiệp bình ổn, có tiếng tăm nhưng không mở rộng được." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có thanh danh, có giúp đỡ cho sự nghiệp của bạn." .
                    "\r\n Vợ chồng sống với nhau bình yên, hòa hợp, ít xảy ra tranh chấp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Thường đi xa, ra ngoài nhiều vui vẻ, có quý nhân giúp đỡ; cũng sẽ có đào hoa, nhưng thuộc loại đào hoa về tinh thần." .
                    "\r\n bạn ở bên ngoài thường nghĩ về gia đình; con cái ở bên ngoài có tiếng tăm.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Thiên Di"){
                    $luanTaiBach .=  "\r\n Dùng tiền có kế hoạch rõ ràng, cân đối thu chi, thu nhập ổn định, tài vận bình ổn, thuận lợi, ít nhưng đều đặn." ;
                    $luanThienDi .= "\r\n Ở bên ngoài có quý nhân giúp đỡ, cư sử với mọi người 1 cách hiền hòa, lễ độ và có lí tính";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Rất có duyên với người chung quanh, sức khỏe tốt, gặp nhiều quý nhân, dễ được trưởng bối quan tâm." .
                    "\r\n Ở bên ngoài có tâm tình tốt, tâm trạng ổn định, ít nạn tai.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Thiên Di"){
                    $luanNoBoc .= "\r\n Giao du với bạn bè hiền hòa, lễ độ, có quý khí,có lí tính, có thể giúp đỡ lẫn nhau." .
                    "\r\n Bạn bè phần nhiều đều có chút tiếng tăm.";
                    $luanHuynhDe .= "\r\n Anh chị em quan tâm lẫn nhau, đối xử với nhau rất tốt, ít có chuyện tranh chấp." ;
                    
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều bình yên, ổn định, có tiếng tăm, ít xày ra biến động, nhưng quy mô không lớn." .
                    "\r\n Đi làm hưởng lương sẽ ổn định, dễ thăng tiến." ;
                    $luanPhuThe .="\r\n Vợ chồng hòa hợp, ít có chuyện tranh chấp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Thường có cơ hội đi ra ngoài, cuộc sống gia đình vui vẻ." .
                    "\r\n Gia sản ít biến động, cảm thấy thỏa mãn với tổ nghiệp, sống yên ở quê nhà.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Ra ngoài có tâm tình tốt, có thể hưởng phước, làm công việc khá nhàn hạ." ;
                    $luanTaiBach .= "\r\n Dùng tiền có kế hoạch rõ ràng, có thể cân đối thu chi, \"tài vận\" binh ổn, ít sóng gió.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .=  "\r\n Có thể được trường bối quan tâm chiếu cố, đề bạt, nâng đỡ.";
                }

                // Hóa Kỵ - thiên di
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Thiên Di"){
                    $luanCungMenh .= "\r\n Tính tình hay thay đổi, tâm trạng dễ bị hoàn cảnh bên ngoài ảnh hường hoặc kích thích." .
                    "\r\n Người khác có thể cảm giá bạn là người khó hiểu, tính thẳng như ruột ngựa" .
                    "\r\n Có mặc cảm tự ti, dễ bị thiệt thòi. Ra bên ngoài không được yên ổn, thường nhớ gia đình." .
                    "\r\n ít gặp quý nhân, duyên với người chung quanh cũng kém, ít được giúp đỡ, dễ bị tiểu nhân hãm hại, chuốc thị phi." .
                    "\r\n Không nhẫn nại, thiếu bình tĩnh, thường có cơ hội xuất ngoại hay đi xa, ra bên ngoài sẽ bôn ba nhiều, không được thuận lợi, nên phòng nạn tai bất ngờ" ;
                    $luanTatAch .= "\r\n Cơ thể suy nhược, làm lụng khá vất vả" ;
                    $luanPhucDuc .= "\r\n Vận về già khá cô độc, một đời nhiều thăng trầm, khá gập gềnh" ;
                    $luanPhuThe .= "\r\n Người phối ngẫu tiêu xài nhiều tiền ở bên ngoài. Giữa vợ chồng thường hay xảy ra tranh chấp ";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Không thích đi xa, đi xa sẽ muốn về nhà, tuy nhiên ở nhà sẽ không được lâu; cũng thường bôn ba ở bên ngoài." .
                    "\r\n Ra bên ngoài không được thuận lợi không nên đi xa làm ăn, ở bên ngoài không có quý nhân, dễ bị tiểu nhân hãm hại, duyên với người chung quanh kém, thường bị thiệt thòi." .
                    "\r\n Sự nghiệp ở tha hương hay nước ngoài đều bất lợi, nên phát triến ở quê nhà, thích hợp làm nghề buôn bán có tính lưu động." ;
                    $luanQuanLoc .= "\r\n Không nên đầu tư sáng lập cơ nghiệp, thích hợp dựa vào nghề nghiệp chuyên môn để mưu sinh." ;
                    $luanThienDi .= "\r\n Đi xa dễ gặp sự cố giao thông";
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Thiên Di"){
                    $luanHuynhDe .= "\r\n Anh chị em không có giúp đỡ, bạn bè giao du không nhiều, ít bạn bè tri kỉ, bạn bè không giúp đỡ, qua lại tiền bạc với bạn bè dễ gặp phiền phức, rắc rối." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp không được thuận lợi, ít phát triển." ;
                    $luanThienDi .= "\r\n Ra bên ngoài sẽ tiêu xài nhiều tiền, thu không bằng chi, ảnh hưởng đến sinh kế gia đình.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Thiên Di"){
                    $luanPhuThe .=  "\r\n Người phối ngẫu tiết kiệm tiền, sợ bị chịu thiệt, hay so đo tính toán." .
                    "\r\n Tình cảm vợ chồng không hòa hợp; sự nghiệp của người phối ngẫu không có sự giúp đỡ";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Đi xa, bôn ba nhiều mà không thuận lợi." .
                    "\r\n Gia vận không phát, rất ít khi ở nhà, cần chú ý phòng sự cố giao thông hoặc đánh nhau với người ta.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Thiên Di"){
                    $luanTaiBach .=  "\r\n ít có cơ hội kiếm tiền, vì tiền mà phải bôn ba nhiều, khó sống hòa hợp với người khác, giao tiếp với người khác không được tốt, sự nghiệp phát triển không thuận lợi." ;
                    $luanThienDi .= "\r\n Ra bên ngoài không thuận lợi, vì hưởng thụ mà tiêu xài tiền một cách uổng phí." ;
                    $luanPhuThe .= "\r\n Tài vận của người phối ngẫu không thuận lợi, tiền bạc dễ bị hao tốn"; 
                    $luanQuanLoc .= "\r\nbận rộn kiếm tiền nên không có phúc hưởng thụ.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Thường có cơ hội đi nước ngoài, nhưng ra bên ngoài không được thuận lợi, thường không hợp thủy thổ, ảnh hưởng đến sức khỏe thân tâm." .
                    "\r\n Nơi làm việc không ổn định, tâm trạng không ổn định, dễ bị thua thiệt, ít được trưởng bối, quý nhân quan tâm.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Thiên Di"){
                    $luanNoBoc .=  "\r\n Anh chị em ít giúp đỡ bạn, ít gặp quý nhân; không có bạn bè tri ki, ít bạn bè, không giỏi giao tiếp xã giao." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều phát triển không thuận lợi, đi làm công ti quy mô nhỏ." ;
                    $luanTaiBach .= "\r\n Sinh hoạt gia đình khá vất vả";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Thiên Di"){
                    $luanQuanLoc .= "\r\n Sự nghiệp phát triển không thuận lợi, không có bối cảnh nhân sự tốt trong công việc, bận rộn mà thu hoạch không lớn, làm nhiều mà thành ít." ;
                    $luanThienDi .= "\r\n Ra bên ngoài cần phải phòng tai họa, nạn tai bất ngờ." ;
                    $luanPhuThe .= "\r\n Vợ chồng sống với nhau kkông được hòa hợp, sinh hoạt tình cảm không thuận lợi.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Thiên Di"){
                    $luanThienDi .= "\r\n Chủ về biến động, sẽ dời nhà hoặc sẽ dời công ti." .
                    "\r\n Ra bên ngoài dễ có sự cố giao thông, phải cẩn thận gây ra tai nạn, dễ xảy ra nạn tai bất ngờ." ;
                    $luanDienTrach .= "\r\n Không cảm thấy thõa mãn với tổ nghiệp, nhưng khó mua tậu nhà cửa.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Thiên Di"){
                    $luanCungMenh .= "\r\n Số mệnh vất vả, bận rộn nhiều mà thành tựu ít. Thể chất kém, tài vận không tốt" .
                    "\r\n Vì hưởng lạc mà lãng phí tiền bạc, không có kế hoạch rõ ràng, không tiết chế. Sự nghiệp của người phối ngẫu phát triển không thuận lợi, vợ chồng duyên phận bạc, thường hay xảy ra tranh chấp hoặc gần nhau ít mà xa nhau nhiều, ít trao đổi cảm thông nhau.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Thiên Di"){
                    $luanThienDi .=  "\r\n Thường có cơ hội đi nước ngoài, nhưng dễ không hợp thủy thổ, dễ có tai họa bất ngờ, cơ thể cũng dễ bị thương." .
                    "\r\n Ra bên ngoài phần nhiều không được thuận lợi, làm cho cha mẹ lo lắng." ;
                    $luanPhuMau .= "\r\n Cha mẹ thường bôn ba ở bên ngoài, không thể quan tâm chăm lo nhiều cho bạn." .
                    "\r\n Gia đình của người phối ngẫu có nhiều thị phi.";
                }
                // hóa lộc - nô bộc
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn phần lớn giao du bạn bè có tiền, có địa vị, bạn bè đối xử với nhau tốt." .
                    "\r\n Rất có ý tứ với bạn bè, qua lại tiên bạc, bạn bè không trả cũng không để bụng; là người tốt quá mức, không so đo tính toán với bạn bè." .
                    "\r\n Giao du rộng, nhiều bạn bè, hợp tác với bạn bè dễ kiếm được tiền.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè đối xừ rất tốt với bạn, thán phục tài năng của bạn, quan tâm bạn." .
                    "\r\n Rất có duyên với người khác giới, rất được bạn bè khác giới ngưỡng mộ, đào hoa sẽ chủ động đến cửa.";
                    if(kiemTraTuHoaPhai($laSoData,"Nô Bộc","Hóa Kỵ","Tài Bạch")||kiemTraTuHoaPhai($laSoData,"Nô Bộc","Hóa Kỵ","Quan Lộc")){
                        $luanTaiBach .= "\r\n Vì bạn bè mà hao tiền tốn của";
                    }
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Tử Tức","Hóa Kỵ")){
                        $luanNoBoc .= "\r\n bạn bè kết giao với bạn hoặc bạn bè muốn bạn giúp họ xoay sở tiền bạc.";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Nô Bộc"){
                    $luanPhucDuc .= "\r\n Gia trạch hưng vượng, nhiều anh chị em, có thể giúp đỡ lẫn nhau, sống với nhau hòa mục." ;
                    $luanNoBoc .= "\r\n Bạn bè có thể giúp bạn kiếm tiền";
                    if(kiemTraTuHoaPhai($laSoData,"Nô Bộc","Hóa Kỵ","Mệnh")){
                        $luanNoBoc .= "\r\n Các cuộc gặp gỡ bạn bè bên ngoài phần lớn đều do bạn mời, nhưng có bạn bè tốt mời bạn trước.";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Nô Bộc"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có sức khỏe tốt, rất có duyên với người chung quanh, lạc quan." .
                    "\r\n Bạn đối sử rất tốt với người phối ngẫu" ;
                    $luanNoBoc .= "\r\n Bạn bè có thể giúp bạn phát triển sự nghiệp";
                    if(kiemTraTuHoaPhai($laSoData,"Nô Bộc","Hóa Kỵ", "Mệnh")){
                        $luanNoBoc .= " và cũng muốn bạn sẽ báo đáp.";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Có duyên với người khác giới, gặp gỡ nhiều đào hoa." .
                    "\r\n Bạn kết giao với nhiều với bạn bè, bạn bè có thể giúp bạn kiếm tiền.";
                    if(kiemTraTuHoaPhai($laSoData,"Nô Bộc","Hóa Kỵ","Mệnh")){
                        $luanNoBoc .= " \r\n Tuy nhiên bạn bè ỷ lại vào bạn, mục đích của họ là muốn tiền bạc của bạn";
                    }
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Thiên Di","Hóa Kỵ")){
                        $luanNoBoc .= "\r\n bạn bè sẽ moi tiền của bạn, bạn chỉ có chi ra mà không có thu hoạch.";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè thường rủ bạn đi ăn nhậu, còn có thể giúp bạn kiếm tiền.";
                    if(kiemTraTuHoaPhai($laSoData,"Nô Bộc","Hóa Kỵ","Mệnh")){
                        $luanNoBoc .= " \r\n bạn bè giúp bạn rất nhiều, bạn bè của bạn phần nhiều đều là bạn thâm giao nhiều năm" .
                            "\r\n  nếu mở công ty, là ý tượng: nhân viên làm thuê lâu dài, trung thành và tận tụy, nhưng không thể hợp tác cổ đông với bạn bè hoặc nhân viên, dễ có phiền phức, rắc rối.";
                    }
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Thiên Di","Hóa Kỵ")||kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Tài Bạch","Hóa Kỵ")||kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Quan Lộc","Hóa Kỵ")){
                        $luanNoBoc .= "\r\n bạn bè sẽ làm bạn hao tốn tiền bạc.";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè thường trả tiền mời bạn đi ăn uống" ;
                    $luanPhucDuc .= "\r\n Có duyên với người khác giới, nhiều đào hoa, cơ thể khỏe mạnh, có thể phùng hung hóa cát.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè giao du hòa hợp, rất quan tâm bạn; có cơ hội kiếm tiền sẽ giới thiệu cho bạn." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với người chung quanh, thích ra ngoài vui chơi, gặp nhiều quý nhân." .
                    "\r\n Tình cảm vợ chồng tốt đẹp; công việc của người phối ngẫu khá thuận lợi.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè có thể giúp bạn kiếm tiền, hợp tác vui vẻ.";
                    if(kiemTraTuHoaPhai($laSoData,"Nô Bộc","Hóa Kỵ","Mệnh")){
                        $luanNoBoc .= " \r\n sẽ kết hôn sớm, nhưng không nên qua lại tiền bạc với bạn bè.";
                    }
                    if(kiemTraTuHoaPhai($laSoData,"Nô Bộc","Hóa Kỵ","Tài Bạch")){
                        $luanNoBoc .= " \r\n Bạn đối đãi với bạn bè rất tốt";
                    }
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Thiên Di","Hóa Kỵ")||kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Tử Tức","Hóa Kỵ")){
                        $luanNoBoc .= "\r\n bạn bè sẽ làm bạn hao tốn tiền bạc.";
                    }
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Phúc Đức","Hóa Kỵ")||kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Phu Thê","Hóa Kỵ")){
                        $luanNoBoc .= "\r\n bạn bè sẽ làm bạn hao tốn tiền bạc.";
                    }
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Điền Trạch","Hóa Kỵ")){
                        $luanNoBoc .= "\r\n Khi có cơ hội kiếm tiền, bạn bè sẽ nói cho bạn biết";
                    }
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .=  "\r\n Có thể hợp tác, bạn bè có thể giúp bạn kiếm tiền, cũng thích đến nhà bạn chơi." ;
                    $luanPhuThe .= "\r\n bạn rất có duyên với người khác giới, dễ có đào hoa, dễ có tình trạng sống chung như vợ chồng." ;
                    $luanPhucDuc .= "\r\n Có thu nhập của tổ nghiệp để chi dụng trong gia đình.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Nô Bộc"){
                    $luanPhuThe .=  "\r\n bạn có duyên với người khác giới, dễ có đào hoa xen vào hôn nhân." .
                    "\r\n Bạn bè có thể giúp cho sự nghiệp của người phối ngẫu phát triển." .
                    "\r\n Công việc hay sự nghiệp của người phối ngẫu khá thuận lợi." ;
                    $luanNoBoc .= "\r\n Thường ăn uống với bạn bè có cùng sở thích.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Nô Bộc"){
                    $luanPhuMau .= "\r\n Có thể thừa kế sự nghiệp của cha mẹ, cha mẹ có phúc trạch, có thể được cha mẹ, trưởng bối quan tâm";
                }
                // hóa quyền - Nô Bộc
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè thường hay ra oai, phách lối, có tài năng, phần nhiều là người có quyền thế, có địa vị; sẽ chọn bạn để giao du, rất tốt với bạn bè." .
                    "\r\n Bạn bè ưa tạo sự chú ý, sẽ tranh quyền với bạn, sẽ kéo chân của bạn." ;
                    $luanQuanLoc .= "\r\n bạn lãnh đạo bộ phận có năng lực, nhưng khó quản lí. ";
                    $luanPhuThe .= "\r\nNgười phối ngẫu có cơ thể khỏe mạnh, ít bị bệnh tật, sức chịu đựng rất mạnh, nhưng một khi mắc bệnh sẽ khó chữa khỏi." .
                    "\r\n Người phối ngẫu cá tính mạnh, dễ kích động, không cam tâm bị người khác lựa gạt, không thích bị thua thiệt, hay so đo với người khác; ông chủ, cấp trên có thế mạnh hơn, người phối ngẫu cũng sẽ so đo thị phi.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè có lực cạnh tranh mạnh, khiến bạn có cảm giác bị áp bức." .
                    "\r\n Giao du với bạn bè ưa cạnh tranh, tranh quyền chủ đạo, cũng thường hay xảy ra tranh chấp." .
                    "\r\n Hai bên cũng có thể bàn bạc, hỗ trợ nhau, khẳng định nhau.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Nô Bộc"){
                    $luanHuynhDe .= "\r\n Anh chị em có tính độc lập rất mạnh, ra bên ngoài gặp nhiều cạnh tranh, có thể có thành tựu." ;
                    $luanTaiBach .= "\r\n Tài chính gia đình ổn định nhưng phải tiêu xài nhiều" ;
                    $luanQuanLoc .= "\r\n Trong công việc hay sự nghiệp, bạn bè hay cạnh tranh với bạn." ;
                    $luanNoBoc .= "\r\n Bạn bè của bạn thích tạo sự chú ý, khá chuyên quyền, hay đấu đá với người khác mà chuốc thị phi.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè xem trọng sự nghiệp, có thể trợ giúp bạn làm cho sự nghiệp phát triển, nhưng cũng sẽ can dự vào công việc hay sự nghiệp của bạn." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có cá tính mạnh, thể chất yếu, dê gặp nạn tai bất ngờ." .
                    "\r\n Người phối ngẫu rất có duyên với người khác giói, dễ bị đào hoa quyến dụ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Giao du với bạn bè khác giới, dễ xảy ra quan hệ tính dục." .
                    "\r\n Bạn bè sẽ lấy tài năng, nghề chuyên môn ra hợp tác với bạn." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có năng lực tính dục khá mạnh, ham muốn nhiều, khó tiết chế." ;
                    $luanNoBoc .= "\r\n Bạn bè ở bên ngoài hay cạnh tranh với người khác, dễ có thị phi.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè cạnh tranh với bạn để kiếm tiền, ngầm đấu đá, âm mưu lừa gạt tiền của bạn." .
                    "\r\n bạn giao du với bạn bè có tính hay ra oai, phách lối, ưa gây sự chú ý.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè thế mạnh hơn, rất chủ động; nếu giao du với bạn bè khác giới sẽ bị họ đeo bám, dễ có hành vi bạo lực." .
                    "\r\n Nên cẩn thận phòng tai họa bất ngờ. Dễ xảy ra tranh cãi thị phi với đồng sự hoặc ông chủ trong công ti.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Nô Bộc"){
                    $luanThienDi .= "\r\n bạn ờ bên ngoài thường hay tranh với người khác tạo sự chú ý, dễ bị tiểu nhân hãm hại, chuốc thị phi tranh chấp." ;
                    $luanNoBoc .= "\r\n Bạn bè có thế mạnh, hay can thiệp vào chuyện của bạn, làm bạn có cảm giác bị áp bức.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè có tài năng, cá tính mạnh, không chịu thua, có thể giúp bạn phát triển công việc hay sự nghiệp." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp có thể phát triển; nếu nhậm chức sẽ dễ thăng tiến, quyền cao chức trọng." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu bận rộn làm việc, dễ tổn hại sức khỏe, có duyên với người chung quanh, nhưng cũng dễ gặp nạn tai bất ngờ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Giao du bạn bè ổn định, có thể duy trì lâu dài, càng lâu càng thân tình." .
                    "\r\n Chiêu đãi bạn bè ở nhà quá sang trọng và phô trương." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu nắm quyền trong nhà, có nghề chuyên môn để mưu sinh, có tham vọng cao về tiền bạc." .
                    "\r\n Người phối ngẫu có năng lực tính dục khá mạnh, dễ có đào hoa.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .=  "\r\n Bạn bè ưa tạo sự chú ý, thích phô trương, có năng lực kiếm tiền, lúc thù tạc bạn bè thường xuất tiền nhiều hơn." ;
                    $luanDienTrach .= "\r\n bạn có thể được thừa kế tổ nghiệp mà phát dương quang đại.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Nô Bộc"){
                    $luanPhuMau .=  "\r\n Sự nghiệp của cha mẹ gặp nhiều cạnh tranh, có phát triển từ nhỏ thành lớn." ;
                    $luanNoBoc .=  "\r\n Bạn bè ưa sai bảo bạn, gây áp lực cho bạn";
                }
                // Hóa Khoa - nô bộc
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Xử sự hòa mục với bạn bè, bạn bè có tài năng, có tu dưỡng, lịch sự nhã nhặn, thường giúp đỡ bạn, không hay so đo tính toán." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu tính tình trung hậu, có phong độ, có thể tự kiềm chế, không hùa theo người khác làm chuyện xấu, là người lạc quan, có sức đề kháng bệnh khá mạnh, mắc bệnh cũng dễ chữa. ";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .=  "\r\n Giao du chân thành, xử sự hiền hòa, có lí tính với bạn bè; bạn là quý nhân của bạn bè.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Nô Bộc"){
                    $luanHuynhDe .=  "\r\n Anh chị em ở bên ngoài làm ăn thuận lợi, có thanh danh, phần nhiều đều có quý nhân giúp đỡ, có thể phùng hung hóa cát.";
                    $luanNoBoc .= "\r\n bạn bè hành sự quang minh lỗi lạc, hiểu biết lễ nghĩa, không so đo tính toán với người khác.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè quan tâm sự nghiệp của bạn, còn có giúp đỡ; trong công việc, cấp trên hoặc đồng sự có giúp đỡ cho bạn." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có sức khỏe tốt, ít sinh bệnh; cũng rất có duyên với người chung quanh, đối nhân xử thế chân thành, là người lạc quan";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè rất quan tâm lo lắng cho gia đình của bạn; sinh hoạt gia đình bình yên, nhãn nhã." ;
                    $luanTuTuc .= "\r\n Con cái có nhu cầu tiền bạc không lớn, dùng tiền có kế hoạch rõ ràng.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .=  "\r\n bạn và bạn bè thường dùng tiền chung với nhau; lúc cấp bách sẽ có quý nhân giúp đỡ." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu dùng tiền có kế hoạch rõ ràng, cân đối thu chi.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Nô Bộc"){
                    $luanTatAch .= "\r\n Gene di truyền tốt, không mắc bệnh di truyền, có sức đề kháng bệnh rất mạnh.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè xừ sự với nhau chân thành, tình bạn lâu bền, có thể giúp đỡ lẫn nhau, không tính toán so đo.";
                    
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Nô Bộc"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp được nhiều giúp đỡ, hòa mục với đồng nghiệp.Sự nghiệp có thanh danh";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Nô Bộc"){
                    $luanPhucDuc .= "\r\n Gia vận tốt, nếu gặp khó khăn sẽ có quý nhân tương trợ." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu biết quản lí gia đình, mọi việc đều ngăn nắp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Nô Bộc"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với người chung quanh, sức khỏe tốt, chú trọng đời sống tinh thần." .
                    "\r\n Công việc hay sự nghiệp của người phối ngẫu khá bình ổn thuận lợi, đồng nghiệp hòa hợp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Nô Bộc"){
                    $luanPhuMau .=  "\r\n Cha mẹ là người sáng suốt, có lí lẽ, hiền hòa lễ độ, đối xử với mọi người rất hiền hòa." ;
                    $luanQuanLoc .= "\r\n Công việc của bạn khá thuận lợi, có quý nhân giúp đỡ, trưởng bối quan tâm, có thể cùng nhau giúp cho sự nghiệp cùa cha mẹ.";
                }

                // Hóa Kỵ - nô bộc
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Giao du bạn bè không ổn định, hay thay đổi; ít có bạn tri ki, khó có bạn thâm giao." .
                    "\r\n Bạn bè sẽ khá vô tình, tự tư tự lợi, sẽ lợi dụng hoặc phản bội, bán đứng bạn." .
                    "\r\n Có tình trạng mắc nợ nhau rối rắm, không rõ ràng giữa bạn bè. Thiếu nợ thì phải trả, một xu một hào cũng không bỏ; nhưng người khác thiếu tiền bạn, thường là không cách nào thu tiền về, lúc đi đòi thì tình bạn cũng sẽ mất." .
                    "\r\n Nhân viên hay thuộc cấp tuy nhiều nhưng không đắc lực, khó quản lí; thường hay thay đổi nhân viên." .
                    "\r\n Không nên hợp tác với người khác, dễ bị thua thiệt.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè rất ỷ lại vào bạn, lúc ăn uống vui chơi cùng bạn bè đều do bạn chi trà, nhưng rất ít khi được đáp trả." .
                    "\r\n ít bạn bè, phần nhiều là bạn bè gây khó xử; bạn cũng có rất ít bạn thâm giao." ;
                    $luanThienDi .= "\r\n Rất có duyên với người khác giới, dễ có đào hoa rắc rối, phiền phức." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có sức khỏe kém, dễ bị bệnh đau, không có phúc trạch, duyên phận bạc với bạn, dễ xảy ra tình huống sinh li từ biệt." ;
                    $luanQuanLoc .= "\r\n không nên hợp tác làm ăn với người khác. dễ bị thua thiệt";
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè phần nhiều là người ở nơi khác, bất lợi đối với bạn, không được giao du thân thiết, sẽ làm bạn hao tốn tiền bạc, ảnh hưởng đến sinh kế gia đình." .
                    "\r\n Không nên hợp tác với bạn bè, cũng đừng qua lại tiền bạc hay cho mượn đồ quý giá, sẽ bị thua thiệt, mà còn gặp phiền phức, rắc rối." ;
                    $luanPhuThe .= "\r\n Sức khỏe của người phối ngẫu không được tốt, dễ gặp nạn tai bất ngờ." .
                    "\r\n Nêu có đối tượng đào hoa bên ngoài là do đối phương chủ động đến, dễ có phiền phức, rắc rối.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Nô Bộc"){
                    $luanQuanLoc .=  "\r\n Bạn bè không giúp đỡ cho công việc hay sự nghiệp cùa bạn, mà còn gây thiệt hại. " ;
                    $luanPhuThe .= "\r\n bạn là người hay ghen tuông, sẽ quản thúc chuyện giao du bạn bè của người phối ngẫu.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè tìm bạn để hợp tác hoặc đề thù tạc, sẽ là bạn hao tốn tiên bạc." .
                    "\r\n Bạn bè không thích đến nhà bạn. Nhà ở không được yên ổn, dễ xảy ra chuyện phiền phức, rắc rối." .
                    "\r\n Có duyên với người khác giới, sẽ có đào hoa gây rắc rối.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .=  "\r\n Bạn bè sẽ làm hao tốn tiền bạc của bạn, đừng qua lại tiền bạc với bạn bè, cũng đừng cho người ta vay tiền để lấy lãi, dễ bị giật." ;
                    $luanCungMenh .= "\r\n Lúc còn nhỏ, cuộc sống gia đình không được sung túc.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\nKhông biết lòng dạ của bạn bè, dễ bị bạn bè gây lụy mà dẫn đến thị phi kiện tụng." ;
                    $luanQuanLoc .= "\r\nKhông nên làm người bảo lãnh, dễ có phiền phức, rắc rối về văn thư hợp đồng." ;
                    $luanThienDi .= "\r\n Rất có duyên vói người khác giới, sẽ có đào hoa đeo bám, có quan hệ thân mật, cũng sẽ gặp phiền phức, rắc rối, khó xử.";
                    $luanTatAch .= "\r\nGene di truyền không tốt, dễ bị bệnh tật tiềm ẩn bẩm sinh.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .=  "\r\n ít bạn bè tri kỉ, bạn bè có. duyên phận bạc với bạn, sẽ vì bạn bè mà bị tổn thương hoặc tổn thất." ;
                    $luanPhuThe .= "\r\n Vợ chồng gần nhau ít mà xa nhau nhiều, dễ xảy ra tình trạng li hôn." .
                    "\r\n Nếu có đào hoa sẽ không giữ lại, chi là duyên nhất thời, không lâu dài.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Nô Bộc"){
                    $luanQuanLoc .= "\r\n Bạn bè không giúp đỡ cho sự nghiệp của bạn; đi làm hường lương khó thăng tiến, nếu sáng lập cơ nghiệp dễ thuê nhầm người không có năng lực." .
                    "\r\n Nếu Làm ăn kinh doanh sẽ có đấu đá ngầm, chọc tức hoặc gây tổn thất cho bạn, sẽ rất khó xử." .
                    "\r\n Làm công chức thì quan trường không thuận lợi." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có sức khỏe kém, dễ mắc bệnh nặng";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè thường đến nhà bạn để tán gẫu, bàn luận những chuyện vô dụng, không có lợi cho bạn, mà còn gây khó xử." .
                    "\r\n Hợp tác bất lợi, nếu bạn bè có chuyện mượn tiền, sẽ một đi không trở lại." ;
                    $luanPhuThe .= "\r\n Rất có duyên vói người khác giới, nếu có đào hoa sẽ khó cắt đứt, có quan hệ tình dục. Giao du với người khác giới khá xem trọng quan hệ nhục dục." .
                    "\r\n Lúc sinh đẻ, phải lưu ý sức khỏe của đứa bé";
                    if(kiemTraTuHoaPhai($laSoData,"Nô Bộc". "Hóa Lộc", "Chính cung") && $gt == "false"){
                        $luanTatAch .= " \r\n  Dễ mắc bệnh phụ khoa";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .= "\r\n Bạn bè sẽ làm bạn hao tốn tiền bạc, tiêu xài tiền của bạn." ;
                    $luanCungMenh .= "\r\n Lúc còn nhỏ gia cảnh không được tốt, phúc trạch kém, dễ bị tai nạn" ;
                    $luanPhuThe .= "\r\n Người phối ngẫu dễ mắc bệnh khó chữa, khá vất vả, công việc hay sự nghiệp đều không thuận lợi.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Nô Bộc"){
                    $luanNoBoc .=  "\r\n Bạn bè thường hay làm phiền bạn, đã không có lợi mà còn gây tốn hại cho bạn." .
                    "\r\n Không nên hợp tác hoặc đứng ra bảo lãnh bạn bè; cho vay lấy lãi sẽ dễ bị phiền lụy." ;
                    $luanQuanLoc .= "\r\n Không nên đầu cơ hoặc đầu tư, cũng không thích hợp làm việc trong các tổ chức công hoặc tư nhân, rất khó thăng tiến." +
                    $luanPhuMau .= "\r\n Công việc hay sự nghiệp của cha mẹ không thuận lợi.";
                }
                // hóa lộc - quan lộc
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Quan Lộc"){
                    $luanCungMenh .= "\r\n Bạn là người Có hứng thú với nhiều lãnh vực, không thể chuyên nhất, học hành không chuyên tâm, ham vui; không thích cuộc sống đơn điệu khô khan, vì vậy thường thường học nhiều mà không chuyên." .
                    "\r\n Là người xừ sự rất vững vàng, mặt nào cũng thấu đáo, không xúc phạm hay làm phiền lòng người khác, vì vậy bạn đến đâu cũng được đón tiếp, quan hệ xã hội rất tốt." .
                    "\r\n Rất biết làm ăn, đi làm việc cho công ti sẽ hưng vượng hơn so với tự tạo dựng cơ nghiệp" .
                    "\r\n Nếu tự sáng lập cơ nghiệp, có thể được người phối ngẫu giúp đỡ, dễ được quý nhân giúp đỡ, công việc hay sự nghiệp có thể mang lại tiền bạc, tự kiếm tiền tự tiêu xài." .
                    "\r\n Vợ chồng khá hợp nhau";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Bạn là người thông minh, tài cán, có sở trường chuyên môn, có duyên với người chung quanh, sự nghiệp ổn định, có thể kiếm tiền, có khả năng làm việc độc lập, sống tự lập, sáng lập cơ nghiệp có thành tựu.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Về công việc hay sự nghiệp, có thể được anh chị em và bạn bè giúp đỡ." .
                    "\r\n Dùng tiền nhờ người khác giới thiệu để có chức vị hoặc thăng tiến.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Hoàn cảnh sự nghiệp khá tốt đẹp, có không gian để phát triển, có thể được người phối ngẫu tương trợ, phát triển theo nhiều hướng, có thể mang tài phú đến cho gia tộc." .
                    "\r\n Trong công việc hay sự nghiệp, quý nhân phần nhiều là người khác giới. Rất có duyên với người khác giới, dễ gặp đào hoa ở bên ngoài.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Bạn Có thể hợp tác làm ăn với người khác, phát triển khá tốt." ;
                    $luanThienDi .= "\r\n Đi xa dễ kiếm tiền, có thể làm ăn kinh doanh ngành giải trí hoặc bất động sản, có thế tự sáng lập cơ nghiệp, mở tiệm.";
                    $luanPhuThe .= "\r\n Cũng có duyên với người khác giới, dễ phát sinh tình cảm bên ngoài";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Quan Lộc"){
                    $luanTaiBach .= "\r\n Bạn là người có đầu óc, tiền kiếm được sẽ mang ra tái đầu tư để kiếm thêm tiền" .
                    "\r\n Dựa vào bản thân để kiếm tiền, công việc hay sự nghiệp kiếm được tiền, nếu đi làm sẽ hưởng lương cao.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Trong cả cuộc đời Công việc hay sự nghiệp gặp nhiều cơ hội tốt, thường được quý nhân đề bạt, nâng đỡ, thăng tiến nhanh." .
                    "\r\n Làm việc bận rộn, có cơ hội phát triển theo nhiều hướng, thường được học tập những kiến thức chuyên môn mới";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp có thể phát triển ở phương xa, nên làm việc ở nước ngoài; ra bên ngoài có thể đắc ý, được nhiều quý nhân tương trợ, thích hợp mậu dịch, buôn bán." .
                    "\r\n Tự lập kiếm tiền, có thể người phối ngẫu cũng thành tựu tài phú, người phối ngẫu cũng sẽ đầu tư vào sự nghiệp của bạn." ;
                    $luanPhuThe .= "\r\n Rất có duyên với người chung quanh, nhất là người khác giới.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Bạn bè hoặc nhân viên có thể giúp bạn kiếm tiền, công việc thuận lợi." .
                    "\r\n Có thể hợp tác làm ăn với người khác; sống hòa hợp với đồng nghiệp." .
                    "\r\n Dùng tiền nhờ người ta giới thiệu để được thăng quan tiến chức.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Bạn có khuynh hướng Sẽ mua tậu bất động sản, tích lũy tiền được, có thể làm ăn kinh doanh liên quan đến bất động sản, cũng có thể mở tiệm, kinh doanh tại nhà, kết hợp cơ sở doanh nghiệp với nhà ở thành một chỗ.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Công việc hay sự nghiệp có cơ hội kiếm được nhiều tiền, có khả năng làm việc độc lập; kiếm được tiền sẽ hưởng thụ." .
                    "\r\n Đầu tư vào sự nghiệp của người phối ngẫu, hoặc cùng làm ăn với người phối ngẫu.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp gặp cơ hội tốt, có thể được trường bối, quý nhân đề bạt, nâng đỡ, dễ thăng tiến, chức vị cao, có tài năng, học giỏi." .
                    "\r\n Làm công chức có thể thăng tiến, Về công việc, có thể được cha mẹ quan tâm, hoặc có thể làm những công việc về văn thư, hoặc làm cho cơ quan công.";
                }
                // hóa quyền - quan lộc
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Quan Lộc"){
                    $luanCungMenh .= "\r\n Bạn là người Xem trọng sự nghiệp, có năng lực sáng lập cơ nghiệp, có lực xung kích, dễ được người ta đề bạt, nâng đỡ, dễ thăng tiến." .
                    "\r\n Thích nắm quyền lực, cạnh tranh với người khác dễ xảy ra chuyện phiền phức, rắc rối." .
                    "\r\n Có lúc làm việc theo kiểu đầu voi đuôi chuột, trước nóng sau nguội; có lúc chỉ biết xông tới, không có các biện pháp hỗ trợ theo sau hoặc không cách nào kết hợp với người khác, làm cho vấn đề thêm phức tạp." ;
                    $luanPhuThe .= "\r\n Hay tranh chấp ý kiến với người phối ngẫu, người phối ngẫu sẽ can dự vào công việc hay sự nghiệp của bạn.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Quan Lộc"){
                    $luanCungMenh .= "\r\n Bạn là người Sáng suốt, tài cán, phản úng lanh lẹ, có thế phát triển sự nghiệp từ nhỏ thành lớn." .
                    "\r\n Xem trọng sự nghiệp, lao tâm lao lực, có thể sáng lập cơ nghiệp, nắm quyền, được người ta kính trọng, có thành tựu." .
                    "\r\n Có năng lực làm việc, không ngừng tiến bộ, bận rộn vì sự nghiệp.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Quan Lộc"){
                    $luanHuynhDe .= "\r\n Anh chị em của bạn giao du với người bên ngoài gặp nhiều cạnh tranh, dễ có thị phi." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp có lực cạnh tranh khá mạnh, có cơ hội mớ rộng hoặc thăng quan." .
                    "\r\n Tài năng được khẳng định, nhờ người trung gian giới thiệu mà có việc làm hoặc được thăng tiến." .
                    "\r\n Kiếm được tiền trong sự nghiệp mang ra chi dụng trong gia đình." .
                    "\r\n Đầu tư hợp tác với người khác có thể được nắm thực quyền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Sự nghiệp có phát triển, sẽ có đấu đá cạnh tranh với người khác, dễ gặp phiền phức, rắc rối, cũng dễ bị tiểu nhân gây trở ngại." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có giúp đỡ cho sự nghiệp của bạn, sẽ can dự vào sự nghiệp của bạn, nắm thực quyền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Quan Lộc"){
                    $luanTuTuc .= "\r\n Con cái có tài năng, cá tính mạnh, dễ bị thương bất ngờ." ;
                    $luanQuanLoc .= "\r\n Bạn Có thể hợp tác với người khác, có lực cạnh tranh, sự nghiệp sẽ mờ rộng, có thể phát triển ra nơi khác.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Quan Lộc"){
                    $luanCungMenh .= "\r\n Là người gánh trách nhiệm trong công việc, khá bận rộn, gặp nhiều cạnh tranh." .
                    "\r\n Bạn là người có đầu óc, tiền kiếm được sẽ mang ra tái đầu tư để kiếm thêm tiền";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Quan Lộc"){
                    $luanCungMenh .= "\r\n Bạn Rất có tinh thần trách nhiệm, cũng rất vất vả, đủ sức chịu đựng, có thể vì làm việc quá sức mà ảnh hường đến sức khỏe, dễ bị đau lưng." ;
                    $luanThienDi .= "\r\n Ra bên ngoài dễ có hành vi đào hoa, hay xung động dục tình." ;
                    $luanQuanLoc .= "\r\n Nếu mở công ti thường có xu hướng mở quy mô lớn hoặc làm việc ở cơ sở doanh nghiệp có quy mô lớn ";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Thích hợp với công tác ngoại vụ, nên phát triển sự nghiệp ra ngoài, có thể khai phá thị trường nước ngoài, có lực cạnh tranh, có cơ hội kiếm nhiều tiền." .
                    "\r\n Nếu ở quê hương sẽ khó có cơ hội phát triển sự nghiệp.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Trong công việc, cấp trên nghiêm khắc mà lại hay thay đổi ý kiến, rất khó phục tùng, dễ xảy ra tranh chấp." .
                    "\r\n Nhờ người quen biết giới thiệu để có việc làm hoặc được thăng tiến." .
                    "\r\n Đầu tư vào sự nghiệp do bạn bè nắm quyền, sẽ kiếm được tiền." .
                    "\r\n Công việc hay sự nghiệp sẽ có thành tựu cao hơn anh chị em trong nhà.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp có thể được dòng họ giúp đỡ, có thể phát triến ở quê nhà." .
                    "\r\n Có thể làm những công việc liên quan đến kinh doanh bất động sàn; có tham vọng lớn, kiếm được tiền sẽ mang ra tái đầu tư." .
                    "\r\n Có thể mờ tiệm làm ăn tại nhà.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Đầu tư sự nghiệp khá phô trương, xem trọng phong cách, có tác phong ở thế mạnh." .
                    "\r\n Nếu đi làm hưởng lương, là người rất có năng lực, thăng tiến trong công việc tốt." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu là người có năng lực, gặp nhiều cạnh tranh trong công việc hay sự nghiệp, có thể nắm quyền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Bạn là người Thông minh, có cố gắng, có tài năng, lúc còn đi học đã có thành tích tốt, lúc đi làm thì quyền cao chức trọng, thăng tiến nhanh, có thể làm quan lớn." .
                    "\r\n Sự nghiệp thành tựu là nhờ làm việc chăm chỉ, hại ít mà lợi nhiều, khá vất vả." ;
                    $luanCungMenh .= "\r\n Bạn Là người ưa tranh cãi với người khác.";
                }
                // Hóa Khoa - quan lộc
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Bạn là người Thích học tập, hiểu biết rộng, thành tích tốt." .
                    "\r\n Công việc nhờ người khác giới thiệu mà được, thuận lợi bình ổn." .
                    "\r\n Nơi đi làm (công ti...) khá ổn định, nhưng sẽ không phát triển thành quy mô lớn." .
                    "\r\n Làm việc có phong độ, tính tình ôn hòa, lễ độ; trong công việc dễ được cấp trên, trường bối hoặc quý nhân xem trọng, đề bạt, nâng đỡ." .
                    "\r\n Ít bị lừa gạt khi hợp tác làm ăn với người khác, vốn liếng xoay chuyển cũng thuận lợi";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Quan Lộc"){
                    $luanThienDi .=  "\r\n Bạn Rất có duyên với người khác giới, có nhiều tri ki khác giới, giao du về phương diện tinh thần.";
                    $luanQuanLoc .= "\r\n Thích hợp đi làm hưởng lương, làm việc có kế hoạch; nếu sáng lập cơ nghiệp, sự nghiệp tuy bình ổn, nhưng danh tiếng lớn hơn lợi ích, có quy mô làm ăn không lớn." .
                    "\r\n Công việc hay sự nghiệp đều bình ổn thuận lợi, dễ có danh vọng.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Công việc hay sự nghiệp của bạn đều bình ổn, đi làm cho công ti có chế độ đãi ngộ hợp lí, ít xảy ra tranh luận." .
                    "\r\n Về công việc, có thể được anh chị em giúp đỡ, ở bên ngoài gặp nhiều quý nhân." .
                    "\r\n Nhờ người khác giới thiệu mà có việc làm.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Bạn Nên đi làm hưởng lương, có quý nhân giúp đỡ, đề bạt, nâng đỡ." .
                    "\r\n Công việc hay sự nghiệp đều bình ổn, nếu sáng lập cơ nghiệp thì danh sẽ lớn hơn lợi, quy mô không lớn, lợi ích không nhiều." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có tài năng, có tiếng ở bên ngoài.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Quan Lộc"){
                    $luanTuTuc .= "\r\n Con cái có khí chất, có tài năng, cơ thể khỏe mạnh, ít bệnh tật." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều bình ổn thuận lợi có thanh danh, ở bên ngoài phần nhiều đều được quý nhân tương trợ." .
                    "\r\n Nếu có hợp tác làm ăn, sẽ bình ổn, nhưng quy mô không lớn, lợi nhuận ít, nhưng có tiếng tăm." ;
                    $luanThienDi .= "\r\n Rất có duyên với người khác giới, giao du với nhiều bạn bè khác giới, thiên về phương diện tinh thần.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Đi làm hưởng lương, tiền bạc bình ổn, ít có sóng gió, thu nhập ít nhưng đều đặn." .
                    "\r\n Đầu tư sáng lập cơ nghiệp làm ăn, giỏi tính toán giá thành, vốn ít, lời ít, chi cầu có tiếng; sẽ kiếm được tiền, lợi nhuận không nhiều, nhưng bình ổn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Cơ sở doanh nghiệp nơi làm việc có quy mô không lớn, nhưng rất ngăn nắp, đi làm thoải mái, vui vẻ; công việc hay sự nghiệp đều bình ổn thuận lợi." ;
                    $luanTatAch .= "\r\n Cơ thể khỏe mạnh, làm việc có kế hoạch, rất có tài năng mà không phô trương; nếu có nạn tai bệnh tật, dễ gặp thầy thuốc giỏi,hoặc được quý nhân tương trợ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Có thể phát triển sự nghiệp ở phương xa, dễ được quý nhằn tương trợ, có thể nổi tiếng, nhưng danh lớn hơn lợi; nên kinh doanh buôn bán, mậu dịch, kiếm tiền môi giới trung gian." .
                    "\r\n Đi làm hường lương có thể phát huy tài chuyên môn, dễ có danh vọng.";
                    
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Quan Lộc"){
                    $luanNoBoc .= "\r\n Có thể nhờ bạn bè giới thiệu mà có việc làm." .
                    "\r\n Quan hệ với đồng nghiệp khá tốt, người hợp tác, phần nhiều đều có giúp đỡ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Thu nhập bình ổn, công việc thuận lợi và ổn định, tài phú có thể tích lũy dần dần, phần nhiều được dòng họ giúp đỡ." .
                    "\r\n Có thể mua tậu nhà cửa theo phương thức trả góp, có thể làm môi giới bất động sản để kiếm hoa hồng/ hoặc làm nghề liên quan đến kinh doanh bất động sản.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Quan Lộc"){
                    $luanTaiBach .= "\r\n Nguồn tiền bình ổn thuận lợi, không dao động lớn, có kế hoạch điều chuyển vốn liếng rõ ràng, không lo bị lãng phí." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp phù hợp với hứng thú, có thể vận dụng được sở học." ;
                    $luanPhuThe .= "\r\n Vợ chồng đều thích hợp đi làm hưởng lương hoặc theo ngành văn hóa giáo dục, giúp đỡ lẫn nhau.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Quan Lộc"){
                    $luanPhuMau .=  "\r\n Công việc hay sự nghiệp có thể được cha mẹ, trưởng bối quan tâm giúp đỡ, đề bạt, nâng đỡ; việc học thuận lợi, nhậm chức bình ổn; đại đa số đều làm công việc về văn, trong cơ cấu lón hoặc cơ cấu công, dễ thăng tiến, tài năng có chỗ để phát huy." .
                    "\r\n Cha mẹ là người sáng suốt, có lí lẽ, gia giáo, thỏa mãn với tổ nghiệp.";
                }

                // Hóa Kỵ - quan lộc
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều không ổn định, không có bối cành tốt, nơi làm việc (công ti...) dễ bị phá sản, đóng cửa; thường hay thay đổi công việc." .
                    "\r\n khó kiếm tiền do tầm nhìn chưa được xa, không có thành tựu sự nghiệp lớn, cũng khó sáng lập cơ nghiệp, khó giữ được thành quà, thích hợp đi làm hưởng lương." .
                    "\r\n Trong công việc, dễ gặp tiểu nhân, chuốc thị phi." .
                    "\r\n Thời kì còn đi học hành, chi học qua loa hời hợt, dễ nghỉ học hoặc chuyển trường, thay đổi nhiều trường." .
                    "\r\n Công việc hay sự nghiệp không được thuận lợi, ảnh hường đốn cuộc sống tình cảm hôn nhân." .
                    "\r\n Nếu muốn đầu tư, chi thích hợp đầu tư ngắn hạn, thu hồi vốn nhanh.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Quan Lộc"){
                    $luanCungMenh .= "\r\n Bạn Là người Khá bảo thủ, không cầu tiến bộ, tham vọng không lớn, không có hoài bão." .
                    "\r\n Công việc hay sự nghiệp đều không được thuận lợi, vất vả mà thành quả ít, thường có ý nghĩ thay đổi công việc làm." .
                    "\r\n Tiết kiệm, tự kiếm tiền tự tiêu xài, khí độ hẹp hòi, không nên làm chủ sáng lập cơ nghiệp; nếu hoàn cảnh bức bách phải sáng lập cơ nghiệp, không do ý muốn, thì nên làm ăn nhỏ sẽ ổn định hơn." .
                    "\r\n Dù có tiền trong tay cũng than túng thiếu, người khác cũng khó mượn tiền bạn.";
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều không thuận lợi, sẽ xảy ra tình trạng đứt đoạn." ;
                    $luanTaiBach .= "\r\n Tài chính gia đình thu không bằng chi, công việc làm gặp nhiều phiền phức, rắc rối." ;
                    $luanHuynhDe .= "\r\n Anh chị em không giúp đỡ được cho bạn, mạnh ai nấy lo.";
                    $luanNoBoc .= "\r\nDễ bị tiểu nhân hãm hại, phần nhiều dễ bị bạn bè ảnh hưởng, phát triển theo chiều hướng xấu.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n sự nghiệp có biến động thay đổi lớn, nên làm ăn buồn bán có tính luân chuyển nhanh, bằng tiền mặt, quy mô không cần lớn, thì có thế kiếm được tiền.";
                    if(kiemTraSaoTrongCungTheoDinhDanh($laSoData,"Phu Thê","Hóa Kỵ")){
                        $luanQuanLoc .= "\r\n có thể theo sự nghiệp sản xuất theo kiểu dây chuyền; mà không có tình nhân ở bên ngoài.";
                    }else{
                        $luanPhuThe .= "\r\n  hôn nhân duyên bạc, khó kết hôn, hơn nữa dễ có người thứ ba xen vào hôn nhân.";
                    }
                    $luanQuanLoc .= "\r\n Sẽ vì tình cảm hôn nhân không được thuận lợi mà ảnh hưởng đến tình hình phát triển sự nghiệp.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Công việc hay sự nghiệp không thuận lợi, nên rời quê hương đi xa để tìm hướng phát triển sẽ tốt hơn." .
                    "\r\n Không nên đầu tư bất động sản, không nên mở tiệm hoặc mờ công xưởng; nếu có hợp tác làm ăn, sẽ gặp nhiều phiền phức, rắc rối, dễ lỗ vốn." .
                    "\r\n Muốn đầu tư sự nghiệp phải bán bớt hoặc thế chấp bất động sản để xoay sở vốn liếng." .
                    "\r\n Vì công việc làm ăn, nên thường ở bên ngoài giao tiếp mở rộng mối quan hệ, dễ chuốc đào hoa phiền phức, rắc rối.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Quan Lộc"){
                    $luanTaiBach .=  "\r\n Đầu tư sáng lập cơ nghiệp, sẽ thiếu vốn, thu hồi vốn chậm, vòng vốn xoay chuyển thường bị trở ngại." .
                    "\r\n Không nên mờ rộng sự nghiệp thái quá, sẽ gặp nguy cơ về tài chính." .
                    "\r\n Nếu Đi làm hưởng lương, làm công chức, vì muốn gấp rút kiếm tiền mà đi vào con đường hung hiểm, dễ có hành vi tham ô." ;
                    $luanPhuThe .= "\r\n Quan hệ vợ chồng không được hòa hợp, thích hường thụ, ưa tiêu xài tiền, không biết tiết kiệm.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều không thuận lợi, có hiện tượng tuốt dốc; nghề nghiệp đang làm dễ rơi vào tinh trạng suy thoái, lỗi thời." .
                    "\r\n Nặng tinh thần trách nhiệm, không biết cách từ chối người khác, làm việc lại không nắm được trọng điểm, sẽ rất vất vả, dễ mang bệnh nghề nghiệp, công việc hay sự nghiệp đều có thành tích kém, vận kinh doanh không tốt, phải gượng chống đỡ." .
                    "\r\n Công việc không có cơ hội tốt, dễ phạm tiểu nhân, kiện tụng,thị phi, có nhiều biến động";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .=  "\r\n Nên đi làm hường lương, không nên sáng lập cơ nghiệp, công việc có nhiều áp lực, thường bị tắc nghẽn, gặp nguy cơ." .
                    "\r\n Nên làm công tác ngoại vụ, làm công tác nội vụ sẽ gặp nhiều thị phi." .
                    "\r\n Thường vì công việc mà phải bôn ba ờ bên ngoài, nên đi xa tim hướng phát triển sẽ thuận lợi hơn." .
                    "\r\n Công việc bận rộn, thường biến động, thu nhập ít, vận trình phần nhiều không được thuận lợi.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều khó có thành tựu, dễ bị bạn bè ảnh hưởng, bị bạn bè chơi xấu." ;
                    $luanNoBoc .= "\r\n Dễ bị bạn bè giật nợ, thành quả do vất vả khổ sở làm ra dễ bị bạn bè hoặc cấp trên đoạt mất, tiền kiếm được đều rơi vào túi người khác." ;
                    $luanTaiBach .= "\r\n Dễ phạm tiểu nhân, gặp nguy cơ về tài chính, tài chính gia đình bị tổn hại." ;
                    $luanQuanLoc .= "\r\n Đi làm hưởng lương, khó hòa hợp với cấp trên, tình trạng nơi làm việc (công ti...) không được tốt cho lắm.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Không nên hợp tác làm ăn với người khác, nên làm ăn bằng tiền mặt." .
                    "\r\n Bạn có thể Mở tiệm tại nhà, hoặc bạn biến phòng khách thành phòng làm việc." .
                    "\r\n Có thể làm môi giới mua bán bất động sản để hưởng hoa hồng; muốn mua bán bất động sản, không đủ vốn." ;
                    $luanPhuThe .= "\r\n Có vận đào hoa, nếu vướng vào sẽ gặp nhiều phiền phức rắc rối";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Quan Lộc"){
                    $luanQuanLoc .= "\r\n Đầu tư sáng lập cơ nghiệp làm ăn không đủ vốn, phải mượn tiền để xoay sở, kinh doanh không giỏi, dễ bị lỗ vốn." .
                    "\r\n Sự nghiệp không gặp cơ hội tốt, vì bận rộn làm việc mà không được hưởng phước." .
                    "\r\n Vì công việc làm ăn, phải tiêu tốn nhiều tiền cho việc thù tạc, nhưng thu về ít." ;
                    $luanPhuThe .= "\r\n Vợ chồng sống với nhau không được hòa hợp, dễ vì công việc hay tiền bạc mà xảy ra tranh cãi.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Quan Lộc"){
                    $luanCungMenh .=  "\r\n Lúc còn đi học, bạn không thích học hành, hay nghỉ học, bỏ học nửa chừng hoặc thường hay chuyên trường." .
                    "\r\n Là người không nói thực, nhưng mềm lòng, không có ý muốn hại ai." ;
                    $luanQuanLoc .= "\r\n Đi làm hưởng lương có khả năng bị giảm biên chế" .
                    "\r\n Bạn dễ phạm tiểu nhân, thị phi về văn thư giấy tờ hợp đồng và thị phi kiện tụng." .
                    "\r\n Vận xấu thì Sự nghiệp sẽ tuột dốc,Vòng vốn xoay chuyển không thuận lợi, dễ bị người ta làm khó" .
                    "\r\n Nếu làm công nhân viên chức thì khó có cơ hội thăng tiến" ;
                    $luanTatAch .= "\r\n Sức khỏe kém, giảm trí nhớ, hiệu quả công việc không được tốt." ;
                    $luanPhuMau .= "\r\n Cha mẹ có tính bảo thủ, ưa so đo tính toán, có thừa kế sản nghiệp của tổ tiên.";
                }
                // hóa lộc - điền trạch
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Điền Trạch"){
                    $luanCungMenh .= "\r\n Hoàn cảnh gia đình khá tốt, có tổ nghiệp, có tiền, cũng rất biết tiêu xài tiền, tiêu xài nhiều ít chẳng quan tâm." ;
                    $luanDienTrach .= "\r\n bạn cũng sẽ tự mua bất động sản, nhà cửa, cũng sẽ bán nhà; có thể làm nghề liên quan đến bất động sản hoặc kinh doanh tiền tệ." ;
                    $luanPhuThe .= "\r\n Có duyên với người chung quanh, cũng rất có duyên với người khác giới, tính dục mạnh, sau kết hôn có thể có tình nhân ở bên ngoài, thuộc loại đào hoa nhục dục.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .=  "\r\n Hoàn cảnh gia đình khá tốt, có thể được hưởng phước ấm của dòng họ, sản nghiệp sẽ tăng thêm." ;
                    $luanPhuThe .= "\r\n Nếu có tình nhân bên ngoài thì đối tượng là người đã từng có hôn nhân hoặc đã có con.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Cuộc sống gia đình khá sung túc, có tiền có thể giúp đỡ anh chị em, anh em sống với nhau vui vẻ, anh chị em có thể được dòng họ quan tâm chăm lo." .
                    "\r\n Có duyên với người chung quanh, sống hòa hợp với bạn bè.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Điền Trạch"){
                    $luanPhucDuc .= "\r\n Sự nghiệp phát triển có thể là nhờ có dòng họ giúp đỡ." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu quản lí tài chính trong gia đình, có thể sẽ lấy danh nghĩa của người phối ngẫu để mua tậu nhà cửa." .
                    "\r\n Rất có duyên với người khác giới, sau kết hôn vẫn còn giao du nhiều bạn bè khác giới.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Sẽ mua tậu được bất động sản" ;
                    $luanThienDi .= "\r\n Rất có duyên với người chung quanh, ra bên ngoài gặp nhiều quý nhân, thường hay ăn nhậu liên hoan, thích chạy rong bên ngoài." ;
                    $luanTuTuc .= "\r\n Con cái có duyên với người chung quanh, có thể được dòng họ bà con quan tâm chăm lo, ra bên ngoài cũng có quý nhân giúp đỡ.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Điền Trạch"){
                    $luanPhucDuc .= "\r\n Dòng họ bà con có thể giúp vốn cho bạn, cầm tiền trong nhà ra tiêu xài hoặc đầu tư sự nghiệp." .
                    "\r\n Có thể hợp tác làm ăn với người nhà.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Hoàn cảnh sống khá tốt, bụng dạ rộng rãi";
                    $luanTatAch .= "\r\n cơ thể dễ phát phì, nhưng cũng sẽ vì việc nhà nhiều mà mệt mỏi, cẩn thận phòng bệnh đường tiêu hóa.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Có thể không có tổ nghiệp, sẽ xa quê hương để tìm hướng phát triển, và tự mua bất động sản." ;
                    $luanThienDi .= "\r\n Rất có duyên với người khác giới, nếu có đào hoa bên ngoài thì đối tượng đào hoa là người đã từng có hôn nhân.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Điền Trạch"){
                    $luanThienDi .= "\r\n Có duyên với người chung quanh, giao du rộng, bạn bè có giúp đỡ." ;
                    $luanPhuThe .= "\r\n Vợ chồng tình thâm ý trọng, người phối ngẫu có thể được dòng họ bà con quan tâm chăm lo, cuộc sống được hưởng thụ nhiều.";
                    $luanTatAch .= "\r\ndễ phát phì, dễ mắc bệnh đường tiêu hóa.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .=  "\r\n Bạn có thể mờ tiệm tại nhà, làm ăn sẽ tốt hơn." .
                    "\r\n Nếu bạn đầu tư để phát triển sự nghiệp thì gia đình có thể giúp vốn";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Điền Trạch"){
                    $luanPhucDuc .=  "\r\n Có phước để hưởng, cuộc sống bình ổn";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Điền Trạch"){
                    $luanPhuMau .= "\r\n Cha mẹ tính tình rộng rãi, độ lượng; bạn có thể thừa kế tổ nghiệp, cuộc sống sung túc, có thể được gia đình quan tâm chăm lo, là người hiếu kính với cha mẹ.";
                }
                // hóa quyền - điền trạch
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Người trong nhà có nhiều ý kiến, ưa tranh quyền, không hòa hợp với nhau." ;
                    $luanThienDi .= "\r\n bạn Có duyên vói người chung quanh, xem trọng tiền bạc, tự tư tự lợi, sáng suốt tài cán, có thê làm nghề liên quan đến kĩ thuật để mưu sinh." ;
                    $luanDienTrach .= "\r\n Bạn có dự định sẽ mua bất động sản, có thể cho thuê bất động sản để sinh lời" .
                    "\r\n Lúc phân chia gia sản sẽ có chuyện tranh giành.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Dòng họ có tổ nghiệp, bạn có thể được thừa kế sản nghiệp của tổ tiên, nhưng sẽ xảy ra tranh chấp thị phi; sẽ mua thêm bất động sản, lúc xoay chuyển vốn liếng bị trở ngại, có thể dùng bất động sàn giải quyết.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Điền Trạch"){
                    $luanQuanLoc .= "\r\n Đầu tư sáng lập cơ nghiệp làm ăn, nên dựa vào sự trợ giúp cùa anh chị em." ;
                    $luanDienTrach .= "\r\n Lúc phân chia tài sàn của dòng họ, sẽ có hành vi tranh chấp tài sản";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Điền Trạch"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có năng lực, giỏi quản lí tài chính, sau kết hôn sẽ lấy danh nghĩa của người phối ngẫu để mua bẩt động sàn, tài sàn để cho người phối ngẫu quàn lí.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Điền Trạch"){
                    $luanThienDi .= "\r\n Ở bên ngoài, bạn có lực cạnh tranh; sẽ xa quê hương để tìm hướng phát triển." ;
                    $luanTuTuc .= "\r\n Con cái có tài năng, có nghề chuyên môn, ở bên ngoài được khắng định." ;
                    $luanThienDi .= "\r\n Bạn Rất có duyên với người khác giới, dễ có đào hoa quấn vào người.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Điền Trạch"){
                    $luanQuanLoc .=  "\r\n Bạn phải dựa vào bản thân để kiếm tiền" .
                    "\r\n Nếu đầu tư làm ăn, phải cầm tiền trong nhà ra đầu tư";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Điền Trạch"){
                    $luanQuanLoc .= "\r\n Bạn Phần nhiều dựa vào bản thân nỗ lực, nghề nghiệp chuyên môn và tài năng để mưu sinh, vì gia đình mà làm lụng vất vả." ;
                    $luanPhuThe .= "\r\n Cẩn trọng Dễ có đào hoa quấn vào người.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Không thòa mãn với tổ nghiệp, sẽ rời quê hương đi xa để tìm hướng phát triển, ra bên ngoài kiếm tiền, sẽ mua bất động sản.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Điền Trạch"){
                    $luanNoBoc .= "\r\n Tự giúp mình rồi người mới giúp mình, có thể phát huy sở trường của mình, được người ta xem trọng, rất có lực cạnh tranh." .
                    "\r\n Giao du nhiêu bạn bè, được bạn bè tôn trọng.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Điền Trạch"){
                    $luanQuanLoc .= "\r\n Phần nhiều dựa vào nghề chuyên môn và tài năng để mưu sinh, khá vất vả, dòng họ bà con có thể giúp vốn sáng lập cơ nghiệp." ;
                    $luanNoBoc .= "\r\n Phần nhiều sẽ được bạn bè khác giới giúp đỡ, khiến đời sống tinh cảm hoặc hôn nhân xảy ra nhiều chuyện phiền phức, rắc rối.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Điền Trạch"){
                    $luanTaiBach .=  "\r\n Sinh hoạt gia đình không tiết kiệm, tiêu xài lớn, khá phô trương." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu vì sự nghiệp mà rất bận rộn, thường phải giao tế thù tạc để tranh thủ tình cảm đối tác làm ăn.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Điền Trạch"){
                    $luanPhuMau .=  "\r\n Cha mẹ rất chủ quan , ưa ra oai, uy nghiêm, khó chấp nhận lời nói nhẹ nhàng lịch sự của người khác." ;
                    $luanDienTrach .= "\r\n Có tiền Sẽ không ngừng đầu tư vào bất động sản.";
                }
                // Hóa Khoa - điền trạch
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Điền Trạch"){
                    $luanTuTuc .= "\r\n Gia đình có giáo dục tốt, có bầu không khí thư hương, con cái diện mạo thanh tú, thông tuệ, nhu thuận, có khí chất văn nghệ." ;
                    $luanTaiBach .= "\r\n Có năng lực quản lí tài chính, phần nhiều đều có kế hoạch rõ ràng về tài chính, lúc cần tiết kiệm thì tiết kiệm, lúc cần tiêu xài thì tiêu xài, cách hường thụ thanh nhã, không lãng phí." ;
                    $luanDienTrach .= "\r\n Có thể có bất động sản, do người khác giúp đỡ mà có được, mua nhà không cầu lớn hoặc sang trọng, đủ ở là thỏa mãn." ;
                    $luanThienDi .= "\r\n Rất có duyên với người khác giới, giao du nhiều bạn bè khác giới, nhưng không dính dáng nhục dục. ";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .=  "\r\n Gia đình có giáo dục tốt, cuộc sống bình yên vui vẻ; bạn được gia đình quan tâm, thỏa mãn với tổ nghiệp." ;
                    $luanTaiBach .= "\r\n Lúc thiếu vốn liếng xoay sở, sẽ lấy bất động sản thế chấp để vay tiền.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Điền Trạch"){
                    $luanHuynhDe .=  "\r\n Trong anh chị em có người làm việc trong ngành văn hóa giáo dục." ;
                    $luanDienTrach .= "\r\n Cuộc sống gia đình bình yên, ít tranh chấp với người khác." ;
                    $luanTaiBach .= "\r\n Sinh kế gia đình bình ổn thuận lợi, có thể cân đối thu chi, tuy không nhiều, nhưng không lo thiếu." ;
                    $luanThienDi .= "\r\n Rất có duyên với người chung quanh, ra bên ngoài có người giúp đỡ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Điền Trạch"){
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với người chung quanh, lo liệu việc nhà có bài bản, quan hệ gia đình rất tốt, tài vụ do người phối ngẫu quản lí, thường có kế hoạch rõ ràng, không lãng phí." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều được gia đình và người phối ngẫu giúp đỡ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Gia đình khá già, gia phong tốt đẹp, cuộc sống bình yên, ít sóng gió, gặp nạn tai có thể được quý nhân giúp đỡ." ;
                    $luanTuTuc .= "\r\n Con cái thông minh, dễ thương, có khí chất, có thanh danh ở bôn ngoài.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Điền Trạch"){
                    $luanTaiBach .=  "\r\n Tài chính của gia đình bạn có nhiều giúp đỡ, sẽ có tình hình điều chuyển qua lại về tiền bạc." ;
                    $luanThienDi .= "\r\n Rất có duyên với người khác giới, thái độ giao du với người khác giới hiền hòa, rộng rãi, nhưng không tùy tiện.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .="\r\n Sinh hoạt gia đình có quy luật, cơ thể khỏe mạnh bình an; nếu gặp nạn tai, dễ được quý nhân giúp đỡ.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Điền Trạch"){
                    $luanQuanLoc .= "\r\n Gia đình có thanh danh ỡ bên ngoài, bạn ra ngoài dễ được quý nhân giúp đỡ, ít nạn tai." ;
                    $luanQuanLoc .= "\r\n Sẽ có tình hình xa quê hương để tìm hướng phát triển, không thỏa mãn với tổ nghiệp, ở bên ngoài cũng được gia đình giúp đỡ.";
                    
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .= "\r\n Gia đình của bạn rất thân thiết với bạn bè của bạn." +
                    "\r\n bạn thường xuyên liên lạc với bạn bè; thích điện thoại, gửu email... \"tán dóc\" cả ngày với bạn bè.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Điền Trạch"){
                    $luanQuanLoc .= "\r\n Thích hợp làm những công việc cần động não hoặc có tài nghệ, công việc hay sự nghiệp đều bình ổn, nhẹ nhàng và vui vẻ; gia đình giúp đỡ nhiều." .
                    "\r\n Thu nhập ổn định, ít nhưng đều đặn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Điền Trạch"){
                    $luanPhucDuc .= "\r\n Có nhiều phúc ấm, phùng hung hóa cát, biết trân quý đồng tiền, trân quý hạnh phúc." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu rất có duyên với người chung quanh, sức khỏe tốt, chú trọng việc điều chỉnh đời sống tinh thần." .
                    "\r\n Công việc hay sự nghiệp của người phối ngẫu đều bình ổn, thuận lợi, nhẹ nhàng; hòa hợp với đồng sự.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Điền Trạch"){
                    $luanPhuMau .=  "\r\n Cha mẹ là người sáng suốt, có lí lẽ, hiền hòa, không so đo tính toán với người khác, sinh hoạt gia đình vui vẻ, rất quan tâm chăm lo cho bạn.";
                }

                // Hóa Kỵ - điền trạch
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Điền Trạch"){
                    $luanPhucDuc .= "\r\n Bạn Sinh ra trong gia đình bận rộn lo chuyện sinh kế, lúc còn nhỏ gia giáo không nghiêm, duyên phận bạc với dòng họ bà con." ;
                    $luanDienTrach .= "\r\n Nhà ở lộn xộn mà nhỏ, thường thay đổi nơi cư trú; tự mua nhà rất khó khăn." ;
                    $luanTaiBach .= "\r\n Không có quan niệm quản lí tài chính, tiêu xài lãng phí, không biết tiết chế, thường tiêu xài hết tiền." ;
                    $luanPhuThe .= "\r\n Không muốn mấy chuyện lập gia đình, có kết hôn hay không, cũng chẳng quan trọng." ;
                    $luanThienDi .= "\r\n Rất có duyên với người khác giới, dễ có đào hoa nhung không giữ lại." ;
                    $luanQuanLoc .= "\r\n Có thể làm nghề mua bán bất động sản." ;
                    $luanDienTrach .= "\r\n Lần đầu mua nhà dễ xảy ra vấn đề, trước năm 35 tuổi không nên mua nhà." .
                    "\r\n Không quen ở nhà, thường bôn ba ở bên ngoài, dễ vì tai nạn bất ngờ mà phá tài" ;
                    $luanPhuThe .= "\r\n Sau kết hôn vẫn dễ có tình nhân ở bên ngoài, đối tượng là người độc thân đã li hôn. ";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Điền Trạch"){
                    $luanCungMenh .= "\r\n Tính tình bảo thủ, quản lí tài chính theo nguyên tắc của mình, bản thân tiết kiệm, cũng không thích chiếm tiện nghi của người khác." .
                    "\r\n Tuy không muốn nhưng thường phải bôn ba ở bên ngoài." ;
                    $luanDienTrach .= "\r\n Lần đầu mua nhà dễ bị thua thiệt." ;
                    $luanThienDi .= "\r\n Có thể giao du với bạn bè khác giới lâu dài, đối tượng đào hơn thường là người đã li hôn nhung không có con." ;
                    $luanTaiBach .= "\r\n Tiền bạc thường có hiện tượng hao hụt, tổn thất, không giữ được.";
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Điền Trạch"){
                    $luanTaiBach .= "\r\n Không nên qua lại tiền bạc với anh chị em hay bạn bè, dễ bị tổn thất, phiền phức, rắc rối." .
                    "\r\n Sinh kế gia đình thường bị tình trạng thu không bằng chi." .
                    "\r\n Đến đại hạn thứ hai, thường sẽ xa quê hương để tìm hướng phát triển.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Điền Trạch"){
                    $luanQuanLoc .= "\r\n Đầu tư sáng lập cơ nghiệp làm ăn, vận kinh doanh không thuận lợi, dễ bị lỗ vốn; tốt nhất là nên đi làm hưởng lương." ;
                    $luanPhuThe .= "\r\n Rất có duyên với người khác giới, nhiều đào hoa, dễ có tình huống sống chung như vợ chồng, hôn nhân không có danh phận.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Điền Trạch"){
                    $luanPhucDuc .=  "\r\n Gia vận không được tốt, lúc còn nhỏ cuộc sống không ổn định, thích chạy rong bên ngoài, gia đình thiếu quan tâm bạn." ;
                    $luanDienTrach .= "\r\n Thích tiêu xài tiền, cầm tiền trong nhà ra ngoài tiêu xài, không có ý định mua nhà cửa. Sẽ thường dời nhà." ;
                    $luanTuTuc .= "\r\n Rất quan tâm chăm sóc con cái, tuy nhiên nhà thường ít con" ;
                    $luanQuanLoc .= "\r\n Nếu kinh doanh làm ăn thì không nên là người đứng mũi chịu sào" ;
                    $luanThienDi .= "\r\n Thường hay ăn uống, hay liên hoan mở rộng quan hệ xã hội ở nơi phong hoa tuyết nguyệt" ;
                    $luanPhuThe .= "\r\n Nhiều mối tình, nhiều người để ý nhưng dễ có tình trạng sống chung như vợ chồng, nếu kết hôn \"người ấy\" sẽ mang con đến! ";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Điền Trạch"){
                    $luanTaiBach .=  "\r\n Tài vận không tốt, trong nhà thường túng thiếu tiền bạc, chi nhiều hơn thu." .
                    "\r\n Nên đi làm hưởng lương; không thích hợp đầu tư sáng lập cơ nghiệp, thường bị hụt vốn, sẽ mang nhà ra thế chấp để vay tiền hoặc bán nhà để xoay sở.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .=  "\r\n Số mệnh dễ rời xa cố hương, định cư ở tha hương." .
                    "\r\n Trong gia đình xảy ra nhiều chuyện thị phi rắc rối, có thể sẽ không ờ chung với cha mẹ." ;
                    $luanTatAch .= "\r\n Cẩn trọng Dễ bị thương tật vì nạn tai bất ngờ.";
                    if($gt == "false"){
                        $luanThienDi .= " \r\n Hạn chế đi chơi khuya hay giao du với bạn bè bất hảo, có thể bị kẻ xấu xâm phạm, khiến thân tâm bị tổn thương.";
                    }
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Điền Trạch"){
                    $luanDienTrach .=  "\r\n Thường hay dời nhà, sẽ rời xa cố hương, định cư ở tha hương để mưu sinh; ở bên ngoài dễ có tai ách, nên lưu ý phòng sự cố giao thông." ;
                    $luanPhucDuc .= "\r\n Xem trọng quan niệm gia đình, nhưng duyên phận bạc với dòng họ bà con." ;
                    $luanDienTrach .= "\r\n Không có duyên với bất động sản; mua nhà lần đầu bị thiếu tiền." ;
                    $luanPhuThe .= "\r\n Chậm kết hôn, giao du với bạn bè khác giới không được lâu, ít con" ;
                    $luanTaiBach .= "\r\n Tài vận không tốt, tiền bạc dễ bị hao tốn, tổn thất.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Điền Trạch"){
                    $luanNoBoc .= "\r\n Không hòa hợp với bạn bè, ít bạn bè mà nhiều phiền phức, dễ vì bạn bè mà tổn thất tiền bạc." ;
                    $luanHuynhDe .= "\r\n Lớn lên các anh chị em trong nhà đều ra ở riêng";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Điền Trạch"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp đều không thuận lợi, sáng lập cơ nghiệp sẽ bị thiếu vốn, người nhà sẽ chi viện, nhưng dễ vì thua lỗ mà sinh phiền phức, rắc rối." ;
                    $luanDienTrach .= "\r\n Nếu mua nhà, không nên lấy danh nghĩa của người phối ngẫu để đăng kí." .
                    "\r\n Người phối ngẫu có mối quan hệ xa cách với gia đình của bạn.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Điền Trạch"){
                    $luanTaiBach .= "\r\n Tài Vận không tốt, thu nhập không ổn định, trong nhà thường không có tiền, cuộc sống không ổn định, không có phước để hưởng." .
                    "\r\n Đầu tư làm ăn, vận kinh doanh không được tốt, sẽ lỗ vốn; nên đi làm hưởng lương." .
                    "\r\n Thường hay lãng phí tiền bạc, hoặc dễ bị tổn thất tiền bạc; khó mua nhà";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Điền Trạch"){
                    $luanThienDi .=  "\r\n Thường bôn ba, bốn phương là nhà." ;
                    $luanPhuMau .= "\r\n Sẽ ở chung với cha mẹ." ;
                    $luanTatAch .= "\r\n Dễ bị phá tướng vì tổn thương bất ngờ.";
                }
                // hóa lộc - phúc đức
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Bạn là người độ lượng, không có tâm cơ, không nhờ vả lục thân, tự kiếm tiền tự hường thụ." ;
                    $luanPhucDuc .= "\r\n Rẩt sẵn sàng chi tiền để hường thụ; phúc ấm không tệ, được thừa kế sản nghiệp, cho nên tiêu xài tiền mà không lo." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có quan hệ xã hội rất tốt, công việc thuậnlợi, kiếm được tiền sẽ cùng hưởng với bạn,khi bạn vất vả, sẽ giúp bạn kiếm tiền.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .= "\r\n Bạn Là người bụng dạ rộng rãi, độ lượng, thích hưởng thụ, có phúc ấm của tổ tiên, có tiền để tiêu xài; nhưng không nhất định là có đủ phúc phận, phúc khí cũng có lúc sẽ hết, tổ nghiệp có lúc cũng không còn." ;
                    $luanPhuThe .= "\r\n Được hưởng thụ cuộc sống vợ chồng vui vẻ, nhưng cũng ngầm chứa nguy cơ." ;
                    $luanTaiBach .= "\r\n Có cơ hội kiếm được nhiều tiền, nhẹ nhàng, vui vẻ.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .=  "\r\n Anh chị em có thể được hường phước ấm của tổ nghiệp, sinh kế gia đình được tổ nghiệp cung ứng đầy đủ." ;
                    $luanThienDi .= "\r\n Thường liên hoan ăn nhậu vui vẻ với bạn bè";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Phúc Đức"){
                    $luanPhuThe .= "\r\n Người phối ngẫu thông minh, tài cán, có sở trường chuyên môn, rất có duyên với người chung quanh, có thể làm việc độc lập, sự nghiệp ổn định, giúp đỡ nhiều cho bạn, có phúc khí, hôn nhân tốt đẹp, công việc hay sự nghiệp gặp được cơ hội tốt, phát triển thuận lợi.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .= "\r\n Dòng họ bà con có vận thế tốt, ờ bên ngoài có nhiều sự giúp đỡ." ;
                    $luanThienDi .= "\r\n Ra bên ngoài kiếm tiền vì gia đình, có thể mua thêm nhà cửa, nhưng hôn nhân dễ có người thứ ba xen vào.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Phúc Đức"){
                    $luanQuanLoc .= "\r\n Có thể bồi dưỡng tri thức cho thị hiếu sở thích của bàn thân để thành phương tiện kiếm tiền, kết hợp công việc với thị hiếu sở thích thành một." ;
                    $luanPhucDuc .= "\r\n Có phúc ấm của tổ tiên, có tổ nghiệp, nhận được sự giúp đỡ, vợ chồng có phúc cùng hưởng";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Phúc Đức"){
                    $luanTatAch .= "\r\n Được hưởng gene di truyền tốt, cơ thể khỏe mạnh, không có bệnh nặng." ;
                    $luanDienTrach .= "\r\n Tổ nghiệp hưng thịnh, nhung không nhất định sẽ giữ được." ;
                    $luanPhuThe .= "\r\n Cuộc sống Vợ chồng ít sảy ra mâu thuẫn";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Phúc Đức"){
                    $luanThienDi .= "\r\n Ở bên ngoài vui vẻ, tâm tình cởi mở, sống hòa hợp với mọi người, không so đo tính toán thị phi." ;
                    $luanPhucDuc .= "\r\n Hôn nhân yên ấm, được hưởng phúc ẩm tổ tiên";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Phúc Đức"){
                    $luanNoBoc .= "\r\n Là người độ lượng, không tính toán so đo với bạn bè." .
                    "\r\n Thích giao du ăn uống, chơi với bạn bè có chung thị hiếu, sở thích.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .=   "\r\n Có tổ nghiệp, có phúc ấm, sự nghiệp có căn cơ vững chắc, càng ngày càng mạnh hơn, phát triển theo nhiều hướng." ;
                    $luanPhuThe .= "\r\n Rất có duyên với người khác giới, sau kết hôn vẫn giao du với nhiều bạn bè khác giới.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .=  "\r\n Gia vận tốt, vui vẻ quan tâm chăm lo gia đình, tiền kiếm được phần nhiều chi dụng trong gia đình." ;
                    $luanDienTrach .= "\r\n Người phối ngẫu kinh doanh tại nhà, có thể kinh doanh làm ăn liên quan đến bất động sản, kiếm đủ tiền sẽ mua nhà.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phụ Mẫu" && $tenCung == "Phúc Đức"){
                    $luanPhuMau .= "\r\n Có phúc ấm của tổ tiên, cha mẹ được hưởng gia tài, là người không tính toán so đo, bụng dạ rộng rãi." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu gặp nhiều cơ hội tốt, nhậm chức có thể được quý nhân đề bạt, nâng đỡ, chức vị cao, sáng lập cơ nghiệp có thể thành tựu.";
                }
                // hóa quyền - phúc đức
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Tính cách của bạn Là người ưa ra oai, thích biểu hiện cái tôi, hay tranh địa vị chủ đạo, nhưng không cố ý hại ai, mà chi mưu đồ làm cho bản thân được an nhàn, có thành tựu, mà không để ý đến cảm thụ của người khác." .
                    "\r\n Xem mình là trung tâm, không chịu nghe ý kiến của người khác, cố chấp kiến giải của bàn thân, cũng không tin số mệnh." ;
                    $luanPhuThe .= "\r\n Cuộc sống hôn nhân có nhiều xung đột, không được ổn định. ";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Nặng ý thức về cái tôi, rất chủ quan, không chịu nghe lời khuyên chân thành, ưa ra oai, thích nắm quyền." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu bận rộn làm ăn, lao tâm lao lực nhưng có thành tựu.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .= "\r\n Công việc hay sự nghiệp của bạn đều gặp cơ hội tốt, thuận lợi, kiếm được nhiều tiền, sinh kế gia đình sung túc, còn được dòng họ giúp vốn." ;
                    $luanHuynhDe .= "\r\n Trong anh chị em có người thích ra oai, phách lối, ưa cạnhtranh.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Phúc Đức"){
                    $luanQuanLoc .= "\r\n bạn chịu nỗ lực vì công việc hay sự nghiệp, người phối ngẫu có giúp đỡ, sự nghiệp có thành tựu." .
                    "\r\n Tổ nghiệp hưng thịnh, quy mô lớn, có lực cạnh tranh.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .= "\r\n Bản thân cũng sẽ phát triến ra bên ngoài, đến nơi khác tìm cơ hội." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu sẽ xa quê hương sáng ỉập cơ nghiệp, có thành tựu." ;
                    $luanQuanLoc .= "\r\n Họp tác làm ăn với người khác sẽ có không gian để mở rộng." ;
                    $luanTuTuc .= "\r\n Con cái có tài năng, ở bên ngoài cạnh tranh với nhiều người, có thể được người ta tôn trọng.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Phúc Đức"){
                    $luanTaiBach .=   "\r\n Có tổ nghiệp hoặc được trưởng bối giúp đỡ, cho cơ hội kiếm tiền." .
                    "\r\n Sẽ vì hường thụ hoặc để mua chuộc cảm tình mà tiêu xài nhiều tiền cho việc mở rộng mối quan hệ, ăn uống." ;
                    $luanPhuThe .= "Người phối ngẫu có tinh thần trách nhiệm, bận rộn làm việc, có tham vọng lớn, có dục vọng kiếm thật nhiều tiên, có thể giúp bạn kiếm tiền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Phúc Đức"){
                    $luanTatAch .= "\r\n Được hưởng gene di truyền tốt, cơ thể cường tráng, công năng tính dục khỏe mạnh, hiếu sắc, không biết tiết chế." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu vất vả vì công việc mà không than oán, quá lao lực làm ảnh hưởng đến sức khỏe.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Bạn Cá tính mạnh, rất chủ quan, ở bên ngoài cạnh tranh với nhiều người, không chịu thua." ;
                    $luanPhuMau .= "\r\n Được trường bối giúp đỡ, có chỗ để phát huy." ;
                    $luanPhuThe .= "\r\n Công việc hay sự nghiệp của người phối ngẫu có nhiều cạnh tranh đấu đá, nhưng trong hôn nhân lại đóng vai trò của kẻ yếu.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Phúc Đức"){
                    $luanNoBoc .= "\r\n bạn là người cố chấp, tính tình cương cường, dễ xảy ra chuyện tranh quyền đoạt lợi với bạn bè đồng sự.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Phúc Đức"){
                    $luanQuanLoc .= "\r\n Có tinh thần trách nhiệm, xem trọng sự nghiệp, rất chủ quan, có lực xung kích, được trưởng bối giúp đỡ mà có thành tựu." ;
                    $luanPhuThe .= "\r\n Trong quan hệ hôn nhân,bạn là người chủ động, đóng vai trò của kẻ mạnh";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Phúc Đức"){
                    $luanDienTrach .=  "\r\n Có thể được hường tổ nghiệp, nhưng vẫn không thỏa mãn, sẽ tự tậu thêm sản nghiệp." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có tham vọng lớn trong sự nghiệp, tranh quyền đoạt lợi tại quê nhà.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phụ Mẫu" && $tenCung == "Phúc Đức"){
                    $luanPhuMau .=  "\r\n Cha mẹ rất chủ quan, ưa cãi lí, được tổ nghiệp giúp đỡ, không chịu nghe ý kiến của người khác, thích hưởng thụ." ;
                    $luanCungMenh .= "\r\n bạn cũng thích hường thụ, dễ vì thị hiếu mà quên ngủ quên ăn, khiến sức khỏe bị tổn thương." ;
                    $luanPhuThe .= "\r\n Hôn nhân không hạnh phúc";
                }
                // Hóa Khoa - phúc đức
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Là người có tu dưỡng tính tình, hành sự sáng suốt, có lí lẽ, không cực đoan, có phong độ, rộng lượng, có thê tự lập, có thiện tâm, thích giúp người khác, có kế hoạch kiếm tiền rõ ràng, nguồn tiền bình ổn, ít sóng gió." .
                    "\r\n Tâm trạng ổn định, thái độ nhã nhặn, có thị hiếu lành mạnh." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu làm việc có phong độ, dễ được quý nhân trợ giúp, dễ có thanh danh. " .
                    "\r\n Quan hệ hôn nhân bề ngoài có vẻ bình ổn, nhưng thực ra không êm ấm. ";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .=  "\r\n Là người có tu dưỡng tính tình, lịch sự nhã nhặn, có phong độ, có khí chất, hiền hòa, không hay so đo tính toán." ;
                    $luanPhucDuc .=  "\r\n Có phúc ấm của tổ tiên, có thể được hưởng tổ nghiệp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Phúc Đức"){
                    $luanTaiBach .=  "\r\n Dùng tiền có kế hoạch rõ ràng, sinh kế gia đình ổn định, nếu gặp lúc cần tiền gấp cũng sẽ an nhiên vượt qua.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Phúc Đức"){
                    $luanPhuThe .= "\r\n Người phối ngẫu có khí chất, có phong độ, công việc thuận lợi có danh vọng, cũng có giúp đỡ cho công việc hay sự nghiệp của bạn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Phúc Đức"){
                    $luanDienTrach .= "\r\n Ra bên ngoài kiếm tiền ít gặp sóng gió, sinh hoạt gia đình ổn định, nhà ở trang nhã thoài mái, cuộc sống hôn nhân ổn định.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Phúc Đức"){
                    $luanTaiBach .=  "\r\n Được tổ nghiệp giúp đỡ, tài chính ổn định, dùng tiền có kế hoạch tốt, không lãng phí, trong cuộc đời ít gặp sóng gió.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Phúc Đức"){
                    $luanTatAch .= "\r\n Được hưởng gene di truyền tốt, có phúc khí, cơ thể khỏe mạnh, tâm trạng yên ổn." ;
                    $luanPhuThe .= "\r\n Cuộc sống hôn nhân chú trọng ý vị tinh cảm, thiên nặng càm giác thỏa mãn vê tinh thần.";
                    $luanCungMenh .= "\r\n thường được quý nhân giải nguy, là người không hay so đo tính toán, không chuốc thị phi.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .= "\r\n Có phúc ấm tổ tiên, ra bên ngoài gặp nhiều quý nhân, bình an thuận lợi, tinh thần vui vẻ." ;
                    $luanPhuThe .= "\r\n Cuộc sống hôn nhân yên ổn.";
                    
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Phúc Đức"){
                    $luanNoBoc .= "\r\n Thích trợ giúp bạn bè, sống hòa hợp với bạn bè, đồng nghiệp.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Phúc Đức"){
                    $luanQuanLoc .= "\r\n Đối với công việc hay sự nghiệp, trước khi hành động đều có kế hoạch, không gấp gáp xông tới, mà đi từng bước, dễ được quý nhân giúp đỡ, đề bạt, cũng dễ có thanh danh.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Phúc Đức"){
                    $luanPhucDuc .= "\r\n Có thể được hường gia sản của tổ tiên, hoặc được người ta cho tiền, nhưng không nhiều." .
                    "\r\n Gia giáo tốt, có thể giữ thành quả; về tài chính, có thể cân đối";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phụ Mẫu" && $tenCung == "Phúc Đức"){
                    $luanPhuMau .=  "\r\n Cha mẹ có thế được hưởng phước ấm của tổ tiên." ;
                    $luanPhucDuc .= "\r\n Vận về già của bạn có thể được hưởng phước thanh nhàn";
                }

                // Hóa Kỵ - phúc đức
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Là mẫu người hay lo lắng thái quá, tính tình không cởi mở, hay rầu lo, ưu uất, vất vả, có khuynh hướng trầm cảm, tâm thần không yên ổn." .
                    "\r\n Là người kỹ tính, biết tính toán, không chịu nghỉ ngơi, sẽ tiêu xài nhiều tiền, không giữ tiền được." ;
                    $luanTaiBach .= "\r\n Không có vận kiếm tiền, sức khỏe kém, vận trình nhiều trắc trở"; 
                    $luanPhuThe .= "\r\ncông việc của người phối ngẫu không ổn định, vợ chồng dễ vì chuyện tiền nong mà xảy ra tranh chấp. ";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Là người kỹ tính, biết tính toán, tuy nhiên thích tranh đoạt danh lợi" .
                    "\r\n tính tình hay thay đổi, không biết hưởng phước,cho nên khá vất vả." .
                    "\r\n Tính tình không cởi mở, hay buồn rầu tư lự, cảm thấy mình có tài mà không gặp thời." .
                    "\r\n Thích hưởng thụ, không có cảm giác an toàn, thường tiêu xài tiền lãng phí.";
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Phúc Đức"){
                    $luanPhuThe .= "\r\n Vợ chồng không hòa hợp, hôn nhân khó hạnh phúc, dễ xảy ra tình trạng ờ riêng, li dị, tình cảm đô vỡ." ;
                    $luanQuanLoc .= "\r\n Khó có tổ nghiệp, sinh kế gia đình phải dựa vào bàn thân, thu nhập hay bị đứt đoạn." ;
                    $luanHuynhDe .= "\r\n Anh chị em sống với nhau không hòa hợp, khó có bạn bè tri ki." ;
                    $luanPhucDuc .= "\r\n Phần nhiều ít biết bà con dòng họ; nếu chia gia sản bạn sẽ không được nhiều";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Phúc Đức"){
                    $luanPhuThe .= "\r\n Người phối ngẫu không có hoài bão, bụng dạ hẹp hòi, hay so đo tính toán, sự nghiệp không phát triển, không ổn định, cũng không trợ giúp cho sự nghiệp của bạn. hôn nhân khó hòa hợp, kết hôn cho có mà thôi, nhưng sẽ không li hôn." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp của bạn không thuận lợi, tự kiếm tiền tự tiêu xài " ;
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Phúc Đức"){
                    $luanQuanLoc .=  "\r\n  thường bôn ba ở bên ngoài, gia vận không tốt, gặp nhiều thị phi, khó được hưởng tổ nghiệp." +
                    $luanPhucDuc .= "\r\n Trong nhà bất hòa, dễ xảy ra tình trạng tranh giành gia sản.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Phúc Đức"){
                    $luanTaiBach .=  "\r\n Xem trọng tiền bạc, nhưng không giữ được tiền, vì hưởng thụ mà lãng phí, gây ra tình trạng thấu chi, tinh thần hay thay đổi" .
                    "\r\n Nguồn tiền không tốt, kiếm tiền vất vả. Người phối ngẫu kiếm được ít tiền";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .=  "\r\n Là người có lòng dạ ngay thẳng, thiện lương, vất vả nhiều." ;
                    $luanTatAch .= "\r\n Cơ thể suy nhược, nhưng ý chí kiên định"; 
                    $luanQuanLoc .= "\r\nnên hành nghề tôn giáo hoặc sự nghiệp từ thiện"; 
                    $luanPhuThe .= "\r\nquan hệ hôn nhân không tốt, sinh hoạt vợ chồng không hòa điệu.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .=  "\r\n Là người kỹ tính, biết tính toán, tuy nhiên thích tranh đoạt danh lợi mà làm ảnh hưởng đến người khác, quan hệ xã hội không được tốt" ;
                    $luanDienTrach .= "\r\n Phong thủy có vấn đề";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Tư tường cực đoan, dễ tranh chấp với người khác, biết tính toán" ;
                    $luanNoBoc .= "\r\n Không hòa hợp với bạn bè đồng nghiệp" ;
                    $luanHuynhDe .= "\r\n Duyên phận bạc với anh chị em, anh em không gần gũi, trong nhà bất hòa, dễ xảy ra tình trạng tranh giành gia sản." ;
                    $luanTaiBach .= "\r\n Không nên cho người ta vay để lấy lãi." .
                    "\r\n Sự nghiệp của người phối ngẫu không kiếm được tiền, không đủ chi dụng trong gia đình." ;
                    $luanPhuThe .= "\r\n Quan hệ hôn nhân không hòa điệu, khó trò chuyện trao đổi với nhau.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Phúc Đức"){
                    $luanQuanLoc .= "\r\n Trong công việc hay sự nghiệp, bạn phải nỗ lực vất vả, nhưng vận khí không tốt, khó khởi sắc." ;
                    $luanPhuThe .=  "\r\n Hôn nhân không được hạnh phúc, ít sum họp, không quan tâm chăm sóc nhau, khó sống với nhau đến đầu bạc, hoặc khó có hôn nhân.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Phúc Đức"){
                    $luanCungMenh .= "\r\n Là người kỹ tính, biết tính toán" ;
                    $luanPhucDuc .= "\r\n Gia vận không thuận lợi, trong nhà khó hòa hợp, dễ xảy ra tình trạng tranh giành gia sản, tiền bạc không thuận lợi, khó kiếm tiền";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phụ Mẫu" && $tenCung == "Phúc Đức"){
                    $luanPhuMau .=  "\r\n Vận trình của cha mẹ không thuận lợi, ít phát triển, sức khỏe kém" ;
                    $luanQuanLoc .= "\r\n bạn không nên làm người bảo lãnh, dê bị hao tài; cũng không nên cho người ta vay tiền để lấy lãi." ;
                    $luanTatAch .= "\r\n Sức khỏe của bạn cũng không tốt, dễ mắc bệnh nặng. Thích nghiên cứu triết học, tôn giáo, tín ngưỡng.";
                }
                // hóa lộc - phụ mẫu
                if($sao == "Tự Hóa Lộc" && $lienQuan == "Chính cung" && $tenCung == "Phụ Mẫu"){
                    $luanCungMenh .=  "\r\n Bạn là người có khí độ lớn, có thể làm chủ hoặc ở cấp chủ quản." .
                    "\r\n Thông minh, thành tích tốt; lúc còn đi học được thầy cô thương yêu; lúc đi làm được mõi người mến." ;
                    $luanPhucDuc .= "\r\n Có phúc ấm của tổ tiên, nhưng không giữ được." ;
                    $luanPhuMau .= "\r\n Cha mẹ là người khiêm tốn, có tính độc lập, có duyên với người chung quanh, còn có khẩu tài, rộng rãi mà nhiệt tình." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu xuất thân từ gia đình khá giả. ";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Mệnh" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ rất quan tâm chăm sóc bạn, duyên phận sâu nặng." ;
                    $luanThienDi .= "\r\n Quan hệ tốt đẹp với cấp trên hay trưởng bối, thường được họ quan tâm." .
                    "\r\n Đi đòi nợ có thể thuận lợi." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có thể giúp vốn cho sự nghiệp của bạn" ;
                    $luanCungMenh .= "\r\n bạn là người thích học hành, có thành tích cao.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Huynh Đệ" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=  "\r\n Tình cảm của cha mẹ tốt đẹp, họ rất quan tâm chăm sóc anh chị em bạn, còn có thể chu cấp cho sinh kế gia đình.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phu Thê" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ hoặc trưởng bối trọ giúp nhiều cho sự nghiệp của bạn, tình thân hòa hợp" ;
                    $luanPhuThe .= "\r\n Người phối ngẫu được gia đình của mình quan tâm và giúp đỡ." .
                    "\r\n Người phối ngẫu rất có duyên với người khác giới, sau kết hôn vẫn giao du với bạn bè khác giới." .
                    "\r\n Quan hệ của người phối ngẫu với cha mẹ bạn khá tốt đẹp, thường được quan tâm chiếu cố.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tử Tức" && $tenCung == "Phụ Mẫu"){
                    $luanTuTuc .= "\r\n Con cái thông minh, rất có duyên với người chung quanh, có tài năng." ;
                    $luanPhuMau .= "\r\n Cha mẹ rất quan tâm con cái của bạn, tình cảm gia đình rất tốt." .
                    "\r\n Cha mẹ thường đi nơi khác để kiếm tiền, thích ra bên ngoài giao tiếp xã hội, quan hệ giao tế khá tốt.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tài Bạch" && $tenCung == "Phụ Mẫu"){
                    $luanNoBoc .=  "\r\n bạn và bạn bè thường tiêu xài tiền chung với nhau." ;
                    $luanPhuMau .= "\r\n Cha mẹ có tài vận tốt, kiếm tiền nhẹ nhàng, có vận kiếm tiền bất ngờ, nhưng cơ thể hai yếu.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Tật Ách" && $tenCung == "Phụ Mẫu"){
                    $luanTatAch .= "\r\n bạn bẩm sinh có thể chẩt rất tốt, cơ thể khòe mạnh, động tác nhanh nhạy." ;
                    $luanPhuMau .= "\r\n Cha mẹ rất có duyên với người chung quanh, quan hệ tốt đẹp, họ rất quan tâm bạn.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Thiên Di" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Giữa bạn và cha mẹ có tình cảm rất tốt đẹp, cha mẹ rất quan tâm bạn." ;
                    $luanCungMenh .= "\r\n Lúc nhỏ bạn học hành có thành tích tốt, tính hiếu động, thích ra ngoài, ở bên ngoài tâm tình vui vè.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Nô Bộc" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ khỏe mạnh, sống khá thọ, sự nghiệp có thành tựu." ;
                    $luanNoBoc .= "\r\n Ông chủ hoặc bạn bè có vốn liếng hùng hậu.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Quan Lộc" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ có sự nghiệp thành tựu, bạn có thể được thừa kế gia nghiệp, gia nghiệp hưng thịnh, đại phát tài lộc." ;
                    $luanPhuThe .= "\r\n Bà con dòng họ của người phối ngẫu có giúp đỡ cho sự nghiệp cún bạn.";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Điền Trạch" && $tenCung == "Phụ Mẫu"){
                    $luanPhucDuc .=  "\r\n Gia vận tốt, vui vẻ quan tâm chăm lo gia đình";
                }
                if($sao == "Hóa Lộc" && $lienQuan == "Phúc Đức" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=  "\r\n Cha mẹ tính tình cỡi mở, thích giao tế, xem trọng chuyện hường thụ, ưa thể diện, vì muốn chiếm cảm tình mà tiêu xài tiền rất rộng rãi." .
                    "\r\n Cha mẹ rất quan tâm bạn, giúp đỡ nhiều cho bạn.";
                }
                // hóa quyền - phụ mẫu
                if($sao == "Tự Hóa Quyền" && $lienQuan == "Chính cung" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ có quyền thế." ;
                    $luanQuanLoc .= "\r\n Nếu làm trong nhà nước thì bạn dễ thăng tiến trong sự nghiệp, có tài năng" .
                    "\r\n Bạn có tác phong làm việc quà quyết, tự kinh doanh làm ăn, có thể trờ thành nhân vật có tiếng tăm trên thương trường." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu có duyên với người chung quanh, có tài năng, thích nắm quyền, xem trọng tiền bạc, bà con dòng họ của người ấy nhiều thị phi, hay tranh chấp ý kiến. ";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Mệnh" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n bạn nỗ lực làm việc, có thể được cha mẹ, trưởng bối quan tâm và hỗ trợ, quá trình trưởng thành rất đắc ý." ;
                    $luanCungMenh .= "\r\n Là người ưa cãi lí, không chịu thua." ;
                    $luanTaiBach .= "\r\n Lúc bạn đi đòi nợ, phải làm mặt \"ngầu\", nhưng cũng không nhất định người ta sẽ trả đủ.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Huynh Đệ" && $tenCung == "Phụ Mẫu"){
                    $luanHuynhDe .= "\r\n Anh chị em phần nhiều là người có tài năng, tính tình cương nghị, có tác phong làm việc quả quyết." ;
                    $luanPhuMau .= "\r\n Cha mẹ dạy dỗ anh chị em bạn khá nghiêm khắc, trong công việc phần nhiều phải cạnh tranh với bạn bè đồng nghiệp." .
                    "\r\n Giữa cha mẹ thường xảy ra tranh chấp ý kiến.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phu Thê" && $tenCung == "Phụ Mẫu"){
                    $luanCungMenh .= "\r\n Bản thân bạn có tài năng, có trí tuệ, được cha mẹ hoặc trưởng bối chỉ dẫn, thêm vào đó còn được gia đình của người phối ngẫu giúp đỡ, khiến công việc hay sự nghiệp có thành tựu." ;
                    $luanPhuMau .= "\r\n Cha mẹ của bạn dạy dỗ con cái nghiêm khắc." .
                    "\r\n Sự nghiệp của cha mẹ bạn có thành tựu";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tử Tức" && $tenCung == "Phụ Mẫu"){
                    $luanTuTuc .= "\r\n Con cái của bạn có tài năng, có khả năng làm việc độc lập, thông minh, có lực xung kích." ;
                    $luanPhuMau .= "\r\n Cha mẹ của bạn dạy dỗ con cái nghiêm khắc.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tài Bạch" && $tenCung == "Phụ Mẫu"){
                    $luanTaiBach .= "\r\n bạn hợp tác với người khác bằng nghề chuyên môn của mình, có thế kiếm tiền." ;
                    $luanPhuMau .= "\r\n Cha mẹ có thể cho tiền giúp đỡ bạn đầu tư sáng lập cơ nghiệp, có thể kiếm tiền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Tật Ách" && $tenCung == "Phụ Mẫu"){
                    $luanCungMenh .= "\r\n bạn có cơ thể cường tráng, có thể lực tốt, nghịch ngợm, hiếu động." .
                    "\r\n bạn có tính tình cương cường, cố chấp, không chịu nghe lời nói thằng, ưa ra oai." ;
                    $luanPhuMau .= "\r\n Cha mẹ ở bên ngoài được người ta xem trọng.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Thiên Di" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ của bạn dạy dỗ con cái nghiêm khắc, không thể trò chuyện trao đổi với nhau." .
                    "\r\n Cha mẹ ờ bên ngoài cạnh tranh với nhiều người, được người ta kính trọng, nhưng cũng dễ có thị phi.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Nô Bộc" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ có cơ thể khỏe mạnh, làm lụng vất vả đến già vẫn không chịu nghỉ." .
                    "\r\n Cha mẹ sẽ can thiệp vào chuyện chọn đối tượng giao du của bạn.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Quan Lộc" && $tenCung == "Phụ Mẫu"){
                    $luanQuanLoc .= "\r\n Bản thân nỗ lực, còn được cha mẹ hoặc trường bối giúp đỡ, khiến công việc hay sự nghiệp đều được nâng lên." .
                    "\r\n Bạn bè muốn đầu tư vào sự nghiệp của bạn, có thế kiếm tiền.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Điền Trạch" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=  "\r\n Gia nghiệp do cha mẹ quản lí." ;
                    $luanTaiBach .= "\r\n Nhờ có người phối ngẫu gia đình giúp đỡ mà phát tài.";
                }
                if($sao == "Hóa Quyền" && $lienQuan == "Phúc Đức" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=  "\r\n Cha mẹ ưa phô trương, vung tay rộng rãi, dám hưởng thụ. bạn ưa tranh với bạn bè để được chú ý, vì vậy mà hao tài, chuốc tổn hại cho bản thân.";
                }
                // Hóa Khoa - phụ mẫu
                if($sao == "Tự Hóa Khoa" && $lienQuan == "Chính cung" && $tenCung == "Phụ Mẫu"){
                    $luanCungMenh .= "\r\n Dáng vẻ hiền hòa lễ độ, thanh tú đoan trang, tâm tình bình hòa, xừ sự hòa mục thân thiết, là người an phận thủ thường; lúc đi học có thành tích tốt, dễ có thanh danh.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Mệnh" && $tenCung == "Phụ Mẫu"){
                    $luanQuanLoc .=  "\r\n Được cha mẹ hoặc trường bối quan tâm; quan hệ giao tế với cấp trên rất tốt, công việc nhẹ nhàng, đắc ý." .
                    "\r\n Lúc đòi nợ bạn bè, sẽ nói khéo léo để thuyết phục họ trà, bạn bè sẽ trả làm nhiều kì.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Huynh Đệ" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=  "\r\n Cha mẹ có kế hoạch rất tốt về sinh kế gia đình, có thể làm cho người nhà về sau không cần lo lắng." .
                    "\r\n Cha mẹ sống với nhau rất tốt, hòa hợp." .
                    "\r\n Cha mẹ rất quan tâm chăm sóc anh chị em bạn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phu Thê" && $tenCung == "Phụ Mẫu"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp của bạn được cha mẹ hoặc trưởng bối quan tâm giúp đỡ, gia đình của người phối ngẫu cũng sẽ ra sức trợ giúp, khiến sự nghiệp của bạn phát triển thuận lợi." ;
                    $luanPhuThe .= "\r\n Người phối ngẫu là con nhà gia giáo, được quan tâm chăm sóc tốt.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tử Tức" && $tenCung == "Phụ Mẫu"){
                    $luanTuTuc .= "\r\n Con cái phần nhiều đều được quý nhân đề bạt, nâng đỡ; việc học hành và sự nghiệp đều thuận lợi, dễ có thành danh." ;
                    $luanPhuMau .= "\r\n Cha mẹ rất quan tâm chăm sóc gia đình, có tình cảm tốt đẹp với con cái của bạn." .
                    "\r\n Cha mẹ ở bên ngoài rất có duyên vói người chung quanh, sống hòa hợp vói mọi người, có thành danh.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tài Bạch" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=  "\r\n Cha mẹ rất quan tâm chăm sóc bạn." ;
                    $luanTaiBach .= "\r\n bạn qua lại tiền bạc với bạn bè rất có uy tín, xử lí rành mạch." .
                    "\r\n bạn với bạn bè cũng thường tiêu xài tiền chung với nhau, đôi bên giúp đỡ lẫn nhau, tiền bạc rõ ràng, ít xảy ra chuyện phiền phức, rắc rối.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Tật Ách" && $tenCung == "Phụ Mẫu"){
                    $luanTatAch .= "\r\n bạn có cơ thể khỏe mạnh, ít bệnh đau";
                    $luanQuanLoc .= "\r\n làm công việc liên quan đến văn hóa giáo dục dễ có tiếng tăm." ;
                    $luanPhuMau .= "\r\n Cha mẹ rất có duyên với người chung quanh, giao du hòa hợp với mọi người, ít xảy ra tranh cãi, được nhiều người biết tên tuổi.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Thiên Di" && $tenCung == "Phụ Mẫu"){
                    $luanThienDi .= "\r\n bạn ra bên ngoài được trưởng bối quý nhân giúp đỡ;thành tích học tập tốt." ;
                    $luanPhuMau .= "\r\n Cha mẹ của bạn rất có duyên với người chung quanh, giao du bạn bè phần nhiều là người hiền hòa, lễ độ, có thể giúp đỡ lẫn nhau.";
                    
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Nô Bộc" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ lúc còn trẻ rất chuyên tâm học hành và làm việc, hòa hợp với bạn học, đồng sự, đồng nghiệp; công việc thuận lợi, có thành danh." ;
                    $luanNoBoc .= "\r\n Bạn bè của bạn phần nhiều là người lạnh nhạt với tài lợi, dùng tiền có kế hoạch rõ ràng, không lãng phí.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Quan Lộc" && $tenCung == "Phụ Mẫu"){
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp của bạn được cấp trên hay trưởng bối trợ giúp, hoặc nhờ quan hệ giao tế của cha mẹ giúp đỡ, khiến công việc thuận lợi, không có sóng gió trắc trở, tài lợi bình ổn.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Điền Trạch" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ rất quan tâm gia đình, giỏi chăm lo việc nhà." .
                    "\r\n Quan hệ gia đinh hai bên của vợ chồng rất tốt" ;
                    $luanTatAch .= "\r\n Sau kết hôn, bạn có sức khỏe tốt, tâm tình vui vẻ, ít sinh bệnh.";
                }
                if($sao == "Hóa Khoa" && $lienQuan == "Phúc Đức" && $tenCung == "Phụ Mẫu"){
                    $luanCungMenh .=  "\r\n Tính tình cời mở, tâm trạng ổn định, ưa hưởng thụ, có thể cân đối thu chi, không lãng phí, có phúc ấm của tổ tiên." ;
                    $luanPhuMau .=  "\r\n Cha mẹ thanh nhàn, dễ chiếm cảm tình, có thị hiếu lành mạnh." .
                    "\r\n Cha mẹ của bạn có giúp đỡ cho công việc hay sự nghiệp của người phối ngẫu.";
                }

                // Hóa Kỵ - phụ mẫu
                if($sao == "Tự Hóa Kỵ" && $lienQuan == "Chính cung" && $tenCung == "Phụ Mẫu"){
                    $luanCungMenh .=  "\r\n Bạn là người kỹ tính, có cái nhìn tinh tế, đa nghi, thường vì chuyện nhỏ mà nghi ngờ, tính tình hay thay đổi, hay để bụng chuyện nhỏ" .
                    "\r\n Dễ dùng lời nói làm tổn thương người khác, trên mặt dễ có vết sẹo" .
                    "\r\n Hồi nhỏ thành tích học tập không được tốt" .
                    "\r\n Lúc đi làm thường không hòa hợp với sếp hoặc đồng nghiệp, vì vậy không thích hợp đứng vị trí lãnh đạo hay quản lý do mọi người khó phục" .
                    "\r\n Không nên đầu cơ, đầu tư, chơi hụi vì dễ bị thua lỗ" ;
                    $luanPhuMau .= "\r\n Vận thế của cha mẹ kém, khi lớn lên không ở chung với cha mẹ";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Mệnh" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n bạn với cha mẹ dễ có sự ngăn cách giữa hai đời." ;
                    $luanCungMenh .= "\r\n bạn phản ứng không được nhanh nhạy, là người lương thiện, thiệt thà, dê bĩ người ta lừa gạt." ;
                    $luanTaiBach .= "\r\n Bạn bè mượn tiền khó đòi, có trả cũng sẽ dây dưa, chia ra làm nhiều đợt." .
                    "\r\n Không nên cho người ta vay tiền để lấy lại, dễ giật." ;
                    $luanQuanLoc .= "\r\n Vận học hành không thuận lợi, thành tích kém." .
                    "\r\n Thích nghiên cứu triết học, huyền học, có khuynh hướng tiếp cận tôn giáo, tín ngưỡng." ;
                    $luanTuTuc .= "\r\n Có con nhưng số lượng không nhiều";
                    
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Huynh Đệ" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Công việc hay sự nghiệp của cha mẹ không thuận lợi, khó gánh vác khoản tiền chi dụng trong sinh hoạt gia đình." .
                    "\r\n Cha mẹ không hòa hợp với anh chị em bạn." ;
                    $luanHuynhDe .= "\r\n Anh chị em hay so đo tính toán, ưa tranh đoạt danh lợi, tình cảm với cha mẹ không tốt, thường oán trách." ;
                    $luanPhuMau .= "\r\n Cha mẹ dễ xảy ra chuyện phiền phức, rắc rối về tiền bạc với bạn bè của họ." ;
                    $luanNoBoc .= "\r\n Bạn bè bị áp lực về tài chính.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phu Thê" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Cha mẹ đối với anh em bạn khá nghiêm khắc, quản lí nhân viên cũng khắc khe. Công việc của cha mẹ không thuận lợi, không giúp đỡ cho công việc hay sự nghiệp của bạn. Quan hệ giữa cha mẹ với người phối ngẫu không được tốt." ;
                    $luanQuanLoc .= "\r\n Công việc hay sự nghiệp của bạn có thu nhập ít.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tử Tức" && $tenCung == "Phụ Mẫu"){
                    $luanTuTuc .=  "\r\n Con cái phần nhiều có tính bảo thủ, tham vọng không lớn, không có hoài bão, ít gặp quý nhân." ;
                    $luanPhuMau .=  "\r\n Cha mẹ thường bôn ba ở bên ngoài" .
                    "\r\n Cha mẹ không ở chung với bạn.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tài Bạch" && $tenCung == "Phụ Mẫu"){
                    $luanTaiBach .=  "\r\n Qua lại tiền bạc với bạn bè dễ xảy ra chuyện phiền phức, rắc rối, thường sẽ bị hao tốn hoặc tổn thất; lúc bạn bè trả tiền cho bạn sẽ dây dưa kéo dài." ;
                    $luanQuanLoc .= "\r\n Cẩn thận vấn đề văn thư, hợp đồng... dễ vì lầm lẫn mà bị tổn thất; không nên làm người bảo lãnh, sẽ bị liên lụy." ;
                    $luanPhuMau .= "\r\n Cha mẹ sức khỏe kém; không cách nào giúp đỡ bạn về tài chính.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Tật Ách" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=  "\r\n Cha mẹ không được khỏe mạnh ";
                    $luanTatAch .= "\r\nbản thân bạn cũng nhiều bệnh, thể chất yếu, phản ứng chậm, động tác không linh hoạt." ;
                    $luanTaiBach .= "\r\n Đừng qua lại tiền bạc với bạn bè, sẽ bị giật tiền, đã không đòi được tiền mà còn gặp phiền phức, thị phi kiện tụng.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Thiên Di" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=   "\r\n Cha mẹ có quan niệm hoàn toàn khác với bạn, giữa hai đời có sự ngăn cách, sau khi trường thành ít gặp nhau, tình thân nhạt nhẽo." ;
                    $luanTaiBach .= "\r\n Bạn bè sẽ giật nợ, bạn không đòi tiền được." ;
                    $luanCungMenh .= "\r\n bạn tính tình chất phác, phản ứng chậm chạp, ở bên ngoài dễ bị thua thiệt, bị người ta lừa gạt." ;
                    $luanPhuThe .= "\r\n Vợ chồng duyên ít, kết hôn muộn";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Nô Bộc" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .= "\r\n Vận thế của cha mẹ không tốt, cơ thể suy nhược, làm việc vất vả mà ít thành, không cách nào chu cấp đủ cho sinh hoạt gia đình, qua lại tiền bạc với bạn bè dễ bị thua thiệt, gặp nhiều khó khăn." .
                    "\r\n Tình cảm giữa cha mẹ không được hòa hợp, gần nhau ít mà xa nhau nhiều.";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Quan Lộc" && $tenCung == "Phụ Mẫu"){
                    $luanQuanLoc .= "\r\n Vận sự nghiệp không tốt, không thích hợp đi làm hưởng lương sẽ khó thăng tiến" .
                    "\r\n Nếu tự sáng lập cơ nghiệp thì không nên mở rộng quá độ dễ bị mắc nợ" .
                    "\r\n Lúc còn đi học thành tích kém";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Điền Trạch" && $tenCung == "Phụ Mẫu"){
                    $luanCungMenh .= "\r\n Lúc còn nhỏ, gia cảnh không được tốt, nhiều thị phi" ;
                    $luanPhuThe .= "\r\n Gia cảnh người phối ngẫu cũng có nhiều khó khăn, không được khá giả" ;
                    $luanTuTuc .= "\r\n Bạn có ít con cái hoặc muộn con, sự nghiệp của con cái có nhiều biến động, nên xa quê hương để phát triển, thích hợp làm các công việc ngoại vụ" ;
                    $luanPhuMau .= "\r\n Cha mẹ buôn ba vất vả, thu nhập kém mà không được toại ý";
                }
                if($sao == "Hóa Kỵ" && $lienQuan == "Phúc Đức" && $tenCung == "Phụ Mẫu"){
                    $luanPhuMau .=  "\r\n Cha mẹ sức khỏe không được tốt, có bệnh tật, quan hệ với ông bà không được tốt" ;
                    $luanTaiBach .= "\r\n Bạn bè mượn tiền bạn thì bạn khó đòi được, bạn mượn tiền bạn bè năm ba bận đều không cho mượn" ;
                    $luanCungMenh .= "\r\n Hồi đi học thì dễ chuyển trường, chuyển hệ hoặc gián đoạn, mất nhiều thời gian hơn người bình thường để hoàn thành khóa học" ;
                    $luanPhuThe .= "\r\n Gia đình người phối ngẫu vì hưởng thụ mà lãng phí tiền bạc, tình trạng tài chính không được tốt.";
                }
            }
        }
    }
    $output .= "\n Luận giải cung Mệnh:\n" . $luanCungMenh . "\n\n Luận giải cung Huynh Đệ:\n" . $luanHuynhDe . 
    "\n\n Luận giải cung Phu Thê:\n" . $luanPhuThe . "\n\n Luận giải cung Tử Tức:\n" . $luanTuTuc .
    "\n\n Luận giải cung Tài Bạch:\n" . $luanTaiBach . "\n\n Luận giải cung Tật Ách:\n" . $luanTatAch .
    "\n\n Luận giải cung Thiên Di:\n" . $luanThienDi . "\n\n Luận giải cung Nô Bộc:\n" . $luanNoBoc .
    "\n\n Luận giải cung Quan Lộc:\n" . $luanQuanLoc . "\n\n Luận giải cung Điền Trạch:\n" . $luanDienTrach .
    "\n\n Luận giải cung Phúc Đức:\n" . $luanPhucDuc . "\n\n Luận giải cung Phụ Mẫu:\n" . $luanPhuMau ;
    return $output;
}
function kiemTraTuHoaPhai(array $laSoData, string $tenCung, string $sao, string $lienQuan): bool {
    foreach ($laSoData as $cung) {
        if (($cung['cung'] ?? '') === $tenCung) {
            $tuHoaPhai = $cung['tu_hoa_phai'] ?? [];
            foreach ($tuHoaPhai as $saoTen => $lienQuanTen) {
                if (trim($saoTen) === $sao && trim($lienQuanTen) === $lienQuan) {
                    return true;
                }
            }
        }
    }
    return false;
}

?>