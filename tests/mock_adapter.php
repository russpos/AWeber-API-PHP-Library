<?php

class MockOAuthAdapter extends OAuthApplication {

    public $requestsMade = array();

    protected $requests = array(
        'GET' => array(
            '/accounts'                                => 'accounts',
            '/accounts/1'                              => 'account',
            '/accounts/1/lists'                        => 'lists',
            '/accounts/1?ws.op=getWebForms'            => 'web_forms',
            '/accounts/910/lists'                      => 'lists',
            '/accounts/1/lists?ws.size=20&ws.start=20' => 'lists_page2',
            '/accounts/1/lists/303449'                 => 'lists/303449',
            '/accounts/1/lists/303450'                 => 'lists/303450',
            '/accounts/910/lists/123456'               => 'error',
            '/accounts/1/lists/303449/campaigns'       => 'lists/303449/campaigns',
            '/accounts/1/lists/1/subscribers/1'        => 'subscribers/1',
        ),
        'DELETE' => array(
            '/accounts/1/lists/303449'                 => '200',
            '/accounts/1'                              => '404',
        ),
        'PATCH' => array(
            '/accounts/1/lists/303449'                 => '209',
            '/accounts/1/lists/303450'                 => '404',
            '/accounts/1/lists/1/subscribers/1'        => '209',
        )
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

    public function request($method, $uri, $data=array(), $options=array()) {
        if ($method == 'GET' && !empty($data)) {
            $uri = $uri.'?'. http_build_query($data);
        }
        $this->addRequest($method, $uri, $data);
        if (!empty($options['return']) && $options['return'] == 'status') {
            return $this->requests[$method][$uri];
        }
        $data = MockData::load($this->requests[$method][$uri]);
        $this->parseAsError($data);
        return $data;
    }

}


