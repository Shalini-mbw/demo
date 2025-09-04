<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'data/dbconfig.php';
date_default_timezone_set('Asia/Kolkata');
session_start();

require 'vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Shuchkin\SimpleXLSX;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {  
    if (isset($_FILES['file'])) { 
        $fileTmpPath = $_FILES['file']['tmp_name'];

        // Load the Excel file
        if ($xlsx = SimpleXLSX::parse($fileTmpPath)) {
            $totalRows = count($xlsx->rows()) - 1;
            // Prepare SQL statement
            $stmt = $conn->prepare("INSERT INTO employee (name, email, department, designation, mobile, address, role, isenable, password, addedBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insertCount = 0;
            foreach ($xlsx->rows() as $key => $row) {
                // Skip header row (assuming the first row contains headers)
                if ($key > 0) {
                    $name = $row[0]; // First column
                    $email = $row[1]; // Second column
                    $department = $row[2];
                    $designation = $row[3];
                    $mobile = $row[4];
                    $address = $row[5];
                    $role = $row[6];
                    $isenable = (isset($row[7]) && $row[7] === 'Enable') ? 1 : 0;
                    $password = generateRandomString(8);
                    $addedBy = $JWT_adminName;
                    $assignedDesignation = $JWT_userDesignation ;

                    $template = file_get_contents('Mail\AddEmployee.html');
    
                    // Replace placeholders in the template
                    $template = str_replace('##Name##', $name, $template);
                    $template = str_replace('##Email##', $email, $template);
                    $template = str_replace('##Password##', $password, $template);
                    $template = str_replace('##AssignedBy##', $addedBy, $template);
                    $template = str_replace('##AssignedDesignation##', $assignedDesignation, $template);

                    $Body_message = $template;

        $mail = new PHPMailer(true);
        // Server settings
        $mail->isSMTP();
        $mail->SMTPDebug = false; // Disable debugging for production
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->Username = 'taskenginembw@gmail.com';
        $mail->Password = 'dwed lrmz jzue bsml';
        $mail->SMTPSecure = 'tls'; // Use TLS encryption, `ssl` also accepted

        // Recipients
        $mail->setFrom('taskenginembw@gmail.com', 'Task manager');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Task manager';
        $mail->Body    = $Body_message;

        // Send the email
        if ($mail->send()) {

                    // Bind parameters and execute
                    $stmt->bind_param("ssssssssss", $name, $email, $department, $designation, $mobile, $address, $role, $isenable, $password, $addedBy);
                    if ($stmt->execute()) {
                        $insertCount++;

                    }
                      // Calculate percentage
                      $progress = ($insertCount / $totalRows) * 100;
                      echo json_encode(['progress' => $progress]);
                      flush(); 
                      ob_flush(); 
                }
            }
            }

            $response = [
                'status' => 'success',
                'count' => $insertCount
            ];
            
            echo json_encode($response);// Send back success response
            $stmt->close();
        } else {
            echo "Error parsing the Excel file.";
        }
    } else {
        echo "No file uploaded or there was an upload error.";
    }
}


?>