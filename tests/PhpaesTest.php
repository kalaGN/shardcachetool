<?php
/**
 * Created by Afei.
 * User: feiwang
 * Date: 2019/6/16
 * Time: 17:04
 */

require_once 'vendor/autoload.php';
use Infobird\Tool\Phpaes;
use PHPUnit\Framework\TestCase;
class PhpaesTest extends TestCase
{

    protected static $aes;

    public static function setUpBeforeClass()
    {
        self::$aes = new Phpaes('123456');
    }

    public static function tearDownAfterClass()
    {
        self::$aes = null;
    }

    public function testEncrypt(){

        $enkey = 'GdKIL/Z9IAENoxJncwAdPQ==';
        $this->assertEquals($enkey,self::$aes->encrypt('123456qwe'));

    }

    public function testDecrypt(){

        $enkey = '123456qwe';
        $this->assertEquals($enkey,self::$aes->decrypt('GdKIL/Z9IAENoxJncwAdPQ=='));

    }
}