<?php

namespace Fwk\Db;

/**
 * Test class for Table.
 * Generated by PHPUnit on 2012-05-27 at 15:10:12.
 */
class TableTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Table
     */
    protected $object;
    
    /**
     *
     * @var Connection
     */
    protected $connection;
 
    public function __construct() {
        $schema = require __DIR__ .'/resources/testDatabaseSchema.php';
        $driver = new Testing\Driver();
        
        $this->connection = new Connection($driver, $schema, array(
            
        ));
    }
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new Table('testTable');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        unset($this->object);
    }

    /**
     */
    public function testAddColumn() {
        $this->assertEquals($this->object, $this->object->addColumn(new Columns\TextColumn('test', 'varchar')));
        $this->setExpectedException('\Fwk\Db\Exceptions\DuplicateTableColumn');
        $this->object->addColumn(new Columns\TextColumn('test', 'varchar'));
    }

    public function testAddColumns()
    {
        $this->object->addColumns(array(
            new Columns\NumericColumn('id', 'integer', 11, false, null, Column::INDEX_PRIMARY, true),
            new Columns\TextColumn('test', 'varchar', 255, false, null),
            new Columns\TextColumn('test_null', 'varchar', 50, true, null)
        ));

        $this->assertTrue($this->object->hasColumn('id'));
        $this->assertTrue($this->object->hasColumn('test'));
        $this->assertTrue($this->object->hasColumn('test_null'));
    }
    /**
     */
    public function testGetColumns() {
        $this->assertEquals(array(), $this->object->getColumns());
        $this->object->addColumn(new Columns\TextColumn('test', 'varchar'));
        $this->assertEquals(1, count($this->object->getColumns()));
    }

    /**
     */
    public function testSetConnection() {
        $this->assertEquals($this->object, $this->object->setConnection($this->connection));
    }

    /**
     */
    public function testGetConnectionFail() {
        $this->setExpectedException("\Fwk\Db\Exception");
        $this->object->getConnection();
    }
    
    /**
     */
    public function testGetConnection()
    {
        $this->object->setConnection($this->connection);
        $this->assertEquals($this->connection, $this->object->getConnection());
    }

    public function testGetFinder()
    {
        $this->assertInstanceOf('\Fwk\Db\Finder', $this->object->finder());
    }
    
    /**
     */
    public function testGetName()
    {
        $this->assertEquals('testTable', $this->object->getName()); 
    }

    /**
     */
    public function testGetIdentifiersKeysFail() {
        $this->setExpectedException('\Fwk\Db\Exceptions\TableLacksIdentifiers');
        $this->object->getIdentifiersKeys();
    }

    public function testGetIdentifiersKeys()
    {
        $this->object->addColumns(array(
            new Columns\NumericColumn('id', 'integer', 11, false, null, Column::INDEX_PRIMARY, true),
            new Columns\TextColumn('test', 'varchar', 255, false, null),
            new Columns\TextColumn('test_null', 'varchar', 50, true, null)
        ));

        $this->assertEquals(array('id'), $this->object->getIdentifiersKeys());
    }
    
    /**
     */
    public function testGetRegistry() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     */
    public function testGetColumnFail()
    {
        $this->setExpectedException('\Fwk\Db\Exceptions\TableColumnNotFound');
        $this->object->getColumn('test');
    }

    public function testGetColumn()
    {
        $col = new Columns\TextColumn('test', 'varchar');
        $this->object->addColumn($col);
        $this->assertEquals($col, $this->object->getColumn('test'));
    }
    
    /**
     */
    public function testHasColumn()
    {
        $this->assertFalse($this->object->hasColumn('test'));
        $col = new Columns\TextColumn('test', 'varchar');
        $this->object->addColumn($col);
        $this->assertTrue($this->object->hasColumn('test'));
    }

    /**
     */
    public function testDefaultEntity() {
        $this->object->setConnection($this->connection);
        $this->assertEquals('\stdClass', $this->object->getDefaultEntity());
        $this->object->setDefaultEntity('\MyTestEntity');
        $this->assertEquals('\MyTestEntity', $this->object->getDefaultEntity());
    }

    /**
     * @covers {className}::{origMethodName}
     * @todo Implement testSave().
     */
    public function testSave() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers {className}::{origMethodName}
     * @todo Implement testDelete().
     */
    public function testDelete() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    /**
     * @covers {className}::{origMethodName}
     * @todo Implement testDeleteAll().
     */
    public function testDeleteAll() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

}