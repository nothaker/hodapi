<?php


namespace icework\restm\base;

use icework\restm\exceptions\RestmOutputModelException;

abstract class BaseOutputModel
{
  const PROP_AS_MANY=1;
  const PROP_AS_ONE=0;

  const RELATION_INDEX=0;
  const CLASS_INDEX=1;

  /**
   * As many declaration
   * @return array
   */
  public static function asMany() : array {
    return [self::PROP_AS_MANY, get_called_class()];
  }

  /**
   * As one declaration
   * @return array
   */
  public static function asOne() : array {
    return [self::PROP_AS_ONE, get_called_class()];
  }

  /**
   * @var BaseOutputModel|null
   */
  private $__parent;

  /**
   * Class public non static properties
   * @var string[]
   */
  private $__attributeNames;
  /**
   * BaseOutputModel constructor.
   * @param BaseOutputModel|null $parent
   * @throws \ReflectionException
   */
  public function __construct(BaseOutputModel $parent = null)
  {
    $this->__parent=$parent;
    // fetch properties
    $reflection=new \ReflectionClass(get_called_class());
    foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      !$property->isStatic() && $this->__attributeNames[]=$property->getName();
    }
  }

  /**
   * Get parent model. For misc.
   * @return BaseOutputModel|null
   */
  public function getParent() : BaseOutputModel {
    return $this->__parent;
  }

  /**
   * Build from declaration
   * @param array(2) $declaration
   * @param array $data
   * @param BaseOutputModel|null $parent
   * @return BaseOutputModel|BaseOutputModel[]
   * @throws RestmOutputModelException
   */
  public static function build(array $declaration, array $data, BaseOutputModel $parent=null) {
    $asMany=$declaration[self::RELATION_INDEX] === self::PROP_AS_MANY;

    if (!$asMany) {
      $data = [$data]; // work in array mode
    }

    $instances=[];
    foreach ($data as $item) {
      if (!is_array($item)) {
        throw RestmOutputModelException::maleInvalidEntity($declaration[self::CLASS_INDEX], gettype($item));
      }

      $instance=new $declaration[self::CLASS_INDEX]($parent); /* @var BaseOutputModel $instance */
      $instance->applyPropertyValues($item);

      // processing dependencies
      foreach ($instance->dependencies() as $propertyName=> $dependency) {
        // if property doesn't exist
        if (!$instance->hasAttribute($propertyName)) {
          throw RestmOutputModelException::makeInvalidDependency($propertyName, get_class($instance));
        }
        // if target data didn't contains entities
        // if dependency has been declared then the target data must be contains field as null anyway
        if (!array_key_exists($propertyName, $item)) {
          throw RestmOutputModelException::makeNotFoundDependency($propertyName, get_class($instance));
        }

        // for AS_MANY declaration source field must be an array
        if ($dependency[self::RELATION_INDEX] === self::PROP_AS_MANY && !is_array($item[$propertyName])) {
          throw RestmOutputModelException::makeInvalidSource(get_class($instance), $propertyName, "array", gettype($item[$propertyName]));
        }

        $instance->{$propertyName}=$item[$propertyName] !== null // for other situations
          ? BaseOutputModel::build($dependency, $item[$propertyName], $instance)
          : null;
      }
      $instances[]=$instance;
    }
    // mode-depend return
    return $asMany ? $instances : array_pop($instances);
  }

  /**
   * Declared dependencies. Format:
   * ['<property>' => <dependency>, ...]. dependencies are built using methods [[BaseOutputModel::asMany()]] and [[BaseOutputModel::asOne()]]
   *
   * @see BaseOutputModel::asMany()
   * @see BaseOutputModel::asOne()
   * @return array
   */
  abstract protected function dependencies() : array;

  /**
   * Has Attribute
   * @param string $attributeName
   * @return bool
   */
  protected function hasAttribute(string $attributeName) {
    return in_array($attributeName, $this->__attributeNames, true);
  }
  /**
   * Apply property values
   * @param array $values
   * @throws RestmOutputModelException
   */
  protected function applyPropertyValues(array $values) : void {
    foreach ($this->__attributeNames as $attributeName) {
      if (!array_key_exists($attributeName, $values)) {
        throw RestmOutputModelException::makeNotFoundAttribute($attributeName, $this);
      }
      $this->{$attributeName}=$values[$attributeName];
    }
  }

  /**
   * To Array recursive
   * @return array
   */
  public function toArray() : array {
    $data=[];
    foreach ($this->__attributeNames as $attributeName) {
      $data[$attributeName]=$this->{$attributeName};
    }
    foreach ($this->dependencies() as $dependencyName => $declaration) {
      $asMany=$declaration[self::RELATION_INDEX] === self::PROP_AS_MANY;
      $children=!$asMany
        ? [$this->{$dependencyName}]
        : $this->{$dependencyName};

      $dependData=[];
      foreach ($children as $child) { /* @var $child BaseOutputModel */
        $dependData[]=$child instanceof BaseOutputModel
          ? $child->toArray()
          : null;
      }
      $data[$dependencyName]=$asMany
        ? $dependData
        : array_pop($dependData);
    }
    return $data;
  }
}