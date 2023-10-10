<?php
// エラーを出力する
ini_set('display_errors', 'On');
ini_set('error_reporting', 32767);
class PDOcontrollerMySQL
{
    //------------
    // 属性
    //------------
    private $pdo;      // DB接続オブジェクト
    // private $error;   // エラーメッセージ(あると親切)

    //------------
    // 操作
    //------------

    public function __construct()
    {
        // データベース接続
        $this->connect();
    }

    /**
     * データベース接続
     *--------------------------
     * 環境によって接続するDB変更
     * ローカル環境　ipアドレス 192.168.33.1
     * dev5環境　ipアドレス 219.111.2.62
     * -------------------------
     */
    protected function connect()
    {
        $host = 'mysql:dbname=m_yoshizawa;host=localhost;charset=utf8';
        $username = 'root';
        $passwd = '';
        // 環境によって接続するデータベース変更
        if ($_SERVER['REMOTE_ADDR'] == '192.168.33.1') {
            // ローカル環境　ipアドレス 192.168.33.1
            $passwd = '{データベースパスワード}';
        }

        // データベースに接続
        try {
            $this->pdo = new PDO($host, $username, $passwd, [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * データ抽出
     *
     * @param [type] $sql
     * @return void
     */
    public function query($sql)
    {
        try {
            $stmt = $this->pdo->prepare($sql);     //sql文を実行する準備を行う。戻り値はPDOStatement
            $stmt->execute(); //実行
            $result = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log(print_r($e, true), "3", "debug.log");
            exit;
        }

        return $result;
    }

    /**
     * Undocumented function
     *
     * @param [type] $sql
     * @return void
     */
    public function query_fetchColumn($sql)
    {
        try {
            $csth = $this->pdo->prepare($sql);
            $csth->execute();
            $total = $csth->fetchColumn();
        } catch (PDOException $e) {
            echo ($e);
            exit;
        }
        return $total;
    }

    public function insert($chatwork_messag, $chatgpt_message)
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO conversation_history (`id`, `user_content`,`assistant_content`) VALUES(NULL,:user_content,:assistant_content)');
            $stmt->bindValue(':user_content', $chatwork_messag);
            $stmt->bindValue(':assistant_content', $chatgpt_message);
            // / SQL実行
            $stmt->execute();
            $stmt->commit();
        } catch (PDOException $e) {
            // $data = $e;
            error_log(print_r($e, true), "3", "debug.log");
            return $e;
        }
        return;
    }
}
