<?php

class TestAWeberCollection extends UnitTestCase {

    /**
     * Run before each test.  Sets up mock adapter, which uses fixture
     * data for requests, and creates a new collection.
     */
    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->collection = new AWeberCollection(MockData::load('lists'), '/accounts/1/lists', $this->adapter);
    }

    /**
     * Should have the total size available.
     */
    public function testShouldHaveTotalSize() {
        $this->assertEqual($this->collection->total_size, 24);
    }

    /**
     * Should have the URL used to generate this collection 
     */
    public function testShouldHaveURL() {
        $this->assertEqual($this->collection->url, '/accounts/1/lists');
    }

    /**
     * Should not allow direct access to the entries data retreived from
     * the request.
     */
    public function testShouldNotAccessEntries() {
        $this->assertNull($this->collection->entries);
    }

    /**
     * Should allow entries to be accessed as an array
     */
    public function testShouldAccessEntiresAsArray() {
        $entry = $this->collection[0];
        $this->assertTrue(is_a($entry, 'AWeberResponse'));
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEqual($entry->id, 251847);
    }

    public function testShouldKnowItsCollectionType() {
        $this->assertEqual($this->collection->type, 'lists');
    }

    /**
     * When accessing an offset out of range, should return null
     */
    public function testShouldNotAccessEntriesOutOfRange() {
        $this->assertNull($this->collection[26]);
    }

    /**
     * When accessing entries by offset, should only make a request when
     * accessing entries not in currenlty loaded pages.
     */
    public function testShouldLazilyLoadAdditionalPages() {
        $this->adapter->clearRequests();

        $entry = $this->collection[19];
        $this->assertEqual($entry->id, 50000003);
        $this->assertTrue(empty($this->adapter->requestsMade));

        $entry = $this->collection[20];
        $this->assertEqual($entry->id, 50000002);
        $this->assertEqual(count($this->adapter->requestsMade), 1);

        $entry = $this->collection[21];
        $this->assertEqual($entry->id, 406860);
        $this->assertEqual(count($this->adapter->requestsMade), 1);
    }

    /**
     * Should implement the Iterator interface
     */
    public function testShouldBeAnIterator() {
        $this->assertTrue(is_a($this->collection, 'Iterator'));
    }

    /**
     * When accessed as an iterator, should return entries by offset,
     * from 0 to n-1.
     */
    public function testShouldAllowIteration() {
        $count = 0;
        foreach ($this->collection as $index => $entry) {
            $this->assertEqual($index, $count);
            $count++;
        }
        $this->assertEqual($count, $this->collection->total_size);
    }

    /**
     * getById - should return an AWeberEntry with the given id
     */
    public function testShouldAllowGetById() {
        $id = 303449;
        $name = 'default303449';
        $entry = $this->collection->getById($id);

        $this->assertEqual($entry->id, $id);
        $this->assertEqual($entry->name, $name);
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEqual(count($this->adapter->requestsMade), 1);

        $this->assertEqual($this->adapter->requestsMade[0]['uri'],
            '/accounts/1/lists/303449');
    }

    /**
     * Should implement the countable interface, allowing count() and sizeOf() 
     * functions to work properly
     */
    public function testShouldAllowCountOperations() {
        $this->assertEqual(count($this->collection), $this->collection->total_size);
        $this->assertEqual(sizeOf($this->collection), $this->collection->total_size);
    }

}



?>
