<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../log/receipt_generation.log');
require_once __DIR__ . '/../admin/config/config.php';
require_once __DIR__ . '/../admin/models/EmailModel.php';


function logMessage($message){
    $log_file = __DIR__ . '/../log/paymentlogs.log';
    $log_message = date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

function writeToFile($file_path, $content){
    $file_handle = fopen($file_path, 'w');
    if($file_handle){
        if(fwrite($file_handle, $content) === false){
            logMessage("Failed to write to file: $file_path");
            return false;
        } else {
            logMessage("Successfully wrote to file: $file_path");
            return true;
        }
        fclose($file_handle);
    } else {
        logMessage("Failed to open file: $file_path for writing");
        return false;
    }
}

function writeJsonDataToFile($dest, $data){
    $json_data = json_encode($data, JSON_PRETTY_PRINT);
    if($json_data === false){
        //echo "Failed to encode data to JSON" . PHP_EOL;
        return false;
    }
    if(!writeToFile($dest, $json_data)) return false;
    return true;
}

function makeHTMLReceiptFromJSON($json_data_file){
    if(!file_exists($json_data_file)){
        logMessage("JSON data file does not exist: $json_data_file");
        return false;
    }
    $json_content = file_get_contents($json_data_file);
    $data = json_decode($json_content, true);
    if($data === null){
        logMessage("Failed to decode JSON data from file: $json_data_file");
        return false;
    }
    // styles should be well aligned, 4 space indentation
    $html_content = "<html lang='en'>
    <head>
    <meta charset='UTF-8'/>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'/>
    <title>Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        *{
        box-sizing: border-box;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .content { background: #f9f9f9; padding: 30px; }
        .receipt.header {
            background: linear-gradient(45deg, #785109, #770376);
            color: white; padding: 20px;
            text-align: center; display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo img { max-height: 50px; }
        .transaction-info { text-align: right; }
        th, td { border: 0px solid #ddd; padding: 8px; text-align: left;
        word-wrap: break-word; text-overflow: ellipsis; }
        th { background-color: #f2f2f2; }
        table{
            table-layout: fixed;
        }
        a {
            color: #4CAF50; text-decoration: none; padding:
            10px; border-radius: 5px; text-align: center;
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            display: inline-block;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            margin-top: 20px;
        }
        footer {
            margin-top: 30px; padding: 10px; background: #333;
            color: white; text-align: center; font-size: 12px;
        }
    </style>
    </head><body>";
    $html_content .= "<div class='container'>
    <div class='receipt header'>
        <div class='logo'>
            <img src='cid:\\favicon.png' alt='Company Logo' />
        </div>
        <div class='transaction-info'>
            <h2>Receipt</h2>
            <p>Transaction ID: " . htmlspecialchars($data['transaction_id']) . "</p>
            <p>Date: " . htmlspecialchars($data['date']) . "</p>
        </div>
    </div>
    <div class='content'>
    <h3>Здравствуйте, " . htmlspecialchars($data['customer_name']) . "!</h3>
    <p>Благодарим вас за использование наших услуг.</p>
    <h3>Детали транзакции:</h3>
    <table border='0' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
        <tr style='background: #e9e9e9;'>
            <th></th>
            <th></th>
        </tr>
    ";
    foreach($data['service'] as $key => $value){
        if($key === 'image_location') continue;
        $html_content .= "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    $html_content .= isset($data['service']['image_location']) ? 
    "<tr>
        <td>Image</td>
        <td><img src='" . htmlspecialchars($data['service']['image_location']) . "' alt='Service Image' style='width: 200px; height: 200px;'/>
        </td>
    </tr>" : "";
    $html_content .= "
    </table></div>
    </div>
    <footer>
        <div class='company-info'>
            <h1>MediaHub</h1>
            <p></p>
            <p>Email:" . SITE_EMAIL . "</p>
            <p>Phone: +7 (995) 953-16-11</p>
            <p>Address: 30 Grazhdansky Ave, St Petersburg, Russia</p>
            <a href='https://s120187.foxcdn.ru'>For more visit our site</a>
        </div>
    </footer>
    </body></html>";
    $html_file = str_replace('.json', '.html', $json_data_file);
    writeToFile($html_file, $html_content);

    // log output to log file
    $log_file = __DIR__ . '/../log/receipt_generation.log';
    $log_message = date('Y-m-d H:i:s') . " - Generated HTML receipt: $html_file from JSON: $json_data_file" . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
    return true;
}

if(isset($_GET['q'])){
    switch($_GET['q']){
        case 'sendreceipt':
            try{
                if(isset($_GET['f'])){
                    $transaction_name = $_GET['f'];
                    $transaction_data = file_get_contents('php://input');
                    if($transaction_data === false){
                        echo json_encode(["status" => "error", "message" => "Failed to read transaction data from input"]);
                        exit;
                    }
                    $transaction_data = json_decode($transaction_data, true);
                    if($transaction_data === null){
                        echo json_encode(["status" => "error", "message" => "Failed to decode transaction data from input"]);
                        exit;
                    }
                    $transaction_data['service']['item name'] = $transaction_name;
                    $transaction_data['order_id'] = uniqid('order_', true);
                    $file_dest = __DIR__ . "/../records/receipts/" . $transaction_name . '_' . $transaction_data['order_id'] . '.json';
                    if(writeJsonDataToFile($file_dest, $transaction_data)){
                        if(makeHTMLReceiptFromJSON($file_dest)){
                            $body_html = file_get_contents(str_replace('.json', '.html', $file_dest));
                            if($body_html === false){
                                echo json_encode(["status" => "error", "message" => "Failed to read generated HTML receipt"]);
                                exit;
                            }
                            $email = new Email();
                            $email->prepare(
                                'Your Transaction Receipt',
                                $transaction_data['customer_email'],
                                $transaction_data['customer_name'],
                                $body_html,
                                'Please view the receipt in an HTML compatible email viewer.'
                            );
                            $email->createReceiptPDF($body_html, $transaction_data);
                            $send_result = $email->send();
                            if($send_result['status'] === 'success'){
                                echo json_encode(["status" => "success", "message" => "Receipt email sent successfully"]);
                            } else {
                                error_log("Failed to send receipt email: " . $send_result['message']);
                                echo json_encode(["status" => "error", "message" => $send_result['message']]);
                            }
                        } else {
                            echo json_encode(["status" => "error", "message" => "Failed to generate HTML receipt"]);
                        }
                    } else {
                        echo json_encode(["status" => "error", "message" => "Failed to write transaction data to file"]);
                    }
                } else {
                    echo json_encode(["status" => "error", "message" => "No transaction file specified"]);
                }
            } catch(Exception $e){
                logMessage($e);
                echo json_encode(["status" => "error", "message" => $e->getMessage() . ',' . var_dump($transaction_data)]);
            }
            break;
        default:
            echo json_encode(["status" => "error", "message" => "Invalid query parameter"]);
            break;
    }
}

exit;
?>