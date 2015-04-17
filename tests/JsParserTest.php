<?php
require_once "../JsParser.php";

class JsParserTest extends PHPUnit_Framework_TestCase {
    function testParser() {
        $inputStr = '"string"';
        $result = JsParser::parse($inputStr);
        $this->assertEquals("string", $result);
        $this->assertEquals('', $inputStr);

        // arrays
        $result = JsParser::doParse("[]");
        $this->assertEquals(array(), $result);

        $result = JsParser::doParse("[5]");
        $this->assertEquals(array(5), $result);

        $result = JsParser::doParse("[ 5 , 6 ]");
        $this->assertEquals(array(5,6), $result);

        $result = JsParser::doParse("[' 5']");
        $this->assertEquals(array(' 5'), $result);

        $result = JsParser::doParse("['5', \"6\"]");
        $this->assertEquals(array('5','6'), $result);

        $result = JsParser::doParse(" ['5',\"6\" ] ");
        $this->assertEquals(array('5','6'), $result);

        // objects
        $result = JsParser::doParse("{}");
        $this->assertEquals(array(), $result);

        $result = JsParser::doParse("{test:5}");
        $this->assertEquals(array('test' => 5), $result);

        $result = JsParser::doParse('{"test":5}');
        $this->assertEquals(array('test' => 5), $result);

        $result = JsParser::doParse("{test:'string'}");
        $this->assertEquals(array('test' => 'string'), $result);

        $result = JsParser::doParse("{test:[]}");
        $this->assertEquals(array('test' => array()), $result);

        $result = JsParser::doParse("{test:[1,2,3]}");
        $this->assertEquals(array('test' => array(1,2,3)), $result);

        $result = JsParser::doParse('{"test":5, "b":false}');
        $this->assertEquals(array('test' => 5, 'b' => false), $result);

        $result = JsParser::doParse('{test:[{test:1}]}');
        $this->assertEquals(array('test'=>array(array('test'=>1))), $result);

        $result = JsParser::doParse('true');
        $this->assertEquals(true, $result);

        $result = JsParser::doParse('null');
        $this->assertEquals(null, $result);
    }
}
