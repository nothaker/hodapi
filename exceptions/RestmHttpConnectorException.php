<?php


namespace icework\restm\exceptions;

/**
 * Class RestmHttpConnectorException
 * Exception for [[HttpConnector]] functionality
 * @package icework\restm\exceptions
 */
class RestmHttpConnectorException extends RestmException {
  public static function makeWrongBaseUrl($baseUrl) {
    return new RestmHttpConnectorException("base url '{$baseUrl}' must be a base fucking url, not be a url with the question mark (?) separators!");
  }

  public static function makeWrongInput() {
    return new RestmHttpConnectorException("The input model must not be null. If an input model is not required, then you don't need to call the \"input\" method");
  }

  public static function makeWrongCurlResponse($curlError) {
    return new RestmHttpConnectorException("CURL stop with error: '{$curlError}'");
  }

  public static function makeEmptyBody() {
    return new RestmHttpConnectorException("Body is empty");
  }

  public static function makeUnhandledResponse($httpStatus) {
    return new RestmHttpConnectorException("Unhandled response with {$httpStatus} httpStatus ");
  }
}