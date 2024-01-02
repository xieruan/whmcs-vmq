<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function vmq_MetaData()
{
    return array(
        'DisplayName' => 'VMQ Payment Gateway',
        'APIVersion' => '1.1',
    );
}
function vmq_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'VMQ Payment Gateway',
        ),
        'url' => array(
            'FriendlyName' => 'URL',
            'Type' => 'text',
            'Size' => '60',
            'Default' => '',
            'Description' => 'Gateway URL',
        ),
        'key' => array(
            'FriendlyName' => 'Key',
            'Type' => 'text',
            'Size' => '60',
            'Default' => '',
            'Description' => 'Communication key',
        ),
        'paytype' => array(
            'FriendlyName' => 'Default Pay Type',
            'Type' => 'dropdown',
            'Options' => array(
                '1' => '微信支付',
                '2' => '支付宝支付',
            ),
            'Description' => '默认支付方式，用户可以在支付页面选择其他方式',
        ),
        'paytype_selection_enabled' => array(
            'FriendlyName' => 'Enable Pay Type Selection',
            'Type' => 'yesno',
            'Description' => '启用后，用户将能够选择支付方式。如果禁用，则使用默认支付方式且不显示选择框。',
        ),
    );
}

function vmq_link($params)
{
    // 定义服务器和端点URLs
    $createOrderUrl = $params['url'] . '/createOrder';
    
    // 从$params中检索必要参数
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount'];

    // 使用发票ID和时间戳生成一个唯一的payId
    $payId = md5($invoiceId . time());

    // 根据用户选择或默认值确定支付类型（1代表微信，2代表支付宝）
    $type = isset($_POST['vmq_paytype']) ? $_POST['vmq_paytype'] : $params['paytype'];

    // 构建签名
    $signString = $payId . $invoiceId . $type . $amount . $params['key'];
    $sign = md5($signString);

    // 准备其他参数
    $isHtml = 1; // 设置为1自动重定向到支付页面，0为JSON响应
    $notifyUrl = $params['systemurl'] . '/modules/gateways/vmq/notify.php';
    $returnUrl = $params['systemurl'] . '/viewinvoice.php?id=' . $invoiceId;

    // 检查是否启用了支付类型选择并且请求是POST
    if ($params['paytype_selection_enabled'] == 'on' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vmq_paytype'])) {
        // 重定向到支付页面或采取其他适当的行动
        $query = http_build_query([
            'payId' => $payId,
            'type' => $type,
            'price' => $amount,
            'sign' => $sign,
            'param' => $invoiceId,
            'isHtml' => $isHtml,
            'notifyUrl' => $notifyUrl,
            'returnUrl' => $returnUrl
        ]);

        header('Location: ' . $createOrderUrl . '?' . $query);
        exit;
    } elseif ($params['paytype_selection_enabled'] == 'on') {
        // 显示改进样式的支付选择表单
        $htmlOutput = '<form method="post" action="">';
        $htmlOutput .= '<select name="vmq_paytype" style="padding:10px; margin-bottom:10px;">';
        $htmlOutput .= '<option value="1">微信支付</option>';
        $htmlOutput .= '<option value="2">支付宝支付</option>';
        $htmlOutput .= '</select><br>';
        $htmlOutput .= '<input type="submit" value="' . $params['langpaynow'] . '" style="padding:10px; color:white; background-color:green; border:none; cursor:pointer;">';
        $htmlOutput .= '</form>';

        return $htmlOutput;
    } else {
        // 当支付类型选择被禁用时，直接使用默认的支付方式继续
        $query = http_build_query([
            'payId' => $payId,
            'type' => $params['paytype'], // 默认类型
            'price' => $amount,
            'sign' => $sign,
            'param' => $invoiceId,
            'isHtml' => $isHtml,
            'notifyUrl' => $notifyUrl,
            'returnUrl' => $returnUrl
        ]);

        header('Location: ' . $createOrderUrl . '?' . $query);
        exit;
    }
}
