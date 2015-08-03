<?php

namespace Jh\DataImportMagentoTest\Exception;

use Jh\DataImportMagento\Exception\AttributeNotExistException;

/**
 * Class AttributeNotExistExceptionTest
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class AttributeNotExistExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $e = new AttributeNotExistException('some_attribute');
        $this->assertSame('Attribute with code: "some_attribute" does not exist', $e->getMessage());
        $this->assertSame(0, $e->getCode());
    }
}
