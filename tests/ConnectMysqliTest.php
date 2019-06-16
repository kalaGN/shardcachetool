<?php
/**
 * Created by Afei.
 * User: feiwang
 * Date: 2019-06-16
 * Time: 19:20
 */
use Infobird\Tool\ConnectMysqli;
class ConnectMysqliTest extends \PHPUnit\Framework\TestCase
{

    protected $conn;
    protected static $pdo;

    public function setUp()
    {
        $xml = simplexml_load_file('phpunit.xml');
        //$this->expectOutputString('foo');
        var_dump($xml);
        if ($this->conn === null) {
            if (self::$pdo == null) {
                self::$pdo = mysqli_connect($this->host,$this->user,$this->pass,$this->db,$this->port);
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
        }

        return $this->conn;
    }

    public function tearDown()
    {
        $this->conn ='';
    }

    public function testGetIntance(){

    }


}