<?php


namespace icework\restm\exceptions\intercept;


use icework\restm\base\BaseOutputModel;
use icework\restm\base\InterceptorInterface;
use icework\restm\exceptions\RestmException;

/**
 * Base Class HttpInterceptException
 *
 * To intercept responses other than [[HttpConnector::HTTP_OK]]
 * @package icework\restm\base
 */
abstract class HttpInterceptException extends RestmException implements InterceptorInterface
{
  private $__declaration;

  /**
   * HttpInterceptException constructor.
   * @see BaseOutputModel::asMany()
   * @see BaseOutputModel::asOne()
   * @param array $declaration Use for declare dependency [[BaseOutputModel::asMany()]] or [[BaseOutputModel::asOne()]]
   */
  public function __construct(array $declaration)
  {
    parent::__construct();
    $this->__declaration=$declaration;
  }

  /**
   * Get interception model
   * @return array
   */
  public function getDeclaration(): array
  {
    return $this->__declaration;
  }

  abstract function populate($data): void;
}