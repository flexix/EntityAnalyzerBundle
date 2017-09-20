<?php

namespace Flexix\EntityAnalyzerBundle\Tests\Util;

use \PHPUnit\Framework\TestCase;
use Flexix\EntityAnalyzerBundle\Util\EntityAnalyzer;

/**
 * Description of EntityAnalyzer
 *
 * @author Mariusz Piela <mariuszpiela@tmsolution.pl>
 */
class EntityAnalyzerTest extends TestCase {

    static $file = 'Scriptelement.orm.yml';
    static $entityAnalyzer;

    public static function setupBeforeClass() {
        self::$entityAnalyzer = new EntityAnalyzer(__DIR__ . DIRECTORY_SEPARATOR . self::$file,'AppBundle\Entity\Scriptelement');
    }

    public function testGetFilterFields() {
        $this->assertNotEmpty(self::$entityAnalyzer->getFilterFields());
    }

    public function testGetInsertFormFields() {
         $this->assertNotEmpty(self::$entityAnalyzer->getInsertFormFields());
    }

    public function testGetEditFormFields() {
         $this->assertNotEmpty(self::$entityAnalyzer->getEditFormFields());
    }

    public function testGetSearchFormFields() {
         $this->assertNotEmpty(self::$entityAnalyzer->getSearchFormFields());
    }

    public function testGetListFields() {
         $this->assertNotEmpty(self::$entityAnalyzer->getListFields());
    }
    
     public function testGetGetFields() {
         $this->assertNotEmpty(self::$entityAnalyzer->getGetFields());
    }

}
