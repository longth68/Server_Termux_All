<?php
// HASHIRAMA: auto-check MBbank qua sieuthicode la dead-code o NRO-LOCAL (return ngay tu dau)
// va khong dung voi schema hashirama. Tra loi JSON de trang khong bi treo.
echo json_encode(array("status" => "error", "message" => "Chức năng tự kiểm tra MBbank không khả dụng. Giao dịch chuyển khoản được xử lý tự động qua bank-hook."));
