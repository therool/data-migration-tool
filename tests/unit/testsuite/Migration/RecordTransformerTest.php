<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Migration;

class RecordTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Resource\Document|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sourceDocument;

    /**
     * @var Resource\Document|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $destDocument;

    /**
     * @var MapReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mapReader;

    /**
     * @var Handler\ManagerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $handlerManagerFactory;

    /**
     * @var RecordTransformer
     */
    protected $recordTransformer;

    protected function setUp()
    {
        $this->sourceDocument = $this->getMock('Migration\Resource\Document', ['getStructure'], [], '', false);
        $this->destDocument = $this->getMock('Migration\Resource\Document', ['getStructure'], [], '', false);
        $this->mapReader = $this->getMock('Migration\MapReader', [], [], '', false);
        $this->handlerManagerFactory = $this->getMock(
            'Migration\Handler\ManagerFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->recordTransformer = new RecordTransformer(
            $this->sourceDocument,
            $this->destDocument,
            $this->handlerManagerFactory,
            $this->mapReader
        );
    }

    protected function initHandler($document, $callNumber = 1)
    {
        $handlerManager = $this->getMock('Migration\Handler\Manager', ['initHandler', 'getHandler'], [], '', false);
        $this->handlerManagerFactory->expects($this->at($callNumber))->method('create')->will(
            $this->returnValue($handlerManager)
        );
        $structure = $this->getMock('Migration\Resource\Structure', ['getFields'], [], '', false);
        $document->expects($this->once())->method('getStructure')->will($this->returnValue($structure));
        $fields = ['field1' => '', 'field2' => '', 'field3' => '',];
        $structure->expects($this->once())->method('getFields')->will($this->returnValue($fields));
        $handlerManager->expects($this->any())->method('initHandler');
        return $handlerManager;
    }

    public function testInit()
    {
        $this->initHandler($this->sourceDocument, 0);
        $this->initHandler($this->destDocument, 1);
        $this->recordTransformer->init();
    }

    public function testTransform()
    {
        $srcHandler = $this->initHandler($this->sourceDocument, 0);
        $this->initHandler($this->destDocument, 1);
        $this->recordTransformer->init();

        $recordFrom = $this->getMock('Migration\Resource\Record', [], [], '', false);
        $recordFrom->expects($this->any())->method('getFields')->will($this->returnValue(['field1', 'field2']));
        $recordTo = $this->getMock('Migration\Resource\Record', [], [], '', false);
        $recordTo->expects($this->any())->method('getFields')->will($this->returnValue(['field3']));

        $field2Handler = $this->getMock('Migration\Handler\SetValue', ['handle'], [], '', false);
        $field2Handler->expects($this->once())->method('handle');
        $srcHandler->expects($this->any())->method('getHandler')->will(
            $this->returnValueMap([['field1', null], ['field2', $field2Handler]])
        );
        $this->recordTransformer->transform($recordFrom, $recordTo);
    }
}