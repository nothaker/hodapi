<?php


namespace icework\restm;

use icework\restm\base\Model;
use icework\restm\interfaces\Connector;
use icework\restm\interfaces\InputModel;
use icework\restm\interfaces\InterceptException;
use icework\restm\HttpInterceptException;
use icework\restm\exceptions\RestmHttpConnectorException;
use icework\restm\exceptions\RestmModelException;

class HttpConnector implements Connector
{
  const ACCEPT_HEADER='Accept';
  const CONTENT_TYPE_HEADER='Content-Type';
  const CONTENT_LENGTH_HEADER='Content-Length';

  const JSON_CONTENT_TYPE='application/json';

  const QUERY_SEPARATOR='?';
  const PATH_SEPARATOR='/';
  const HEADER_SEPARATOR=': ';
  const PARAM_SEPARATOR='&';

  const MODE_GET=1;
  const MODE_POST=2;

  const DEFAULT_MODE=self::MODE_GET;

  // http routine
  const HTTP_OK=200;
  const HTTP_BAD_REQUEST=400;

  // based
  private $__baseUrl;
  private $__method;
  /**
   * @var \icework\restm\interfaces\InputModel
   */
  private $__inputModel;
  /**
   * @var InterceptException[]
   */
  private $__exceptions=[];

  // specify for http
  private $__headers=[];
  private $__query=[];
  private $__mode=self::DEFAULT_MODE;

  /**
   * Open connector with baseUrl
   * @param string $base
   * @return HttpConnector
   */
  public function open(string $base) : HttpConnector {
    if (strpos($base, self::QUERY_SEPARATOR) !== false) {
      RestmHttpConnectorException::makeWrongBaseUrl($base);
    }

    $this->__baseUrl=trim($base, self::PATH_SEPARATOR);
    return $this;
  }

  /**
   * As example 'user/auth' method.
   * For parameterized queries use anchors. for example, the anchor "#id" will be replaced by an parameters element -> ['#id' => 123].
   * Its simply and fast.
   *
   * @param string $method 'user/auth' as example
   * @param array $params
   * @return HttpConnector
   */
  public function method(string $method, array $params = []): HttpConnector
  {
    $method=str_replace(array_keys($params), array_values($params), $method);
    $this->__method=trim($method, self::PATH_SEPARATOR);
    return $this;
  }

  /**
   * Add CURL headers. Use this for specific headers.
   * @param array $headers
   * @return HttpConnector
   */
  public function addHeaders(array $headers) : HttpConnector {
    $this->__headers=array_replace($this->__headers, $headers);
    return $this;
  }

  /**
   * Add http query
   * @param array $query
   * @return HttpConnector
   */
  public function addQuery(array $query) : HttpConnector {
    $this->__query=array_replace($this->__query, $query);
    return $this;
  }

  /**
   * @inheritDoc
   * @param InputModel $inputModel
   * @return HttpConnector
   * @throws RestmHttpConnectorException
   */
  public function setInputModel(InputModel $inputModel) : HttpConnector {
    if ($inputModel === null) {
      throw RestmHttpConnectorException::makeWrongInput();
    }
    $this->__inputModel=$inputModel;
    return $this;
  }

  /**
   * Run Http as GET request
   * @return HttpConnector
   */
  public function asGetRequest() : HttpConnector {
    $this->__mode=self::MODE_GET;
    return $this;
  }

  /**
   * Run Http as POST request
   * @return HttpConnector
   */
  public function asPostRequest() : HttpConnector {
    $this->__mode=self::MODE_POST;
    return $this;
  }

  /**
   * Run connector
   * @param array $modelDependency
   * @return base\Model|base\Model[]|void
   * @throws RestmHttpConnectorException
   * @throws HttpInterceptException
   * @throws RestmModelException
   */
  public function run(array $modelDependency)
  {
    $inputModel=$this->__inputModel;
    $handle=curl_init();
    try {
      $options=[];
      switch ($this->__mode) {
        case self::MODE_GET:
          if ($inputModel !== null) {
            $this->addQuery($inputModel->getInputData());
          }
          break;
        case self::MODE_POST:
          $options=[
            CURLOPT_POST => true
          ];

          $jsonData=$inputModel
            ? json_encode($inputModel->getInputData(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : null;

          if ($jsonData!==null) {
            $options[CURLOPT_POSTFIELDS]=$jsonData;
            $this->addHeaders([self::CONTENT_LENGTH_HEADER => strlen($jsonData)]);
          }
          break;
      }

      curl_setopt_array($handle, array_replace([
        CURLOPT_URL => $this->getPreparedUrl(),
        CURLOPT_HTTPHEADER => $this->getPreparedHeaders(),
        CURLOPT_RETURNTRANSFER => true,
      ], $options));

      $response=curl_exec($handle);
      $httpStatus=curl_getinfo($handle, CURLINFO_HTTP_CODE);

      // curl down
      if ($httpStatus <= 0) {
        throw RestmHttpConnectorException::makeWrongCurlResponse(curl_error($handle));
      }

      // empty body
      if (!is_string($response) || $response==='' || $response===null) {
        throw RestmHttpConnectorException::makeEmptyBody();
      }

      // if ok
      if ($httpStatus==self::HTTP_OK) { //todo processing other Good codes
        $json=json_decode($response, true);
        return Model::build($modelDependency, $json);
      }
      // processing interception with exception
      $interceptor=$this->__exceptions[$httpStatus] ?? null;
      if ($interceptor) {
        $json=json_decode($response, true);
        $preparedData=Model::build($interceptor->getDeclaration(), $json);
        $interceptor->populate($preparedData);
        throw $interceptor;
      }
      throw RestmHttpConnectorException::makeUnhandledResponse($httpStatus);
    } finally {
      is_resource($handle) && curl_close($handle);
    }
  }

  /**
   * Add interceptor for httpStatus exceptions. To intercept responses other than [[HttpConnector::HTTP_OK]].
   * @param int $httpStatus
   * @param InterceptException $exception
   * @return HttpConnector
   */
  function addException(int $httpStatus, InterceptException $exception) : HttpConnector {
    $this->__exceptions[$httpStatus]=$exception;
    return $this;
  }

  /**
   * Get prepared url
   * @return string
   */
  protected function getPreparedUrl() : string {
    $url=$this->__baseUrl . self::PATH_SEPARATOR . $this->__method;
    if (count($this->__query) > 0) {
      $url .= self::QUERY_SEPARATOR . http_build_query($this->__query);
    }
    return $url;
  }

  /**
   * Get prepared headers for CURL
   * @return array
   */
  protected function getPreparedHeaders() : array {
    $headers=[];
    foreach ($this->__headers as $name=>$value) {
      $headers[]= $name . self::HEADER_SEPARATOR . $value;
    }
    return $headers;
  }


}
