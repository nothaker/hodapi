<?php


namespace icework\restm\exceptions;


class RestmOutputModelException extends RestmException
{
  public static function makeInvalidDependency($dependencyName, $targetClass) {
    return new RestmOutputModelException("Dependency <{$dependencyName}> invalid because target property doesn't exist in {$targetClass}");
  }
  public static function makeNotFoundAttribute($attributeName, $instance) {
    $class=get_class($instance);
    return new RestmOutputModelException("Attribute <{$attributeName}> not found in {$class}.");
  }
  public static function makeNotFoundDependency($dependencyName, $targetClass) {
    return new RestmOutputModelException("Dependency <{$dependencyName}> not found in {$targetClass}");
  }
  public static function makeInvalidSource($targetClass, $attributeName, $targetType, $sourceType) {
    return new RestmOutputModelException("Incompatible types. Attribute {$targetClass}::{$attributeName} must be an '{$targetType}' but source type is '{$sourceType}'");
  }
  public static function maleInvalidEntity($targetClass, $sourceType) {
    return new RestmOutputModelException("Incompatible types. Json entity for {$targetClass} must be an array, given {$sourceType}");
  }
}