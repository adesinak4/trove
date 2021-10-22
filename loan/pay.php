<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../classes/Database.php';
require '../middlewares/Auth.php';

$allHeaders = getallheaders();
$db_connection = new Database();
$conn = $db_connection->dbConnection();
$auth = new Auth($conn,$allHeaders);

$returnData = [
    "success" => 0,
    "status" => 401,
    "message" => "Unauthorized"
];

if($auth->isAuthe()){
    $returnData = $auth->isAuthe();
    $rate = $returnData{"loan"}{"prorate"};
    $balance = $returnData{"loan"}{"balance"};
    $info = $returnData{"loan"}{"email"};

}

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

if($_SERVER["REQUEST_METHOD"] != "PUT"):
    $returnData = msg(0,404,'Page Not Found!');

// $check_loan = "SELECT `balance` FROM `loan` WHERE `email`=$info";
//     $check_loan_stmt = $conn->prepare($check_loan);
//     $check_loan_stmt->bindValue($info, $info,PDO::PARAM_STR);
//     $check_loan_stmt->execute();
else:
    if($balance > 0):
        try{
            $result = $balance - $rate;
            if($result > 0):
            $update_query = "UPDATE `loan` SET balance = $result
            WHERE email = '$info'";

            $update_stmt = $conn->prepare($update_query);

            // DATA BINDING
            $update_stmt->bindValue($result, $result,PDO::PARAM_INT);

            $update_stmt->execute();
            else:
                $update_query = "UPDATE `loan` SET balance = 0
            WHERE email = '$info'";

            $update_stmt = $conn->prepare($update_query);

            // DATA BINDING
            //$update_stmt->bindValue($result, $result,PDO::PARAM_INT);

            $update_stmt->execute();
            endif;

            $returnData = msg(1,201,'You have paid $'.$rate.' from your loan. See you next month');

        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    else:
            $update_query = "UPDATE `loan` SET loanAmount = 0, duration = 0, prorate = 0
            WHERE email = '$info'";

            $update_stmt = $conn->prepare($update_query);

            // DATA BINDING
            // $update_stmt->bindValue($result, $result,PDO::PARAM_STR);

            $update_stmt->execute();

            $returnData = msg(1,201,'You have no pending loan.');
    endif;
endif;

echo json_encode($returnData);
?>