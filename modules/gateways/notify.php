<?php

use WHMCS\Database\Capsule;

// 引入WHMCS的必要文件
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// 支付网关模块名称
$gatewayModuleName = 'vmq';

// 检索支付网关配置参数
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// 通讯密钥
$key = $gatewayParams['key'];

// 从GET请求中获取参数
$payId = $_GET['payId'];
$param = $_GET['param'];
$type = $_GET['type'];
$price = $_GET['price'];
$reallyPrice = $_GET['reallyPrice'];
$sign = $_GET['sign'];

// 校验签名
$generatedSign = md5($payId . $param . $type . $price . $reallyPrice . $key);

if ($generatedSign != $sign) {
    logTransaction($gatewayParams['name'], $_GET, 'Invalid Signature'); // 记录事务
    echo "？？？";
    exit;
}

// 检查transactionId是否已经存在
$transactionId = $payId;
$transaction = Capsule::table('tblaccounts')
    ->where('transid', $transactionId)
    ->first();

if ($transaction) {
    echo "Transaction already processed.";
    exit;
}

// 获取发票ID
$invoiceId = $param; // param是传递发票ID

// 检索发票以进行处理
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

// 检查金额是否正确
$invoice = Capsule::table('tblinvoices')
    ->where('id', $invoiceId)
    ->first();

if ($price < $invoice->total) {
    logTransaction($gatewayParams['name'], $_GET, 'Invalid Amount'); // 记录事务
    echo "Incorrect amount";
    exit;
}

// 应用发票支付
addInvoicePayment(
    $invoiceId,
    $transactionId,
    $reallyPrice,
    0,
    $gatewayModuleName
);

// 记录事务
logTransaction($gatewayParams['name'], $_GET, 'Successful');

// 回复成功标识
echo "success";
