<?php


namespace icework\restm\interfaces;

use icework\restm\interfaces\InputModel;
use icework\restm\base\Model;

interface Connector
{
  /**
   * Open connector
   * @param string $base Url or something else
   * @return Connector
   */
  function open(string $base);

  /**
   * Set the method used
   * @param string $method Path of method
   * @param array $params Params for the method
   * @return Connector
   */
  function method(string $method, array $params=[]);

  /**
   * Set input model if need
   * @param InputModel $inputModel
   * @return Connector
   */
  function setInputModel(InputModel $inputModel);

  /**
   * Run
   * @param array $modelDependency [[BaseOutputModel::asMany()]] or [[BaseOutputModel::asOne()]]
   * @return Model|Model[]
   */
  function run(array $modelDependency);
}