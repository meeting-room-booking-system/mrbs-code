<?php
declare(strict_types=1);
namespace MRBS\Auth;

use CAS_OutOfSequenceException;
use CAS_Request_AbstractRequest;
use CAS_Request_RequestInterface;
use GuzzleHttp\Client;

/**
 *  A CAS request class that uses Guzzle to make the request, rather than the default curl.  Guzzle will
 *  use curl if possible but falls back to the native PHP functions if curl is not available.
 */
class AuthCasGuzzleRequest extends CAS_Request_AbstractRequest implements CAS_Request_RequestInterface
{
  private $client;
  private $options = [
    'headers' => []
  ];
  private $response_status_code;
  private $sent = false;  // $_sent is private in CAS_Request_AbstractRequest


  public function __construct()
  {
    $this->client = new Client();
  }


  /**
   * @see CAS_Request_RequestInterface::addCookie()
   */
  public function addCookie($name, $value) : void
  {
    // TODO: Implement addCookie() method.
    throw new \Exception('Not yet implemented');
  }


  /**
   * @see CAS_Request_RequestInterface::addCookies()
   */
  public function addCookies(array $cookies) : void
  {
    // TODO: Implement addCookies() method.
    throw new \Exception('Not yet implemented');
  }


  /**
   * @see CAS_Request_RequestInterface::addHeader()
   */
  public function addHeader($header) : void
  {
    if ($this->sent)
    {
      throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);
    }

    if (preg_match('/^([^:]+):\s*(.+)$/', $header, $matches))
    {
      $this->options['headers'][$matches[1]] = $matches[2];
    }
  }


  /**
   * @see CAS_Request_RequestInterface::addHeaders()
   */
  public function addHeaders(array $headers) : void
  {
    if ($this->sent)
    {
      throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);
    }

    foreach ($headers as $header)
    {
      $this->addHeader($header);
    }
  }


  /**
   * @see CAS_Request_RequestInterface::setPostBody()
   */
  public function setPostBody($body) : void
  {
    parent::setPostBody($body);
    parse_str($body, $this->options['form_params']);
  }


  /**
   * @see CAS_Request_RequestInterface::setSslCaCert()
   */
  public function setSslCaCert($caCertPath, $validate_cn = true) : void
  {
    parent::setSslCaCert($caCertPath, $validate_cn);
    $this->options['verify'] = ($validate_cn) ? $caCertPath : false;
  }


  /**
   * @see CAS_Request_RequestInterface::getResponseStatusCode()
   */
  public function getResponseStatusCode() : int
  {
    if (!$this->sent)
    {
      throw new CAS_OutOfSequenceException('Request has not been sent yet. Cannot '.__METHOD__);
    }

    return $this->response_status_code;
  }


  /**
   * @see CAS_Request_AbstractRequest::sendRequest()
   */
  protected function sendRequest() : bool
  {
    try
    {
      $method = ($this->isPost) ? 'POST' : 'GET';
      $this->sent = true;
      $response = $this->client->request($method, $this->url, $this->options);
      $this->response_status_code = $response->getStatusCode();
      $this->storeResponseBody($response->getBody()->getContents());
      foreach ($response->getHeaders() as $name => $values)
      {
        $this->storeResponseHeader(mb_strtolower($name) . ': ' . implode(', ', $values));
      }
      return true;
    }
    catch (\Exception $e)
    {
      $this->storeErrorMessage( $e->getMessage());
      return false;
    }
  }

}
