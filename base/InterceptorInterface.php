<?php


namespace icework\restm\base;


interface InterceptorInterface
{
  /**
   * @see BaseOutputModel::asOne()
   * @see BaseOutputModel::asMany()
   * @return array
   */
  function getDeclaration() : array;
  function populate($data) : void;
}