<?php


namespace icework\restm\exceptions;

/**
 * Class RestmModelException
 * Model exception
 * @package icework\restm\exceptions
 */
class RestmModelException extends RestmException
{
  public static function makeInvalidDependency($dependencyName, $targetClass) {
    return new RestmModelException("Dependency <{$dependencyName}> invalid because target property doesn't exist in {$targetClass}");
  }
  public static function makeNotFoundAttribute($attributeName, $sourceAttributes) {
    $names=implode(', ', $sourceAttributes);
    return new RestmModelException("Attribute <{$attributeName}> not found in source attributes. Names found [{$names}].");
  }
  public static function makeNotFoundDependency($dependencyName, $targetClass) {
    return new RestmModelException("Dependency <{$dependencyName}> not found in {$targetClass}");
  }
  public static function makeInvalidSource($targetClass, $attributeName, $targetType, $sourceType) {
    return new RestmModelException("Incompatible types. Attribute {$targetClass}::{$attributeName} must be an '{$targetType}' but source type is '{$sourceType}'");
  }
  public static function makeInvalidEntity($targetClass, $sourceType) {
    return new RestmModelException("Incompatible types. Json entity for {$targetClass} must be an array, {$sourceType} given");
  }
}