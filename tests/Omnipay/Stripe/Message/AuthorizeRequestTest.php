<?php

namespace Omnipay\Stripe\Message;

use Omnipay\Tests\TestCase;

class AuthorizeRequestTest extends TestCase
{
    public function setUp()
    {
        $this->request = new AuthorizeRequest($this->getHttpClient(), $this->getHttpRequest());
        $this->request->initialize(
            array(
                'amount' => '12.00',
                'currency' => 'USD',
                'card' => $this->getValidCard(),
            )
        );
    }

    public function testCaptureIsFalse()
    {
        $data = $this->request->getData();
        $this->assertSame('false', $data['capture']);
    }

    /**
     * @expectedException \Omnipay\Common\Exception\InvalidRequestException
     * @expectedExceptionMessage The card parameter is required
     */
    public function testCardRequired()
    {
        $this->request->setCard(null);
        $this->request->getData();
    }

    public function testDataWithCardReference()
    {
        $this->request->setCardReference('xyz');
        $data = $this->request->getData();

        $this->assertSame('xyz', $data['customer']);
    }

    public function testDataWithToken()
    {
        $this->request->setToken('xyz');
        $data = $this->request->getData();

        $this->assertSame('xyz', $data['card']);
    }

    public function testDataWithCard()
    {
        $card = $this->getValidCard();
        $this->request->setCard($card);
        $data = $this->request->getData();

        $this->assertSame($card['number'], $data['card']['number']);
    }

    public function testDataWithTracks()
    {
      $tracks = "%25B4242424242424242%5ETESTLAST%2FTESTFIRST%5E1505201425400714000000%3F";
      $card = $this->getValidCard();
      $card['tracks'] = $tracks;
      unset($card['cvv']);
      unset($card['billingPostcode']);
      $this->request->setCard($card);
      $data = $this->request->getData();

      $this->assertSame($tracks, $data['card']['swipe_data']);
      $this->assertCount(1, $data['card'], "Swipe data should be present. All other fields are not required");
    }

    public function testDataWithTracksAndZipCVVManuallyEntered()
    {
        $tracks = "%25B4242424242424242%5ETESTLAST%2FTESTFIRST%5E1505201425400714000000%3F";
        $card = $this->getValidCard();
        $card['tracks'] = $tracks;
        $this->request->setCard($card);
        $data = $this->request->getData();

        $this->assertSame($tracks, $data['card']['swipe_data']);
        $this->assertSame($card['cvv'], $data['card']['cvc']);
        $this->assertSame($card['billingPostcode'], $data['card']['address_zip']);
        $this->assertCount(3, $data['card'], "Swipe data, cvv and zip code should be present");
    }

    public function testSendSuccess()
    {
        $this->setMockHttpResponse('PurchaseSuccess.txt');
        $response = $this->request->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertSame('ch_1IU9gcUiNASROd', $response->getTransactionReference());
        $this->assertNull($response->getCardReference());
        $this->assertNull($response->getMessage());
    }

    public function testSendError()
    {
        $this->setMockHttpResponse('PurchaseFailure.txt');
        $response = $this->request->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertNull($response->getCardReference());
        $this->assertSame('Your card was declined', $response->getMessage());
    }
}
