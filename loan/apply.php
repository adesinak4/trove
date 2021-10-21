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


function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

// GET DATA FORM REQUEST
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// IF REQUEST METHOD IS NOT PUT
if($_SERVER["REQUEST_METHOD"] != "PUT"):
    $returnData = msg(0,404,'Page Not Found!');

// CHECKING EMPTY FIELDS
elseif(!isset($data->amount) 
    || !isset($data->period)
    || empty(trim($data->amount))
    || empty(trim($data->period))
    ):

    $fields = ['fields' => ['amount','period']];
    $returnData = msg(0,422,'Please Fill in all Required Fields!',$fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    
    $amount = trim($data->amount);
    $period = trim($data->period);

    if($auth->isAuth()){
        $returnData = $auth->isAuth();
        $value = $returnData{"user"}{"portfolioValue"};
        $info = $returnData{"user"}{"id"};

        $val = 0.6 * $value;
    }

    $check_loan = "SELECT `loanAmount` FROM `loan` WHERE `loanAmount`=:amount";
    $check_loan_stmt = $conn->prepare($check_loan);
    $check_loan_stmt->bindValue(':amount', $amount,PDO::PARAM_STR);
    $check_loan_stmt->execute();

    if($check_loan_stmt->rowCount()):
        $returnData = msg(0,422, 'Please Pay up your previous loan!');

    elseif($amount > $val):
        $returnData = msg(0,422,'Enter an amount that is not higher than 60% of your Portfolio Value!');

    elseif($period < 6 || $period > 12):
        $returnData = msg(0,422,'Enter a value that is in between 6 and 12 months!');

    else:
        try{
            $prorate = $amount / $period;
            $update_query = "UPDATE `loan` SET loanAmount = :amount, balance = :amount, duration = :period, prorate = $prorate
            WHERE id = '$info'";

            $update_stmt = $conn->prepare($update_query);

            // DATA BINDING
            $update_stmt->bindValue(':amount', $amount,PDO::PARAM_STR);
            $update_stmt->bindValue(':period', $period,PDO::PARAM_STR);
            $update_stmt->bindValue($prorate, $prorate,PDO::PARAM_STR);

            $update_stmt->execute();

            $returnData = msg(1,201,'Your Loan has been Approved');

        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;
    
endif;

echo json_encode($returnData);

// echo json_encode('$'.$info);
?>