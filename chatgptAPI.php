<?php

ini_set('display_errors', 'On');
ini_set('error_reporting', 32767);

$raw = file_get_contents('php://input');

$receive = json_decode($raw, true);
// return $receive;
// チャットワークからのメッセージ受信
$chatwork_message = $receive['webhook_event']['body'];

// Chatwork APIトークン
$token = 'Chatwork APIトークン 数字を入れてください。';
// Chatwork ルームID
$room =  $receive['webhook_event']['room_id'];
// 送信者id
$account_id = $receive['webhook_event']['account_id'];


// if ($account_id != 6318280) {
if (strpos($chatwork_message, '@chatgpt') !== false) {

    $table_name = call_chatGPT($chatwork_message, $conversation_history);
    $conversation_history = get_table_name($table_name, $chatwork_message);

    //指定した文字列が一致したら置き換える
    $chatwork_message = str_replace('@chatgpt', '', $chatwork_message);

    //'abcd'のなかに'bc'が含まれている場合
    $chatgpt_message = _call_chatGPT($chatwork_message, $conversation_history);

    // get_table_name($chatgpt_message);

    // チャットワークに返信送信
    send_Chatwork($token, $room, $chatgpt_message);
    // 会話履歴保存
    // message_save($chatwork_message, $chatgpt_message);
}

function message_save($chatwork_messag, $chatgpt_message)
{
    require_once 'PDOcontrollerMySQLclass.php';
    $my_pdo = new PDOcontrollerMySQL();

    try {
        $my_pdo->insert($chatwork_messag, $chatgpt_message);
    } catch (PDOException $e) {
        error_log(print_r($e, true), "3", "debug.log");
    }
    return;
}

// チャットワークに返信
function send_Chatwork($token, $room, $message)
{
    $message =  "[info][title]チャットGPTからの返信[/title]{$message}[/info]";
    $reply = array('body' => $message);

    $ch = curl_init();
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        array('X-ChatWorkToken: ' . $token)
    );
    curl_setopt($ch, CURLOPT_URL, "https://api.chatwork.com/v2/rooms/" . $room . "/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($reply, '', '&'));
    curl_exec($ch);
    curl_close($ch);
}

// OpenAPI呼び出し
function _call_chatGPT($userMessage, $conversation_history)
{
    // セキュリティを万全にするならapi keyの管理は工夫したほうがいいかも？
    $OPENAI_API_KEY  = "{openapi_key}";

    $ch = curl_init();
    $headers  = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENAI_API_KEY
    ];

    $postData = [
        'model' => "gpt-3.5-turbo",
        'messages' => [
            // 回答にぶれが出ないように、マイティークラフトのアシスタントであることを明記
            ["role" => "system", "content" => "You are an excellent assistant for Mighty Crafts, Inc."],
            // 会話履歴
            $conversation_history[0],
            // 質問内容
            ["role" => "user", "content" => $userMessage],
        ],
        'max_tokens' => 1000,
    ];

    // データを送信
    try {
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    } catch (Exception $e) {
        error_log(print_r(curl_exec($ch), true), "3", "debug.log");
        error_log(print_r($e, true), "3", "debug.log");
    }

    // 成功した場合はメッセージを失敗した場合はfalseを返す
    // error_log(print_r(curl_exec($ch), true), "3", "debug.log");
    $result = curl_exec($ch);

    if ($result === false) {
        return false;
    }

    $decoded_json = json_decode($result, true);
    return $decoded_json["choices"][0]["message"]["content"];
}

// OpenAPI呼び出し
function call_chatGPT($userMessage)
{
    // セキュリティを万全にするならapi keyの管理は工夫したほうがいいかも？
    $OPENAI_API_KEY  = "{openapi_key}";

    $ch = curl_init();
    $headers  = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENAI_API_KEY
    ];

    $postData = [
        'model' => "gpt-3.5-turbo",
        'messages' => [
            // 回答にぶれが出ないように、マイティークラフトのアシスタントであることを明記
            ["role" => "system", "content" => "*命令文
            =||
            You answer only the table name. You will not talk about anything else.
            
            #Conversation rules and settings
            *leaveテーブルは“有給休暇申請“についてのテーブルです。
            *qualification_rewardテーブルは“資格手当“についてのテーブルです。
            *book_applicationテーブルは“書籍費用申請”についてのテーブルです。
            *質問をするので、どのテーブルが適切か答えます。
            *回答はテーブル名のみ

            #Examples response:
            *leave
            *qualification_reward
            *book_application"],
            // 質問内容
            ["role" => "user", "content" => $userMessage],
        ],
        'max_tokens' => 100,
    ];


    // データを送信
    try {
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    } catch (Exception $e) {
        error_log(print_r(curl_exec($ch), true), "3", "debug.log");
        error_log(print_r($e, true), "3", "debug.log");
    }

    // 成功した場合はメッセージを失敗した場合はfalseを返す
    // error_log(print_r(curl_exec($ch), true), "3", "debug.log");
    $result = curl_exec($ch);

    if ($result === false) {
        return false;
    }

    $decoded_json = json_decode($result, true);
    return $decoded_json["choices"][0]["message"]["content"];
}

function get_conversation_history()
{
    require_once 'PDOcontrollerMySQLclass.php';
    $my_pdo = new PDOcontrollerMySQL();
    // $sql = "SELECT * FROM `conversation_history`";
    $sql = "SELECT * FROM `leave`";
    // $sql = "SELECT * FROM `qualification_reward`";
    $conversation_history = $my_pdo->query($sql);

    $conversation_history_array = array();
    foreach ($conversation_history as $value) {
        $conversation_history_array[] = ["role" => "user", "content" => $value['user_content']];
        $conversation_history_array[] = ["role" => "assistant", "content" => $value['assistant_content']];
    }
    return  $conversation_history_array;
}

function get_table_name($table_name, $chatwork_message)
{
    require_once 'PDOcontrollerMySQLclass.php';
    $my_pdo = new PDOcontrollerMySQL();
    error_log(print_r("こんにちは", true), "3", "debug.log");
    error_log(print_r($table_name, true), "3", "debug.log");
    $sql = "SELECT * FROM `conversation_history`";

    // テーブル識別
    if (false !== strpos($table_name, 'book_application')) {
        $sql = "SELECT * FROM `book_application`";
    }
    if (false !== strpos($table_name, 'leave')) {
        $sql = "SELECT * FROM `leave`";
    }
    if (false !== strpos($table_name, 'qualification_reward')) {
        $sql = "SELECT * FROM `qualification_reward`";
    }

    // if (false !== strpos($sql, 'conversation_history')) {
    //     // 会話履歴保存
    //     message_save($chatwork_message, $table_name);
    // }
    // error_log(print_r("こんにちは", true), "3", "debug.log");
    error_log(print_r($sql, true), "3", "debug.log");

    // $sql = "SELECT * FROM `qualification_reward`";
    $conversation_history = $my_pdo->query($sql);


    $conversation_history_array = array();
    foreach ($conversation_history as $value) {
        $conversation_history_array[] = ["role" => "user", "content" => $value['user_content']];
        $conversation_history_array[] = ["role" => "assistant", "content" => $value['assistant_content']];
    }
    return  $conversation_history_array;
}
