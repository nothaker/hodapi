<?php


namespace icework\restm\interfaces;


interface InterceptException
{
  /**
   * @return array
   *@see Model::asManyDeclaration()
   * @see Model::asOneDeclaration()
   */
  function getDeclaration() : array;
  function populate($data) : void;
}