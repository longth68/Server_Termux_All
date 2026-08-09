<?php
#Nguyen Duc Kien - Ngoc Rong Universe

$_Logo = 'Header.png'; // Thay tên + đuôi của Logo vào đây
$_Domain = '127.0.0.1';
$_Title = 'Hải Tặc Tí Hon - Máy Chủ Hải Tặc Độc Quyền';
// $_ServerName = 'Ngọc Rồng Universe';
$_Description = 'Website chính thức của Hải Tặc Tí Hon – Game Hải Tặc Mobile nhập vai trực tuyến trên máy tính và điện thoại về Hải Tặc phiêu lưu hấp dẫn nhất hiện nay!';
$_ForgotEmail = 'Email'; // Gmail Chạy Quên Mật Khẩu
$_ForgotPass = 'Password'; // Mật Khẩu Gmail Chạy Quên Mật Khẩu
$_GiaTri = '1'; // Nạp x1 -> x2 -> x3 (Thẻ Cào)
$_GiaTriAtm = '1'; // Chuyển Khoản x1 -> x2 -> x3
$_TrangThai = '1'; // Hoạt Động = 1, Bảo Trì = 0 (Trạng Thái Nạp Tiền)
$_AutoMember = '0'; // Auto mở khi nạp = 1, Tắt Auto mở thành viên = 0 (Áp dạng cho nạp thẻ và Atm)
$_FixWeb = '0'; // Bảo Trì = 1, Không Bảo Trì = 0
$_AuthLog = '0'; // Bảo Trì = 1, Không Bảo Trì = 0

#Hỗ Trợ
$_Fanpage = '';
$_Zalo = '';


#---------------#
#Downloads
$_Android = '/Downloads/HaiTacHot.apk'; // Downloads nơi lưu file game
$_Iphone = '/Downloads/HaiTacDragon.ipa';
$_Java = '/Downloads/HaiTacHot.jar';
$_Windows = '/Downloads/HaiTacDragon.rar';

#Card
$Partner_Key = '17f2e600f85de90ae7912172271f415f';
$Partner_Id = '24252101890';
$_ApiCard = 'https://doithe1s.vn/'; // Link Đại Lý Thẻ

#Atm - ACB
$username = ''; // Tài khoản
$password = ''; // Mật khẩu
$account = '2222'; // Số tài khoản
$accountName = ''; // Tên tài khoản acb

function CreateToken()
{
    return md5(uniqid(rand(), true));
}
