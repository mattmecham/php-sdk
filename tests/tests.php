<?php

/**
 * @owner naitik
 * @emails naitik@facebook.com, platform-tests@lists.facebook.com
 */

class FacebookTest extends PHPUnit_Framework_TestCase
{
  const APP_ID = '254752073152';
  const SECRET = '904270b68a2cc3d54485323652da4d14';

  private static $VALID_EXPIRED_SESSION = array(
    'access_token' => '254752073152|2.I_eTFkcTKSzX5no3jI4r1Q__.3600.1273359600-1677846385|uI7GwrmBUed8seZZ05JbdzGFUpk.',
    'expires'      => '1273359600',
    'secret'       => '0d9F7pxWjM_QakY_51VZqw__',
    'session_key'  => '2.I_eTFkcTKSzX5no3jI4r1Q__.3600.1273359600-1677846385',
    'sig'          => '9f6ae89510b30dddb3f864f3caf32fb3',
    'uid'          => '1677846385',
  );

  private static $VALID_SIGNED_REQUEST = 'ZcZocIFknCpcTLhwsRwwH5nL6oq7OmKWJx41xRTi59E.eyJhbGdvcml0aG0iOiJITUFDLVNIQTI1NiIsImV4cGlyZXMiOiIxMjczMzU5NjAwIiwib2F1dGhfdG9rZW4iOiIyNTQ3NTIwNzMxNTJ8Mi5JX2VURmtjVEtTelg1bm8zakk0cjFRX18uMzYwMC4xMjczMzU5NjAwLTE2Nzc4NDYzODV8dUk3R3dybUJVZWQ4c2VaWjA1SmJkekdGVXBrLiIsInNlc3Npb25fa2V5IjoiMi5JX2VURmtjVEtTelg1bm8zakk0cjFRX18uMzYwMC4xMjczMzU5NjAwLTE2Nzc4NDYzODUiLCJ1c2VyX2lkIjoiMTY3Nzg0NjM4NSJ9';
  private static $NON_TOSSED_SIGNED_REQUEST = 'laEjO-az9kzgFOUldy1G7EyaP6tMQEsbFIDrB1RUamE.eyJhbGdvcml0aG0iOiJITUFDLVNIQTI1NiJ9';

