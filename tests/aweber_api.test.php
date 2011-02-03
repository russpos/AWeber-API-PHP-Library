<?php

class TestBase extends UnitTestCase {

    public $app = array(
        'key'    => 'RogsGzUw3QAK6cPSI24u',
        'secret' => '1eaHAFJnEklS8qSBvitvSO6OCkaU4QyHU3AOE1rw',
    );

    public $user = array(
        'token'  => 'lc0UcVJdlpNyVVMLzeZWZZGb61pEnlhBdHGg9usF',
        'secret' => 'VMus5FW1TyX7N24xaOyc0VsylGBHC6rAomq3LM67',
    );


}

class TestAWeberAPI extends TestBase {

    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->aweber = new AWeberAPI($this->app['key'],
            $this->app['secret']);

    }

    /**
     * App keys given at construction should be maintained internally
     */
    public function test_should_contain_app_keys() {
        $this->assertEqual($this->aweber->consumerKey,
                           $this->app['key']);
        $this->assertEqual($this->aweber->consumerSecret,
                           $this->app['secret']);

    }

    /**
     * OAuther adapter object should be allowed to be switched out
     */
    public function test_should_allow_setting_oauth_adapter() {
        $this->aweber->setAdapter($this->adapter);
        $this->assertEqual($this->aweber->adapter, $this->adapter);
    }

    /**
     * When authorization fails, an exception is raised
     */
    public function test_should_raise_exception_if_auth_fails() {
        MockData::$oauth = false;
        $this->aweber->setAdapter($this->adapter);
        try {
            $account = $this->aweber->getAccount($this->user['token'],
                $this->user['secret']);
            $this->assertTrue(false, 'This should not run due to an exception');
        }
        catch (Exception $e) {
            $this->assertTrue(is_a($e, 'Exception'));
        }
        MockData::$oauth = true;
    }

    public function test_should_return_null_after_authorization() {
        $this->aweber->setAdapter($this->adapter);
        $account = $this->aweber->getAccount($this->user['token'],
            $this->user['secret']);
        $list = $account->lists->getById(123456);
        $this->assertTrue(empty($list));
    }

    /**
     * getAccount should load an AWeberEntry based on a single account
     * for the authorized user
     */
    public function test_getAccount() {
        $this->aweber->setAdapter($this->adapter);
        $account = $this->aweber->getAccount($this->user['token'],
            $this->user['secret']);
        $this->assertNotNull($account);
        $this->assertTrue(is_a($account, 'AWeberResponse'));
        $this->assertTrue(is_a($account, 'AWeberEntry'));
    }

    /**
     * Load from URL should take a relative URL and return the correct
     * object based on that request. Allows skipping around the tree
     * based on URLs, not just walking it.
     */
    public function test_loadFromUrl() {
        $this->aweber->setAdapter($this->adapter);
        $list = $this->aweber->loadFromUrl('/accounts/1/lists/303449');

        $this->assertTrue(is_a($list, 'AWeberEntry'));
        $this->assertEqual($list->type, 'list');
        $this->assertEqual($list->id, '303449');
    }

    /**
     * Assert that lazy mode is not the default behavior
     */
    public function test_should_not_be_lazy() {
        $this->assertFalse($this->aweber->lazy);
    }

}

/**
 * TestLazyAWeberAPI
 *
 * Verifies "lazy" loading functionality.
 * @uses UnitTestCase
 * @package
 * @version $id$
 */
class TestLazyAWeberAPI extends TestBase {

    public function setUp() {
        $this->adapter = new MockOAuthAdapter();
        $this->app = array(
            'key'    => 'RogsGzUw3QAK6cPSI24u',
            'secret' => '1eaHAFJnEklS8qSBvitvSO6OCkaU4QyHU3AOE1rw',
        );
        $this->aweber = new AWeberAPI($this->app['key'],
            $this->app['secret'], array('lazy' => true));
        $this->aweber->setAdapter($this->adapter);
        $this->account = $this->aweber->getAccount($this->user['token'],
            $this->user['secret']);
    }

    /**
     * Laziness should be passed on to child attributes
     */
    public function test_should_be_lazy() {
        $this->assertTrue($this->aweber->lazy);
    }

    /**
     * Loading an account cannot be done lazily, as it verifies
     * user status and gets the account id
     */
    public function test_account_was_not_lazy_loaded() {
        $this->assertTrue($this->account);
        $this->assertFalse(empty($this->account->data));
    }

    /**
     * Fetching a child collection of the account object does not
     * make a request. The collection object has no data, but
     * represents the URL of the correct child collection.
     */
    public function test_lists_is_lazy_loaded() {
        $this->adapter->requestsMade = array();
        $lists = $this->account->lists;
        $this->assertTrue($lists);
        $this->assertTrue(is_a($lists, 'AWeberCollection'));
        $this->assertEqual($lists->url, '/accounts/910/lists');
        $this->assertTrue(empty($lists->data));
        $this->assertTrue($lists->lazy);
        $this->assertTrue(empty($this->adapter->requestsMade));
    }

    /**
     * Accessing a property on a lazily loaded collection causes
     * the property to load itself prior to returning the value
     * of the property.
     */
    public function test_list_size_causes_loading() {
        $this->adapter->requestsMade = array();
        $lists = $this->account->lists;
        $this->assertTrue(empty($this->adapter->requestsMade));
        $size = $lists->total_size;
        $this->assertEqual(count($this->adapter->requestsMade), 1);
        $this->assertEqual($size, 24);
    }

    /**
     * Accessing a collection object as a list (using any 
     * of its offsets), causes loading and returns the 
     * child entry. Child entry inherist laziness.
     */
    public function test_list_access_causes_loading() {
        $this->adapter->requestsMade = array();
        $lists = $this->account->lists;
        $this->assertTrue(empty($this->adapter->requestsMade));
        $list = $lists[0];
        $this->assertEqual(count($this->adapter->requestsMade), 1);
        $this->assertTrue($list->lazy);
        $this->assertEqual($list->name, 'default251847');
    }

    /**
     * Accessing a child object via the getById method does
     * not cause loading, as the URL can be infered by the 
     * ID.  Child entry object has no data, makes no requests,
     * and is aware of its representing URL. Laziness is 
     * inherited.
     */
    public function test_get_by_id_defers_loading() {
        $this->adapter->requestsMade = array();
        $lists = $this->account->lists;
        $this->assertTrue(empty($this->adapter->requestsMade));
        $this->assertTrue($lists->lazy);
        $list = $lists->getById('251847');
        $this->assertTrue(is_a($list, 'AWeberEntry'));
        $this->assertEqual(count($this->adapter->requestsMade), 0);
        $this->assertEqual($list->url, '/accounts/910/lists/251847');
    }
}
?>
