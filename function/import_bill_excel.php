<?php
// import_bill_service_excel.php

require_once '../config/config.php';
require_once 'functions.php';

// ตรวจสอบว่ามีไฟล์ถูกอัปโหลดหรือไม่ (สำหรับการนำเข้าข้อมูลจากไฟล์ Excel)
if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีไฟล์ถูกอัปโหลดหรือเกิดข้อผิดพลาดในการอัปโหลดไฟล์']);
    exit;
}

// เรียกใช้ไลบรารี PhpSpreadsheet
require '../vendor/autoload.php';
function isValidPrice($value) {
    return filter_var($value, FILTER_VALIDATE_FLOAT) !== false && preg_match('/^\d+(\.\d{1,2})?$/', $value);
}

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = $_FILES['excelFile']['tmp_name'];
try {
    // อ่านไฟล์ Excel
    $spreadsheet = IOFactory::load($file);
    
    // ตรวจสอบ customer_id จาก POST request
    if (!isset($_POST['id_customer']) || empty($_POST['id_customer'])) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสลูกค้า']);
        exit;
    }
    
    $id_customer = intval($_POST['id_customer']);
    
    // ตรวจสอบว่าลูกค้ามีอยู่จริงหรือไม่
    $checkCustomer = "SELECT id_customer FROM customers WHERE id_customer = ?";
    $stmt = $conn->prepare($checkCustomer);
    $stmt->bind_param("i", $id_customer);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลลูกค้า']);
        exit;
    }
    
    
    // นับจำนวนบิลและบริการที่นำเข้าสำเร็จ
    $billSuccessCount = 0;
    $billUpdatedCount = 0;
    $serviceSuccessCount = 0;
    $serviceUpdatedCount = 0;
    $gadgetSuccessCount = 0;
    $gadgetUpdatedCount = 0;
    $packageSuccessCount = 0;
    $packageUpdatedCount = 0;
    $productSuccessCount = 0;
    $productUpdatedCount = 0;
    $overrideSuccessCount = 0;
    $overrideUpdatedCount = 0;
    $errors = [];
    
    // เริ่มทำ transaction
    $conn->begin_transaction();
    
    try {
        // อ่าน Sheet ของ Bills
        $billSheet = $spreadsheet->getSheetByName('Bills');
        if ($billSheet) {
            $billRows = $billSheet->toArray();
            
            // ตรวจสอบหัวคอลัมน์สำหรับ Bills
            $billHeader = array_shift($billRows);
            $expectedBillHeader = ['Number', 'Type', 'Status', 'Start', 'Contact(Month)'];
            
            if ($billHeader !== $expectedBillHeader) {
                throw new Exception('รูปแบบ Sheet "Bills" ไม่ถูกต้อง');
            }
            
            $validTypes = ['CIP+', 'Special Bill', 'Nt1'];
            $validStatuses = ['ใช้งาน', 'ยกเลิกใช้งาน'];
            $validStatusesG = ['ใช้งาน', 'ยกเลิก'];
            
            // Process Bills
            $billMap = []; // เก็บความสัมพันธ์ระหว่าง number_bill และ id_bill เพื่อใช้สำหรับ services
            
            foreach ($billRows as $rowIndex => $row) {
                // ข้ามแถวที่ว่าง
                if (empty($row[0])) {
                    continue;
                }
                
                $number_bill = trim($row[0]);
                $type_bill = trim($row[1]);
                $status_bill = trim($row[2]);
                $create_at = trim($row[3]);
                $date_count = intval(trim($row[4]));
                
                // ตรวจสอบค่า type และ status
                if (!in_array($type_bill, $validTypes)) {
                    $errors[] = "ประเภทบิล $type_bill ไม่ถูกต้อง";
                    continue;
                }
                if (!in_array($status_bill, $validStatuses)) {
                    $errors[] = "สถานะบิล $status_bill ไม่ถูกต้อง";
                    continue;
                }
                
                // ตรวจสอบรูปแบบวันที่
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $create_at)) {
                    $create_at = date('Y-m-d', strtotime($create_at));
                }
                
                // คำนวณวันที่สิ้นสุดสัญญาจากเดือน
                $end_date = date('Y-m-d', strtotime($create_at . " + $date_count months"));
                
                // ตรวจสอบว่าบิลซ้ำหรือไม่
                $checkDuplicate = "SELECT id_bill FROM bill_customer WHERE number_bill = ? AND id_customer = ?";
                $stmt = $conn->prepare($checkDuplicate);
                $stmt->bind_param("si", $number_bill, $id_customer);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // หากมีอยู่แล้ว ให้อัปเดตข้อมูล
                    $bill_row = $result->fetch_assoc();
                    $id_bill = $bill_row['id_bill'];
                    
                    $updateBill = "UPDATE bill_customer SET type_bill = ?, status_bill = ?, create_at = ?, update_at = NOW(), date_count = ?, end_date = ? WHERE number_bill = ? AND id_customer = ?";
                    $stmt = $conn->prepare($updateBill);
                    $stmt->bind_param("ssssssi", $type_bill, $status_bill, $create_at, $date_count, $end_date, $number_bill, $id_customer);
                    if ($stmt->execute()) {
                        $billUpdatedCount++;
                    }
                } else {
                    // เพิ่มข้อมูลบิลใหม่
                    $insertBill = "INSERT INTO bill_customer (id_customer, number_bill, type_bill, status_bill, create_at, update_at, date_count, end_date) 
                                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)";
                    $stmt = $conn->prepare($insertBill);
                    $stmt->bind_param("issssss", $id_customer, $number_bill, $type_bill, $status_bill, $create_at, $date_count, $end_date);
                    if ($stmt->execute()) {
                        $id_bill = $conn->insert_id;
                        $billSuccessCount++;
                    }
                }
                
                // เก็บข้อมูลสำหรับใช้ในการเชื่อมโยงกับบริการ
                $billMap[$number_bill] = $id_bill;
            }
            
            // อ่าน Sheet ของ Services (ถ้ามี)
            $serviceSheet = $spreadsheet->getSheetByName('Services');
            if ($serviceSheet) {
                $serviceRows = $serviceSheet->toArray();
                
                // ตรวจสอบหัวคอลัมน์สำหรับ Services
                $serviceHeader = array_shift($serviceRows);
                $expectedServiceHeader = ['Number', 'Code', 'Type', 'Gadget', 'Status', 'Start'];
                
                if ($serviceHeader !== $expectedServiceHeader) {
                    throw new Exception('รูปแบบ Sheet "Services" ไม่ถูกต้อง');
                }
                
                $validServiceTypes = ['Fttx', 'Fttx+ICT solution', 'Fttx 2+ICT solution', 'SI service', 'วงจเช่า', 'IP phone', 'Smart City', 'WiFi', 'อื่นๆ'];
                $validGadgetTypes = ['เช่า', 'ขาย', 'เช่าและขาย'];
                $validServiceStatuses = ['ใช้งาน', 'ยกเลิก'];
                $serviceMap = [];
                foreach ($serviceRows as $rowIndex => $row) {
                    // ข้ามแถวที่ว่าง
                    if (empty($row[0])) {
                        continue;
                    }

                    $bill_number = trim($row[0]);
                    $code_service = trim($row[1]);
                    $type_service = trim($row[2]);
                    $type_gadget = trim($row[3]);
                    $status_service = trim($row[4]);
                    $create_at = trim($row[5]);
                    
                    // ตรวจสอบค่า types และ status
                    if (!in_array($type_service, $validServiceTypes)) {
                        $errors[] = "ประเภทบริการ $type_service ไม่ถูกต้อง";
                        continue;
                    }
                    if (!in_array($type_gadget, $validGadgetTypes)) {
                        $errors[] = "ประเภทอุปกรณ์ $type_gadget ไม่ถูกต้อง";
                        continue;
                    }
                    if (!in_array($status_service, $validServiceStatuses)) {
                        $errors[] = "สถานะบริการ $status_service ไม่ถูกต้อง";
                        continue;
                    }
                    
                    // ตรวจสอบว่ามีหมายเลขบิลใน billMap หรือไม่
                    if (!isset($billMap[$bill_number])) {
                        $errors[] = "ไม่พบหมายเลขบิล $bill_number สำหรับบริการ $code_service";
                        continue;
                    }
                    
                    $id_bill = $billMap[$bill_number];
                    
                    // ตรวจสอบรูปแบบวันที่
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $create_at)) {
                        $create_at = date('Y-m-d', strtotime($create_at));
                    }
                    
                    // ตรวจสอบว่าบริการซ้ำหรือไม่
                    $checkDuplicate = "SELECT id_service FROM service_customer WHERE code_service = ? AND id_bill = ? AND create_at = ?";
                    $stmt = $conn->prepare($checkDuplicate);
                    $stmt->bind_param("sis", $code_service, $id_bill, $create_at);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        // หากมีอยู่แล้ว ให้อัปเดตข้อมูล
                        $service_row = $result->fetch_assoc();
                        $id_service = $service_row['id_service'];
                        
                        $updateService = "UPDATE service_customer SET type_service = ?, type_gadget = ?, status_service = ?, create_at = ?, update_at = NOW() WHERE code_service = ? AND id_bill = ?";
                        $stmt = $conn->prepare($updateService);
                        $stmt->bind_param("sssssi", $type_service, $type_gadget, $status_service, $create_at, $code_service, $id_bill);
                        if ($stmt->execute()) {
                            $serviceUpdatedCount++;
                            // เก็บ id_service ใน map
                            $serviceMap[$code_service] = $id_service;
                        }
                    } else {
                        // เพิ่มข้อมูลบริการใหม่
                        $insertService = "INSERT INTO service_customer (code_service, type_service, type_gadget, status_service, id_bill, create_at, update_at) 
                                       VALUES (?, ?, ?, ?, ?, ?, NOW())";
                        $stmt = $conn->prepare($insertService);
                        $stmt->bind_param("ssssis", $code_service, $type_service, $type_gadget, $status_service, $id_bill, $create_at);
                        if ($stmt->execute()) {
                            $serviceSuccessCount++;
                            // เก็บ id_service ใน map
                            $serviceMap[$code_service] = $conn->insert_id;
                        }
                    }
                    
                    // อ่าน Sheet ของ Package (ถ้ามี)
                    $packageSheet = $spreadsheet->getSheetByName('Package');
                    if ($packageSheet) {
                        $packageRows = $packageSheet->toArray();
                        
                        // ตรวจสอบหัวคอลัมน์สำหรับ Package
                        $packageHeader = array_shift($packageRows);
                        $expectedPackageHeader = ['Code', 'Package Name', 'Package Detail', 'Status Package','Product Name', 'Product Detail', 
                        'Status Product', 'Main Package', 'ICT', 'override Detail', 'Start'];
                        
                        if ($packageHeader !== $expectedPackageHeader) {
                            throw new Exception('รูปแบบ Sheet "Package" ไม่ถูกต้อง');
                        }
                        
                        $validPackageStatuses = ['ใช้งาน', 'ยกเลิก'];
                        $validProductStatuses = ['ใช้งาน', 'ยกเลิก'];
                        // Initialize package map for the service
                        $packageServiceMap = [];

                        // Process package data...
                        foreach ($packageRows as $rowIndex => $row) {
                            // Skip empty rows
                            if (empty($row[0])) {
                                continue;
                            }
                            
                            $code_service = trim($row[0]);
                            $name_package = trim($row[1]);
                            $detail_package = isset($row[2]) ? trim($row[2]) : null;
                            $status_package = trim($row[3]);
                            $name_product = trim($row[4]);
                            $detail_product = isset($row[5]) ? trim($row[5]) : null;
                            $status_product = trim($row[6]);
                            $main_package_value = trim($row[7]);  // Changed variable name for clarity
                            $ict_value = trim($row[8]);           // Changed variable name for clarity
                            $override_detail = trim($row[9]);
                            $create_at = trim($row[10]);

                            // Validate status values
                            if (!in_array($status_package, $validPackageStatuses)) {
                                $errors[] = "สถานะ Package $status_package ไม่ถูกต้อง";
                                continue;
                            }
                            if (!in_array($status_product, $validProductStatuses)) {
                                $errors[] = "สถานะ Product $status_product ไม่ถูกต้อง";
                                continue;
                            }

                            // Check if service code exists in serviceMap
                            if (!isset($serviceMap[$code_service])) {
                                $errors[] = "ไม่พบรหัสบริการ $code_service สำหรับแพ็คเกจ $name_package";
                                continue;
                            }
                            
                            $id_service = $serviceMap[$code_service];

                            // Validate date format
                            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $create_at)) {
                                $create_at = date('Y-m-d', strtotime($create_at));
                            }

                            // Validate price formats
                            if (!isValidPrice($main_package_value) || !isValidPrice($ict_value)) {
                                $errors[] = "Main Package หรือ ICT ต้องเป็นตัวเลขและมีทศนิยมไม่เกิน 2 ตำแหน่ง";
                                continue;
                            }
                            
                            // Convert to float for calculations
                            $mainpackage_price = floatval($main_package_value);
                            $ict_price = floatval($ict_value);
                            $all_price = $mainpackage_price + $ict_price;

                            // Check if package exists for this service
                            $checkPackage = "SELECT id_package FROM package_list WHERE id_service = ? AND name_package = ? AND info_package = ?";
                            $stmt = $conn->prepare($checkPackage);
                            $stmt->bind_param("iss", $id_service, $name_package , $detail_package);
                            $stmt->execute();
                            $resultPackage = $stmt->get_result();

                            if ($resultPackage->num_rows > 0) {
                                // If package exists, update it
                                $package_row = $resultPackage->fetch_assoc();
                                $package_id = $package_row['id_package'];

                                $updatePackage = "UPDATE package_list 
                                                SET info_package = ?, status_package = ?, update_at = NOW() 
                                                WHERE id_package = ?";
                                $stmt = $conn->prepare($updatePackage);
                                $stmt->bind_param("ssi", $detail_package, $status_package, $package_id);
                                if ($stmt->execute()) {
                                    $packageUpdatedCount++;
                                }
                            } else {
                                // If package doesn't exist, add a new one
                                $insertPackage = "INSERT INTO package_list (id_service, name_package, info_package, create_at, update_at, status_package) 
                                                VALUES (?, ?, ?, ?, NOW(), ?)";
                                $stmt = $conn->prepare($insertPackage);
                                $stmt->bind_param("issss", $id_service, $name_package, $detail_package, $create_at, $status_package);
                                if ($stmt->execute()) {
                                    $package_id = $conn->insert_id;
                                    $packageSuccessCount++;
                                }
                            }
                            
                            // Store package_id in map
                            $packageServiceMap[$code_service . "_" . $name_package] = $package_id;  // Add package name to avoid collisions

                            // Check if product exists
                            $checkProduct = "SELECT id_product, create_at FROM product_list WHERE id_package = ? AND name_product = ?";
                            $stmt = $conn->prepare($checkProduct);
                            $stmt->bind_param("is", $package_id, $name_product);
                            $stmt->execute();
                            $resultProduct = $stmt->get_result();

                            if ($resultProduct->num_rows > 0) {
                                // If product exists, update it
                                $product_row = $resultProduct->fetch_assoc();
                                $product_id = $product_row['id_product'];
                                $existing_date = $product_row['create_at'];

                                $updateProduct = "UPDATE product_list 
                                                SET info_product = ?, status_product = ?, update_at = NOW() 
                                                WHERE id_product = ?";
                                $stmt = $conn->prepare($updateProduct);
                                $stmt->bind_param("ssi", $detail_product, $status_product, $product_id);
                                if ($stmt->execute()) {
                                    $productUpdatedCount++;
                                }
                            } else {
                                // If product doesn't exist, add a new one
                                $insertProduct = "INSERT INTO product_list (id_package, name_product, info_product, create_at, update_at, status_product) 
                                                VALUES (?, ?, ?, ?, NOW(), ?)";
                                $stmt = $conn->prepare($insertProduct);
                                $stmt->bind_param("issss", $package_id, $name_product, $detail_product, $create_at, $status_product);
                                if ($stmt->execute()) {
                                    $product_id = $conn->insert_id;
                                    $productSuccessCount++;
                                }
                            }

                            // Handle product activation - Only one product per package can be active
                            if ($status_product === 'ใช้งาน') {
                                // Get all active products for this package
                                $checkActiveProducts = "SELECT id_product, create_at FROM product_list 
                                                    WHERE id_package = ? AND status_product = 'ใช้งาน' AND id_product != ?";
                                $stmt = $conn->prepare($checkActiveProducts);
                                $stmt->bind_param("ii", $package_id, $product_id);
                                $stmt->execute();
                                $activeProductsResult = $stmt->get_result();
                                
                                // Compare dates to keep only the most recent active product
                                $shouldDeactivateOthers = true;
                                
                                while ($activeProduct = $activeProductsResult->fetch_assoc()) {
                                    $activeProductDate = new DateTime($activeProduct['create_at']);
                                    $currentProductDate = new DateTime($create_at);
                                    
                                    // If an existing product is newer, don't deactivate others and set current product to inactive
                                    if ($activeProductDate > $currentProductDate) {
                                        $shouldDeactivateOthers = false;
                                        
                                        // Set current product to inactive
                                        $updateCurrentProduct = "UPDATE product_list SET status_product = 'ยกเลิก', update_at = NOW() WHERE id_product = ?";
                                        $stmt = $conn->prepare($updateCurrentProduct);
                                        $stmt->bind_param("i", $product_id);
                                        $stmt->execute();
                                        break;
                                    }
                                }
                                
                                // If current product is the newest, deactivate all others
                                if ($shouldDeactivateOthers && $activeProductsResult->num_rows > 0) {
                                    $updateOtherProducts = "UPDATE product_list SET status_product = 'ยกเลิก', update_at = NOW() 
                                                        WHERE id_package = ? AND status_product = 'ใช้งาน' AND id_product != ?";
                                    $stmt = $conn->prepare($updateOtherProducts);
                                    $stmt->bind_param("ii", $package_id, $product_id);
                                    $stmt->execute();
                                }
                            }

                            // Handle override data
                            $checkOverride = "SELECT id_overide FROM overide WHERE id_product = ?";
                            $stmt = $conn->prepare($checkOverride);
                            $stmt->bind_param("i", $product_id);
                            $stmt->execute();
                            $resultOverride = $stmt->get_result();

                            if ($resultOverride->num_rows > 0) {
                                // If override exists, update it
                                $updateOverride = "UPDATE overide
                                                SET mainpackage_price = ?, ict_price = ?, info_overide = ?, all_price = ? 
                                                WHERE id_product = ?";
                                $stmt = $conn->prepare($updateOverride);
                                $stmt->bind_param("ddsdi", $mainpackage_price, $ict_price, $override_detail, $all_price, $product_id);
                                if ($stmt->execute()) {
                                    $overrideUpdatedCount++;
                                }
                            } else {
                                // If override doesn't exist, add a new one
                                $insertOverride = "INSERT INTO overide (id_product, mainpackage_price, ict_price, info_overide, all_price) 
                                                VALUES (?, ?, ?, ?, ?)";
                                $stmt = $conn->prepare($insertOverride);
                                $stmt->bind_param("iddsd", $product_id, $mainpackage_price, $ict_price, $override_detail, $all_price);
                                if ($stmt->execute()) {
                                    $overrideSuccessCount++;
                                }
                            }
                        }
                    }           
                }
            }
            
            // อ่าน Sheet ของ Gadget (ถ้ามี)
            $gadgetSheet = $spreadsheet->getSheetByName('Gedget');
            if ($gadgetSheet) {
                $gadgetRows = $gadgetSheet->toArray();
                
                // ตรวจสอบหัวคอลัมน์สำหรับ Gadget
                $gadgetHeader = array_shift($gadgetRows);
                $expectedGadgetHeader = ['Number', 'Name Device', 'Start', 'Detail', 'Status'];

                
                if ($gadgetHeader !== $expectedGadgetHeader) {
                    throw new Exception('รูปแบบ Sheet "Gedget" ไม่ถูกต้อง');
                }
                
                foreach ($gadgetRows as $rowIndex => $row) {
                    // ข้ามแถวที่ว่าง
                    if (empty($row[0])) {
                        continue;
                    }
                    
                    $bill_number = trim($row[0]);
                    $name_gadget = trim($row[1]);
                    $create_at = trim($row[2]);
                    $note = isset($row[3]) ? trim($row[3]) : null;
                    $status = isset($row[4]);

                    // ตรวจสถานะ
                    if (!in_array($status, $validStatusesG)) {
                        $errors[] = "สถานะอุปกรณ์ $status ไม่ถูกต้อง";
                        continue;
                    }
                    
                    // ตรวจสอบว่ามีหมายเลขบิลใน billMap หรือไม่
                    if (!isset($billMap[$bill_number])) {
                        $errors[] = "ไม่พบหมายเลขบิล $bill_number สำหรับอุปกรณ์ $name_gadget";
                        continue;
                    }
                    
                    $id_bill = $billMap[$bill_number];
                    
                    // ตรวจสอบรูปแบบวันที่
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $create_at)) {
                        $create_at = date('Y-m-d', strtotime($create_at));
                    }
                    
                    // ตรวจสอบว่าอุปกรณ์ซ้ำหรือไม่ (ตรวจสอบจาก name_gadget และ id_bill)
                    $checkDuplicate = "SELECT id_gedget FROM gedget WHERE name_gedget = ? AND id_bill = ?";
                    $stmt = $conn->prepare($checkDuplicate);
                    $stmt->bind_param("si", $name_gadget, $id_bill);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        // หากมีอยู่แล้ว ให้อัปเดตข้อมูล
                        $updateGadget = "UPDATE gedget SET create_at = ?, note = ?, status_gedget = ? WHERE name_gedget = ? AND id_bill = ?";
                        $stmt = $conn->prepare($updateGadget);
                        $stmt->bind_param("ssssi", $create_at, $note, $status, $name_gadget, $id_bill);

                        if ($stmt->execute()) {
                            $gadgetUpdatedCount++;
                        }
                    } else {
                        // เพิ่มข้อมูลอุปกรณ์ใหม่
                        $insertGadget = "INSERT INTO gedget (name_gedget, id_bill, create_at, note, status_gedget) 
                        VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($insertGadget);
                        $stmt->bind_param("sisss", $name_gadget, $id_bill, $create_at, $note, $status);

                        if ($stmt->execute()) {
                            $gadgetSuccessCount++;
                        }
                    }
                }
            }
        } else {
            throw new Exception('ไม่พบ Sheet "Bills" ในไฟล์ Excel');
        }
        
        // หากไม่มีข้อผิดพลาด ให้ commit transaction
        $conn->commit();
        
        $message = "นำเข้าข้อมูลบิลสำเร็จ $billSuccessCount รายการ, อัปเดตข้อมูลบิลสำเร็จ $billUpdatedCount รายการ";
        if ($serviceSuccessCount > 0 || $serviceUpdatedCount > 0) {
            $message .= ", นำเข้าข้อมูลบริการสำเร็จ $serviceSuccessCount รายการ, อัปเดตข้อมูลบริการสำเร็จ $serviceUpdatedCount รายการ";
        }
        if ($gadgetSuccessCount > 0 || $gadgetUpdatedCount > 0) {
            $message .= ", นำเข้าข้อมูลอุปกรณ์สำเร็จ $gadgetSuccessCount รายการ, อัปเดตข้อมูลอุปกรณ์สำเร็จ $gadgetUpdatedCount รายการ";
        }
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'errors' => $errors
        ]);
    } catch (Exception $e) {
        // กรณีเกิด exception ให้ rollback transaction
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => "เกิดข้อผิดพลาดในการนำเข้าข้อมูล: " . $e->getMessage(),
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอ่านไฟล์ Excel: ' . $e->getMessage()]);
}
?>