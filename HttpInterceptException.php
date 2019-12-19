<?php


namespace icework\restm;


use icework\restm\base\Model;
use icework\restm\interfaces\InterceptException;

/**
 * Base Class HttpInterceptException
 *
 * To intercept responses other than [[HttpConnector::HTTP_OK]]
 * @package icework\restm\base
 */
abstract class HttpInterceptException extends \Exception implements InterceptException
{
  private $__declaration;

  /**
   * HttpInterceptException constructor.
   * @param array $declaration Use for declare dependency [[BaseOutputModel::asMany()]] or [[BaseOutputModel::asOne()]]
   *@see Model::asOneDeclaration()
   * @see Model::asManyDeclaration()
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