<?php

class MockOAuthAdapter extends OAuthApplication {

    public $requestsMade = array();

    # TODO: make this consistant
    protected $requests = array(
        'GET' => array(
            # the new way
            '/accounts'                                => 'accounts/page1',
            '/accounts/1'                              => 'accounts/1',
            '/accounts/1?ws.op=getWebForms'            => 'accounts/webForms',
            '/accounts/1/lists'                        => 'lists/page1',
            '/accounts/1/lists?ws.size=20&ws.start=20' => 'lists/page2',
            '/accounts/1/lists/303449'                 => 'lists/303449',
            '/accounts/1/lists/505454'                 => 'lists/505454',
            '/accounts/1/lists/303449/campaigns'       => 'campaigns/303449',
            '/accounts/1/lists/303449/subscribers'     => 'subscribers/page1',
            '/accounts/1/lists/303449/subscribers/1'   => 'subscribers/1',
            '/accounts/1/lists/303449/subscribers/2'   => 'subscribers/2',
            '/accounts/1/lists/505454/subscribers/3'   => 'subscribers/3',
            '/accounts/1/lists/303449/subscribers?email=someone%40example.com&ws.op=find' => 'subscribers/find',
            '/accounts/1/lists/303449/subscribers?email=someone%40example.com&ws.op=find&ws.show=total_size' => 'subscribers/find_tsl',
        ),
        'DELETE' => array(
            '/accounts/1/lists/303449'                 => '200',
            '/accounts/1'                              => '404',
        ),
        'PATCH' => array(
            '/accounts/1/lists/303449'                 => '209',
            '/accounts/1/lists/303450'                 => '404',
            '/accounts/1/lists/303449/subscribers/1'   => '209',
        ),
        'POST' => array(
            '/accounts/1/lists/303449/subscribers/1' => Array(
                'Status-Code' => '201',
                'Location' => '/accounts/1/lists/505454/subscribers/3',
            ),
            '/accounts/1/lists/303449/subscribers/2' => Array(
                'Status-Code' => '400',
            ),
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

        if (!empty($options['return'])) {
            if ($options['return'] == 'status') {
                return $this->requests[$method][$uri];
            }
            if ($options['return'] == 'headers') {
                return $this->requests[$method][$uri];
            }
        }

        $data = MockData::load($this->requests[$method][$uri]);
        $this->parseAsError($data);
        return $data;
    }

}


