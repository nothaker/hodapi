<?php


namespace icework\restm\exceptions;


class RestmOutputModelException extends RestmException
{
  public static function makeInvalidDependency($dependencyName) {
    return new RestmOutputModelException("Dependency <{$dependencyName}> invalid because target property doesn't exist");
  }
  public static function makeNotFoundAttribute($attributeName) {
    return new RestmOutputModelException("Attribute <{$attributeName}> not found.");
  }
  public static function makeNotFoundDependency($dependencyName, $targetClass) {
    return new RestmOutputModelException("Dependency <{$dependencyName}> not found in {$targetClass}");
  }
}