<?php


namespace icework\restm\base;

use icework\restm\base\BaseOutputModel;

interface ConnectorInterface
{
  /**
   * Open connector
   * @param string $base Url or something else
   * @return ConnectorInterface
   */
  function open(string $base) : ConnectorInterface;

  /**
   * Set the method used
   * @param string $method Path of method
   * @param array $params Params for the method
   * @return ConnectorInterface
   */
  function method(string $method, array $params=[]) : ConnectorInterface;

  /**
   * Set input model if need
   * @param InputModelInterface $inputModel
   * @return ConnectorInterface
   */
  function setInputModel(InputModelInterface $inputModel) : ConnectorInterface;

  /**
   * Run
   * @param array $modelDependency [[BaseOutputModel::asMany()]] or [[BaseOutputModel::asOne()]]
   * @return BaseOutputModel|BaseOutputModel[]
   */
  function run(array $modelDependency);
}