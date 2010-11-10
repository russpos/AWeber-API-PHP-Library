<?php

class MockOAuthAdapter extends OAuthApplication {

    public $requestsMade = array();

    protected $requests = array(
        '/accounts'                                => 'accounts',
        '/accounts/1/lists'                        => 'lists',
        '/accounts/910/lists'                      => 'lists',
        '/accounts/1/lists?ws.size=10&ws.start=20' => 'lists_page2',
        '/accounts/1/lists/303449'                 => 'lists/303449',
        '/accounts/910/lists/123456'               => 'error',
        '/accounts/1/lists/303449/campaigns'       => 'lists/303449/campaigns',
    );

    public function addRequest($method, $uri, $data) {
        $this->requestsMade[] = array(
            'method' => $method,
            'uri'    => $uri,
            'data'   => $data);
    }

    public function clearRequests() {
        $this->requestsMade = array();
    }

    public function request($method, $uri, $data=array()) {
        if (!empty($data)) {
            $uri = $uri.'?'. http_build_query($data);
        }
        $this->addRequest($method, $uri, $data);
        $data = MockData::load($this->requests[$uri]);
        $this->parseAsError($data);
        return $data;
    }

}


