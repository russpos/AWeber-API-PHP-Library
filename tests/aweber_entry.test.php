<?php


class TestAWeberEntry extends UnitTestCase {

    /**
     * Before each test, sets up mock adapter to fake requests with fixture
     * data and AWeberEntry based on list 303449
     */
    public function setUp() {
        $this->adapter = new MockOAuthAdapter();

        $url = '/accounts/1/lists/303449';
        $data = $this->adapter->request('GET', $url);
        $this->entry = new AWeberEntry($data, $url, $this->adapter);
    } 

    /**
     * Should be an AWeberEntry
     */
    public function testShouldBeAnAWeberEntry() {
        $this->assertTrue(is_a($this->entry, 'AWeberEntry'));
    }

    /**
     * AWeberEntry should be an AWeberResponse
     */
    public function testShouldBeAnAWeberResponse() {
        $this->assertTrue(is_a($this->entry, 'AWeberResponse'));
    }

    /**
     * Should be able to access the id property (global to all entries)
     */
    public function testShouldBeAbleToAccessId() {
        $this->assertEqual($this->entry->id, 303449);
    }

    /**
     * Should be able to access name (or any property unique to the response)
     */
    public function testShouldBeAbleToAccessName() {
        $this->assertEqual($this->entry->name, 'default303449');
    }

    /**
     * Should be able to discern its type based on its data
     */
    public function testShouldKnowItsType() {
        $this->assertEqual($this->entry->type, 'list');
    }

    /**
     * When access properties it does not have, but are known sub collections,
     * it will request for it and return the new collection object. 
     */
    public function testShouldProvidedCollections() {
        $this->adapter->clearRequests();
        $campaigns = $this->entry->campaigns;

        $this->assertTrue(is_a($campaigns, 'AWeberCollection'));
        $this->assertEqual(count($this->adapter->requestsMade), 1);
        $this->assertEqual($this->adapter->requestsMade[0]['uri'],
            '/accounts/1/lists/303449/campaigns');
    }

    /**
     * When accessing non-implemented children of a resource, should raised
     * a not implemented exception
     */
    public function testShouldThrowExceptionIfNotImplemented() {
        $this->adapter->clearRequests();
        try {
            $obj = $this->entry->something_not_implemented;
            $this->assertFalse(true, "This should not get called due to exception raising.");
        }
        catch (Exception $e) {
            $this->assertTrue(is_a($e, 'AWeberException'));
            $this->assertTrue(is_a($e, 'AWeberResourceNotImplemented'));
        }
        $this->assertEqual(count($this->adapter->requestsMade), 0);
    }

    /**
     * Should return the name of all attributes and collections in this entry
     */
    public function testAttrs() {
        $this->assertEqual($this->entry->attrs(),
            array(
                'id'          => 303449,
                'name'        => 'default303449',
                'campaigns'   => 'collection',
                'subscribers' => 'collection',
                'web_forms'   => 'collection',
                'web_form_split_tests' => 'collection',
            )
        );

    }
}
