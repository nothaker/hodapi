<?php


namespace icework\restm\base;


interface InterceptExceptionInterface
{
  /**
   * @see BaseOutputModel::asOne()
   * @see BaseOutputModel::asMany()
   * @return array
   */
  function getDeclaration() : array;
  function populate($data) : void;
}