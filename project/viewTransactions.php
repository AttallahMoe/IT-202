<?php
require_once (__DIR__ . "/partials/nav.php");

if (!is_logged_in()) {
    //this will redirect to login and kill the rest of this script (prevent it from executing)
    flash("You must be logged in to access this page");
    die(header("Location: login.php"));
}

$check = true;
$results = [];

if(isset($_GET["id"])){
    $transId = $_GET["id"];
}
else{
    $check = false;
    flash("Id is not set in url");
}

if(isset($_GET["start"])){
    $page = $_GET["start"];
}
else{
    $page = 0;
    flash("page is not set in url");
}

//TODO Fix this so that it returns actual account numbers in the query, not the internal id. Fixed!!!
if($check) {
    $db = getDB();

    //TODO pageination
    $numPerPage = 5;
    $numRecords = 0;

    $stmt = $db->prepare("SELECT COUNT(act_src_id) FROM Transactions WHERE id=:id");
    $r = $stmt->execute([":id" => $transId]);
    $numRecords = $stmt->fetch(PDO::FETCH_ASSOC);

    $numRecords = (int)$numRecords;
    $numLinks = ceil($numRecords/$numPerPage); //gets number of links to be created
    //$page = $_GET['start'];
    //if(!$page) $page=0;
    $start = $page * $numPerPage;

    $stmt = $db->prepare("SELECT act_src_id, Accounts.id, Accounts.account_number, amount, action_type, memo FROM Transactions JOIN Accounts on Accounts.id = Transactions.act_dest_id WHERE act_src_id =:id LIMIT 10");
    $r = $stmt->execute([":id" => $transId]);
    if ($r){
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    else{
        $e = $stmt->errorInfo();
        flash("There was a problem fetching the results." . var_export($e, true));
        $check = false;
    }

    for($i=0;$i<$numLinks;$i++){
        $y = $i + 1;
        echo '<a href="viewTransactions.php?id='.$transId.'?start='.$i.'">'.$y.'</a>';
    }

}

?>
<form method="POST">
    <label><strong>Filter Transactions</strong></label>
    <br>
    <label>START:<br></label>
    <input type="date" name="dateStart" />
    <br>
    <label>END:<br></label>
    <input type="date" name="dateTo"/>
    <label>Transaction Type: <br></label>
    <select name="action_type">
        <option value="deposit">Deposit</option>
        <option value="withdraw">Withdraw</option>
        <option value="transfer">Transfer</option>
    </select>
    <input type="submit" name="save" value="Filter" />
</form>

<div class="bodyMain">
    <h1><strong>List Transactions</strong></h1>

    <div class="results">
        <?php if(count($results) > 0 && !isset($_POST["save"])): ?>
            <div class="list-group">
                <?php foreach ($results as $r): ?>
                    <div class="list-group-item">
                        <div>
                            <div>Destination Account ID:</div>
                            <div><?php safer_echo($r["account_number"]); ?></div>
                        </div>
                        <div>
                            <div>Transaction Type:</div>
                            <div><?php safer_echo($r["action_type"]); ?></div>
                        </div>
                        <div>
                            <div>Amount Moved:</div>
                            <div><?php safer_echo($r["amount"]); ?></div>
                        </div>
                        <div>
                            <div>Memo:</div>
                            <div><?php safer_echo($r["memo"]); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="display:none"></div>
        <?php endif; ?>
    </div>

<?php
    if(isset($_POST["save"])){
        $startDate = $_POST["dateStart"];
        $endDate = $_POST["dateTo"];
        $type = $_POST["action_type"];
        $results = [];

        //$stmt->bindValue(":memo", $memo, PDO::PARAM_STR);

        $startDate = (String)$startDate . ' 00:00:00';
        $endDate = (String)$endDate . ' 00:00:00';

        echo $endDate;

        $stmt = $db->prepare("SELECT act_src_id, Accounts.id, Accounts.account_number, amount, action_type, memo FROM Transactions JOIN Accounts on Accounts.id = Transactions.act_src_id WHERE act_src_id =:id AND action_type=:action_type AND created BETWEEN :startDate AND :endDate LIMIT 10");
        $stmt->bindValue(":startDate", $startDate, PDO::PARAM_STR);
        $stmt->bindValue(":endDate", $endDate, PDO::PARAM_STR);
        $stmt->bindValue(":action_type", $type, PDO::PARAM_STR);
        $stmt->bindValue(":id", $transId, PDO::PARAM_INT);
        $r = $stmt->execute();
        if ($r){
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        else{
            $e = $stmt->errorInfo();
            flash("There was a problem fetching the results." . var_export($e, true));
            $check = false;
        }


    }

require(__DIR__ . "/partials/flash.php");
?>

    <div class="bodyMain">

        <div class="results">
            <?php if(count($results) > 0 && isset($_POST["save"])): ?>
                <h1><strong>Filtered Transactions</strong></h1>
                <div class="list-group">
                    <?php foreach ($results as $r): ?>
                        <div class="list-group-item">
                            <div>
                                <div>Destination Account ID:</div>
                                <div><?php safer_echo($r["account_number"]); ?></div>
                            </div>
                            <div>
                                <div>Transaction Type:</div>
                                <div><?php safer_echo($r["action_type"]); ?></div>
                            </div>
                            <div>
                                <div>Amount Moved:</div>
                                <div><?php safer_echo($r["amount"]); ?></div>
                            </div>
                            <div>
                                <div>Memo:</div>
                                <div><?php safer_echo($r["memo"]); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No Results</p>
            <?php endif; ?>
        </div>
