<?php

class TestAWeberCollectionFind extends PHPUnit_Framework_TestCase {

     public function setUp() {
         $this->adapter = get_mock_adapter();
         $this->subscribers = $this->_getCollection('/accounts/1/lists/303449/subscribers');
         $this->lists = $this->_getCollection('/accounts/1/lists');
         $this->adapter->clearRequests();
     }

     /**
      * Return AWeberCollection
      */
     public function _getCollection($url) {
         $data = $this->adapter->request('GET', $url);
         return new AWeberCollection($data, $url, $this->adapter);
     }

     /**
      * Find That Returns Entries
      */
     public function testFind_ReturnsEntries() {

        $found_subscribers = $this->subscribers->find(array('email' => 'someone@example.com'));

        # Asserts on the API request
        $expected_url = $this->subscribers->url . '?email=someone%40example.com&ws.op=find';
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 2);
        $req = $this->adapter->requestsMade[0];
        $this->assertEquals($req['method'], 'GET');
        $this->assertEquals($req['uri'], $expected_url);

        $req = $this->adapter->requestsMade[1];
        $this->assertEquals($req['method'], 'GET');
        $this->assertEquals($req['uri'], $expected_url . "&ws.show=total_size");

        # Asserts on the returned data
        $this->assertTrue(is_a($found_subscribers, 'AWeberCollection'));
        $this->assertEquals($this->adapter, $found_subscribers->adapter);
        $this->assertEquals($found_subscribers->url, $this->subscribers->url);
        $this->assertEquals($found_subscribers->total_size, 1);
     }

    /**
      * Find That Does Not Return Entries
      */
     public function testFindDoesNot_ReturnsEntries() {

        $found_subscribers = $this->subscribers->find(array('email' => 'nonexist@example.com'));

        # Asserts on the API request
        $expected_url = $this->subscribers->url . '?email=nonexist%40example.com&ws.op=find';
        $this->assertEquals(sizeOf($this->adapter->requestsMade), 2);
        $req = $this->adapter->requestsMade[0];
        $this->assertEquals($req['method'], 'GET');
        $this->assertEquals($req['uri'], $expected_url);

        $req = $this->adapter->requestsMade[1];
        $this->assertEquals($req['method'], 'GET');
        $this->assertEquals($req['uri'], $expected_url . "&ws.show=total_size");

        # Asserts on the returned data
        $this->assertTrue(is_a($found_subscribers, 'AWeberCollection'));
        $this->assertEquals($this->adapter, $found_subscribers->adapter);
        $this->assertEquals($found_subscribers->url, $this->subscribers->url);
        $this->assertEquals($found_subscribers->total_size, 0);
     }

}

class TestAWeberCreateEntry extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->adapter = get_mock_adapter();

        # Get CustomFields
        $url = '/accounts/1/lists/303449/custom_fields';
        $data = $this->adapter->request('GET', $url);
        $this->custom_fields = new AWeberCollection($data, $url, $this->adapter);

    }

    /**
     * Create Succeeded
     */
    public function testCreate_Success() {

         $this->adapter->clearRequests();
         $resp = $this->custom_fields->create(array('name' => 'AwesomeField'));


         $this->assertEquals(sizeOf($this->adapter->requestsMade), 2);

         $req = $this->adapter->requestsMade[0];
         $this->assertEquals($req['method'], 'POST');
         $this->assertEquals($req['uri'], $this->custom_fields->url);
         $this->assertEquals($req['data'], array(
             'ws.op' => 'create',
             'name' => 'AwesomeField'));

         $req = $this->adapter->requestsMade[1];
         $this->assertEquals($req['method'], 'GET');
         $this->assertEquals($req['uri'], '/accounts/1/lists/303449/custom_fields/2');
     }
}

class TestAWeberCollection extends PHPUnit_Framework_TestCase {

    /**
     * Run before each test.  Sets up mock adapter, which uses fixture
     * data for requests, and creates a new collection.
     */
    public function setUp() {
        $this->adapter = get_mock_adapter();
        $this->url = '/accounts/1/lists';
        $data = $this->adapter->request('GET', $this->url);
        $this->collection = new AWeberCollection($data, $this->url, $this->adapter);
    }

    /**
     * Should have the total size available.
     */
    public function testShouldHaveTotalSize() {
        $this->assertEquals($this->collection->total_size, 24);
    }

    /**
     * Should have the URL used to generate this collection
     */
    public function testShouldHaveURL() {
        $this->assertEquals($this->collection->url, $this->url);
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
        $this->assertEquals($entry->id, 1701533);
    }

    public function testShouldKnowItsCollectionType() {
        $this->assertEquals($this->collection->type, 'lists');
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

        $this->assertEquals(sizeof($this->collection->data['entries']), 20);

        $entry = $this->collection[19];
        $this->assertEquals($entry->id, 1424745);
        $this->assertTrue(empty($this->adapter->requestsMade));

        $entry = $this->collection[20];
        $this->assertEquals($entry->id, 1364473);
        $this->assertEquals(count($this->adapter->requestsMade), 1);

        $entry = $this->collection[21];
        $this->assertEquals($entry->id, 1211626);
        $this->assertEquals(count($this->adapter->requestsMade), 1);
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
            $this->assertEquals($index, $count);
            $count++;
        }
        $this->assertEquals($count, $this->collection->total_size);
    }

    /**
     * getById - should return an AWeberEntry with the given id
     */
    public function testShouldAllowGetById() {
        $id = 303449;
        $name = 'default303449';
        $this->adapter->clearRequests();
        $entry = $this->collection->getById($id);

        $this->assertEquals($entry->id, $id);
        $this->assertEquals($entry->name, $name);
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEquals(count($this->adapter->requestsMade), 1);


        $this->assertEquals($this->adapter->requestsMade[0]['uri'],
            '/accounts/1/lists/303449');
    }

    /**
     * Should implement the countable interface, allowing count() and sizeOf()
     * functions to work properly
     */
    public function testShouldAllowCountOperations() {
        $this->assertEquals(count($this->collection), $this->collection->total_size);
        $this->assertEquals(sizeOf($this->collection), $this->collection->total_size);
    }

}

class TestGettingCollectionParentEntry extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->adapter = get_mock_adapter();
        $url = '/accounts/1/lists';
        $data = $this->adapter->request('GET', $url);
        $this->lists = new AWeberCollection($data, $url, $this->adapter);
        $url = '/accounts';
        $data = $this->adapter->request('GET', $url);
        $this->accounts = new AWeberCollection($data, $url, $this->adapter);
        $url = '/accounts/1/lists/303449/custom_fields';
        $data = $this->adapter->request('GET', $url);
        $this->customFields = new AWeberCollection($data, $url, $this->adapter);
    }

    public function testListsParentShouldBeAccount() {
        $entry = $this->lists->getParentEntry();
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEquals($entry->type, 'account');
    }

    public function testCustomFieldsParentShouldBeList() {
        $entry = $this->customFields->getParentEntry();
        $this->assertTrue(is_a($entry, 'AWeberEntry'));
        $this->assertEquals($entry->type, 'list');
    }

    public function testAccountsParentShouldBeNULL() {
        $entry = $this->accounts->getParentEntry();
        $this->assertEquals($entry, NULL);
    }
}