  public function testConstructor() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $this->assertEquals($facebook->getAppId(), self::APP_ID,
                        'Expect the App ID to be set.');
    $this->assertEquals($facebook->getApiSecret(), self::SECRET,
                        'Expect the API secret to be set.');
    $this->assertFalse($facebook->useCookieSupport(),
                       'Expect Cookie support to be off.');
  }

  public function testConstructorWithCookie() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'cookie' => true,
    ));
    $this->assertEquals($facebook->getAppId(), self::APP_ID,
                        'Expect the App ID to be set.');
    $this->assertEquals($facebook->getApiSecret(), self::SECRET,
                        'Expect the API secret to be set.');
    $this->assertTrue($facebook->useCookieSupport(),
                      'Expect Cookie support to be on.');
  }

  public function testSetAppId() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $facebook->setAppId('dummy');
    $this->assertEquals($facebook->getAppId(), 'dummy',
                        'Expect the App ID to be dummy.');
  }

  public function testSetAPISecret() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $facebook->setApiSecret('dummy');
    $this->assertEquals($facebook->getApiSecret(), 'dummy',
                        'Expect the API secret to be dummy.');
  }

  public function testDefaultBaseDomain() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'domain' => 'fbrell.com',
    ));
    $this->assertEquals($facebook->getBaseDomain(), 'fbrell.com');
  }

  public function testSetCookieSupport() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $this->assertFalse($facebook->useCookieSupport(),
                       'Expect Cookie support to be off.');
    $facebook->setCookieSupport(true);
    $this->assertTrue($facebook->useCookieSupport(),
                      'Expect Cookie support to be on.');
  }

  public function testIgnoreDeleteSetCookie() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'cookie' => true,
    ));
    $cookieName = 'fbs_' . self::APP_ID;
    $this->assertFalse(isset($_COOKIE[$cookieName]),
                       'Expect Cookie to not exist.');
    $facebook->setSession(null);
    $this->assertFalse(isset($_COOKIE[$cookieName]),
                       'Expect Cookie to not exist.');
  }

  public function testSetNullSession() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $facebook->setSession(null);
    $this->assertTrue($facebook->getSession() === null,
                      'Expect null session back.');
  }

  public function testNonUserAccessToken() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'cookie' => true,
    ));
    $this->assertTrue($facebook->getAccessToken() ==
                      self::APP_ID.'|'.self::SECRET,
                      'Expect appId|secret.');
  }

  public function testSetSession() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'cookie' => true,
    ));
    $facebook->setSession(self::$VALID_EXPIRED_SESSION);
    $this->assertTrue($facebook->getUser() ==
                      self::$VALID_EXPIRED_SESSION['uid'],
                      'Expect uid back.');
    $this->assertTrue($facebook->getAccessToken() ==
                      self::$VALID_EXPIRED_SESSION['access_token'],
                      'Expect access token back.');
  }

  public function testGetSessionFromCookie() {
    $cookieName = 'fbs_' . self::APP_ID;
    $session = self::$VALID_EXPIRED_SESSION;
    $_COOKIE[$cookieName] = '"' . http_build_query($session) . '"';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'cookie' => true,
    ));

    // since we're serializing and deserializing the array, we cannot rely on
    // positions being the same, so we do a ksort before comparison
    $loaded_session = $facebook->getSession();
    ksort($loaded_session);
    ksort($session);
    $this->assertEquals($loaded_session, $session,
                        'Expect session back.');
    unset($_COOKIE[$cookieName]);
  }

  public function testInvalidGetSessionFromCookie() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'cookie' => true,
    ));
    $cookieName = 'fbs_' . self::APP_ID;
    $session = self::$VALID_EXPIRED_SESSION;
    $session['uid'] = 'make me invalid';
    $_COOKIE[$cookieName] = http_build_query($session);

    $this->assertTrue($facebook->getSession() === null,
                      'Expect no session back.');
    unset($_COOKIE[$cookieName]);
  }

  public function testSessionFromQueryString() {
    // @style-override allow json_encode call
    $_REQUEST['session'] = json_encode(self::$VALID_EXPIRED_SESSION);
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));

    $this->assertEquals($facebook->getUser(),
                        self::$VALID_EXPIRED_SESSION['uid'],
                        'Expect uid back.');
    unset($_REQUEST['session']);
  }

  public function testInvalidSessionFromQueryString() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));

    $params = array(
      'fb_sig_in_iframe' => 1,
      'fb_sig_iframe_key' => '6512bd43d9caa6e02c990b0a82652dca',
      'fb_sig_user' => '1677846385',
      'fb_sig_session_key' =>
        '2.NdKHtYIuB0EcNSHOvqAKHg__.86400.1258092000-1677846385',
      'fb_sig_ss' => 'AdCOu5nhDiexxRDLwZfqnA__',
      'fb_sig' => '1949f256171f37ecebe00685ce33bf17',
    );
    foreach($params as $key => $value) {
      $_GET[$key] = $value;
    }

    $this->assertEquals($facebook->getUser(), null,
                        'Expect uid back.');
    foreach($params as $key => $value) {
      unset($_GET[$key]);
    }
  }

  public function testGetUID() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $session = self::$VALID_EXPIRED_SESSION;
    $facebook->setSession($session);
    $this->assertEquals($facebook->getUser(), $session['uid'],
                        'Expect dummy uid back.');
  }

  public function testAPIWithoutSession() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $response = $facebook->api(array(
      'method' => 'fql.query',
      'query' => 'SELECT name FROM user WHERE uid=4',
    ));
    $this->assertEquals(count($response), 1,
                        'Expect one row back.');
    $this->assertEquals($response[0]['name'], 'Mark Zuckerberg',
                        'Expect the name back.');
  }

  public function testAPIWithSession() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $facebook->setSession(self::$VALID_EXPIRED_SESSION);

    // this is strange in that we are expecting a session invalid error vs a
    // signature invalid error. basically we're just making sure session based
    // signing is working, not that the api call is returning data.
    try {
      $response = $facebook->api(array(
        'method' => 'fql.query',
        'query' => 'SELECT name FROM profile WHERE id=4',
      ));
      $this->fail('Should not get here.');
    } catch(FacebookApiException $e) {
      $msg = 'Exception: 190: Invalid OAuth 2.0 Access Token';
      $this->assertEquals((string) $e, $msg,
                          'Expect the invalid session message.');

      $result = $e->getResult();
      $this->assertTrue(is_array($result), 'expect a result object');
      $this->assertEquals('190', $result['error_code'], 'expect code');
    }
  }

  public function testAPIGraphPublicData() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));

    $response = $facebook->api('/naitik');
    $this->assertEquals(
      $response['id'], '5526183', 'should get expected id.');
  }

  public function testGraphAPIWithSession() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $facebook->setSession(self::$VALID_EXPIRED_SESSION);

    try {
      $response = $facebook->api('/me');
      $this->fail('Should not get here.');
    } catch(FacebookApiException $e) {
      // means the server got the access token
      $msg = 'OAuthException: Error processing access token.';
      $this->assertEquals((string) $e, $msg,
                          'Expect the invalid session message.');
      // also ensure the session was reset since it was invalid
      $this->assertEquals($facebook->getSession(), null,
                          'Expect the to be reset.');
    }
  }

  public function testGraphAPIMethod() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));

    try {
      $response = $facebook->api('/naitik', 'DELETE');
      $this->fail('Should not get here.');
    } catch(FacebookApiException $e) {
      // ProfileDelete means the server understood the DELETE
      $msg = 'GraphMethodException: Unsupported delete request.';
      $this->assertEquals((string) $e, $msg,
                          'Expect the invalid session message.');
    }
  }

  public function testCurlFailure() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));

    try {
      // we dont expect facebook will ever return in 1ms
      Facebook::$CURL_OPTS[CURLOPT_TIMEOUT_MS] = 1;
      $facebook->api('/naitik');
    } catch(FacebookApiException $e) {
      unset(Facebook::$CURL_OPTS[CURLOPT_TIMEOUT_MS]);
      $this->assertEquals(
        CURLE_OPERATION_TIMEOUTED, $e->getCode(), 'expect timeout');
      $this->assertEquals('CurlException', $e->getType(), 'expect type');
      return;
    }

    $this->fail('Should not get here.');
  }

  public function testGraphAPIWithOnlyParams() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));

    $response = $facebook->api('/platform/feed', array('limit' => 1));
    $this->assertEquals(1, count($response['data']), 'should get one entry');
    $this->assertTrue(
      strstr($response['paging']['next'], 'limit=1') !== false,
      'expect the same limit back in the paging urls'
    );
  }

  public function testLoginURLDefaults() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com';
    $_SERVER['REQUEST_URI'] = '/examples';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $encodedUrl = rawurlencode('http://fbrell.com/examples');
    $this->assertNotNull(strpos($facebook->getLoginUrl(), $encodedUrl),
                         'Expect the current url to exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
  }

  public function testLoginURLDefaultsDropSessionQueryParam() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com';
    $_SERVER['REQUEST_URI'] = '/examples?session=xx42xx';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $expectEncodedUrl = rawurlencode('http://fbrell.com/examples');
    $this->assertTrue(strpos($facebook->getLoginUrl(), $expectEncodedUrl) > -1,
                      'Expect the current url to exist.');
    $this->assertFalse(strpos($facebook->getLoginUrl(), 'xx42xx'),
                       'Expect the session param to be dropped.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
  }

  public function testLoginURLDefaultsDropSessionQueryParamButNotOthers() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com';
    $_SERVER['REQUEST_URI'] = '/examples?session=xx42xx&do_not_drop=xx43xx';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $expectEncodedUrl = rawurlencode('http://fbrell.com/examples');
    $this->assertFalse(strpos($facebook->getLoginUrl(), 'xx42xx'),
                       'Expect the session param to be dropped.');
    $this->assertTrue(strpos($facebook->getLoginUrl(), 'xx43xx') > -1,
                      'Expect the do_not_drop param to exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
  }

  public function testLoginURLCustomNext() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com';
    $_SERVER['REQUEST_URI'] = '/examples';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $next = 'http://fbrell.com/custom';
    $loginUrl = $facebook->getLoginUrl(array(
      'next' => $next,
      'cancel_url' => $next
    ));
    $currentEncodedUrl = rawurlencode('http://fbrell.com/examples');
    $expectedEncodedUrl = rawurlencode($next);
    $this->assertNotNull(strpos($loginUrl, $expectedEncodedUrl),
                         'Expect the custom url to exist.');
    $this->assertFalse(strpos($loginUrl, $currentEncodedUrl),
                      'Expect the current url to not exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
  }

  public function testLogoutURLDefaults() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com';
    $_SERVER['REQUEST_URI'] = '/examples';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $encodedUrl = rawurlencode('http://fbrell.com/examples');
    $this->assertNotNull(strpos($facebook->getLogoutUrl(), $encodedUrl),
                         'Expect the current url to exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
  }

  public function testLoginStatusURLDefaults() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com';
    $_SERVER['REQUEST_URI'] = '/examples';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $encodedUrl = rawurlencode('http://fbrell.com/examples');
    $this->assertNotNull(strpos($facebook->getLoginStatusUrl(), $encodedUrl),
                         'Expect the current url to exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
  }

  public function testLoginStatusURLCustom() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com';
    $_SERVER['REQUEST_URI'] = '/examples';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $encodedUrl1 = rawurlencode('http://fbrell.com/examples');
    $okUrl = 'http://fbrell.com/here1';
    $encodedUrl2 = rawurlencode($okUrl);
    $loginStatusUrl = $facebook->getLoginStatusUrl(array(
      'ok_session' => $okUrl,
    ));
    $this->assertNotNull(strpos($loginStatusUrl, $encodedUrl1),
                         'Expect the current url to exist.');
    $this->assertNotNull(strpos($loginStatusUrl, $encodedUrl2),
                         'Expect the custom url to exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
  }

  public function testMagicQuotesQueryString() {
    if (!get_magic_quotes_gpc()) {
      // this test cannot run without get_magic_quotes_gpc(), and the setting
      // cannot be modified at runtime, so we're shit out of luck. thanks php.
      return;
    }

    // @style-override allow json_encode call
    $_REQUEST['session'] = addslashes(
      json_encode(self::$VALID_EXPIRED_SESSION));
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));

    $this->assertEquals($facebook->getUser(),
                        self::$VALID_EXPIRED_SESSION['uid'],
                        'Expect uid back.');
    unset($_REQUEST['session']);
  }

  public function testMagicQuotesCookie() {
    if (!get_magic_quotes_gpc()) {
      // this test cannot run without get_magic_quotes_gpc(), and the setting
      // cannot be modified at runtime, so we're shit out of luck. thanks php.
      return;
    }

    $cookieName = 'fbs_' . self::APP_ID;
    $session = self::$VALID_EXPIRED_SESSION;
    $_COOKIE[$cookieName] = addslashes('"' . http_build_query($session) . '"');
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'cookie' => true,
    ));

    // since we're serializing and deserializing the array, we cannot rely on
    // positions being the same, so we do a ksort before comparison
    $loaded_session = $facebook->getSession();
    ksort($loaded_session);
    ksort($session);
    $this->assertEquals($loaded_session, $session,
                        'Expect session back.');
    unset($_COOKIE[$cookieName]);
  }

  public function testNonDefaultPort() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com:8080';
    $_SERVER['REQUEST_URI'] = '/examples';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $encodedUrl = rawurlencode('http://fbrell.com:8080/examples');
    $this->assertNotNull(strpos($facebook->getLoginUrl(), $encodedUrl),
                         'Expect the current url to exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
  }

  public function testSecureCurrentUrl() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com';
    $_SERVER['REQUEST_URI'] = '/examples';
    $_SERVER['HTTPS'] = 'on';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $encodedUrl = rawurlencode('https://fbrell.com/examples');
    $this->assertNotNull(strpos($facebook->getLoginUrl(), $encodedUrl),
                         'Expect the current url to exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
    unset($_SERVER['HTTPS']);
  }

  public function testSecureCurrentUrlWithNonDefaultPort() {
    $_SERVER['HTTP_HOST'] = 'fbrell.com:8080';
    $_SERVER['REQUEST_URI'] = '/examples';
    $_SERVER['HTTPS'] = 'on';
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $encodedUrl = rawurlencode('https://fbrell.com:8080/examples');
    $this->assertNotNull(strpos($facebook->getLoginUrl(), $encodedUrl),
                         'Expect the current url to exist.');
    unset($_SERVER['HTTP_HOST']);
    unset($_SERVER['REQUEST_URI']);
    unset($_SERVER['HTTPS']);
  }

  public function testIgnoreArgSeparatorForCookie() {
    $cookieName = 'fbs_' . self::APP_ID;
    $session = self::$VALID_EXPIRED_SESSION;
    $_COOKIE[$cookieName] = '"' . http_build_query($session) . '"';
    ini_set('arg_separator.output', '&amp;');
    // ensure we're testing what we expect
    $this->assertEquals(http_build_query(array('a' => 1, 'b' => 2)),
                        'a=1&amp;b=2');
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
      'cookie' => true,
    ));

    // since we're serializing and deserializing the array, we cannot rely on
    // positions being the same, so we do a ksort before comparison
    $loaded_session = $facebook->getSession();
    ksort($loaded_session);
    ksort($session);
    $this->assertEquals($loaded_session, $session,
                        'Expect session back.');
    unset($_COOKIE[$cookieName]);
    ini_set('arg_separator.output', '&');
  }

  public function testAppSecretCall() {
    $facebook = new Facebook(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $response = $facebook->api('/' . self::APP_ID . '/insights');
    $this->assertTrue(count($response['data']) > 0,
                      'Expect some data back.');
  }

  public function testBase64UrlEncode() {
    $input = 'Facebook rocks';
    $output = 'RmFjZWJvb2sgcm9ja3M';

    $this->assertEquals(FBPublic::publicBase64UrlDecode($output), $input);
  }

  public function testSignedToken() {
    $facebook = new FBPublic(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $payload = $facebook->publicParseSignedRequest(self::$VALID_SIGNED_REQUEST);
    $this->assertNotNull($payload, 'Expected token to parse');
    $session = $facebook->publicCreateSessionFromSignedRequest($payload);
    foreach (array('uid', 'access_token') as $key) {
      $this->assertEquals($session[$key], self::$VALID_EXPIRED_SESSION[$key]);
    }
    $this->assertEquals($facebook->getSignedRequest(), null);
    $_REQUEST['signed_request'] = self::$VALID_SIGNED_REQUEST;
    $this->assertEquals($facebook->getSignedRequest(), $payload);
    unset($_REQUEST['signed_request']);
  }

  public function testNonTossedSignedtoken() {
    $facebook = new FBPublic(array(
      'appId'  => self::APP_ID,
      'secret' => self::SECRET,
    ));
    $payload = $facebook->publicParseSignedRequest(
      self::$NON_TOSSED_SIGNED_REQUEST);
    $this->assertNotNull($payload, 'Expected token to parse');
    $session = $facebook->publicCreateSessionFromSignedRequest($payload);
    $this->assertNull($session);
    $this->assertNull($facebook->getSignedRequest());
    $_REQUEST['signed_request'] = self::$NON_TOSSED_SIGNED_REQUEST;
    $this->assertEquals($facebook->getSignedRequest(),
      array('algorithm' => 'HMAC-SHA256'));
    unset($_REQUEST['signed_request']);
  }
}

class FBPublic extends Facebook {
  public static function publicBase64UrlDecode($input) {
    return self::base64UrlDecode($input);
  }
  public function publicParseSignedRequest($intput) {
    return $this->parseSignedRequest($intput);
  }
  public function publicCreateSessionFromSignedRequest($payload) {
    return $this->createSessionFromSignedRequest($payload);
  }
}
