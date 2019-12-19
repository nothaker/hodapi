<?php


namespace icework\restm\base;

use icework\restm\exceptions\RestmModelException;

abstract class Model
{
  const AS_MANY_ATTACHMENTS_TYPE=1;
  const AS_ONE_ATTACHMENT_TYPE=0;

  const ATTACHMENT_TYPE_INDEX=0;
  const CLASS_INDEX=1;
  const ENABLE_RECURSIVE_INDEX=2;

  /**
   * As many attachments declaration
   * @return array
   * @param bool $enableRecursive Processing recursive attachments
   */
  public static function asManyDeclaration(bool $enableRecursive=true) : array {
    return [
      self::ATTACHMENT_TYPE_INDEX => self::AS_MANY_ATTACHMENTS_TYPE,
      self::CLASS_INDEX => get_called_class(),
      self::ENABLE_RECURSIVE_INDEX => $enableRecursive
    ];
  }

  /**
   * As one attachment declaration
   * @param bool $enableRecursive Processing recursive attachments
   * @return array
   */
  public static function asOneDeclaration(bool $enableRecursive=true) : array {
    return [
      self::ATTACHMENT_TYPE_INDEX => self::AS_ONE_ATTACHMENT_TYPE,
      self::CLASS_INDEX => get_called_class(),
      self::ENABLE_RECURSIVE_INDEX => $enableRecursive
    ];
  }

  public static function isManyAttachmentsDeclaration(array $attachmentDeclaration) : bool {
    return $attachmentDeclaration[self::ATTACHMENT_TYPE_INDEX] === self::AS_MANY_ATTACHMENTS_TYPE;
  }

  public static function isOneAttachmentDeclaration(array $attachmentDeclaration) : bool {
    return $attachmentDeclaration[self::ATTACHMENT_TYPE_INDEX] === self::AS_ONE_ATTACHMENT_TYPE;
  }

  public static function isEnabledRecursiveDeclaration(array $attachmentDeclaration) : bool {
    return $attachmentDeclaration[self::ENABLE_RECURSIVE_INDEX];
  }

  /**
   * @var Model|null
   */
  private $__parent;

  /**
   * Class public non static properties
   * @var string[]
   */
  private $__attributeNames;

  /**
   * BaseOutputModel constructor.
   * @param Model|null $parent
   * @throws \ReflectionException
   */
  public function __construct(Model $parent = null)
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
   * @return Model|null
   */
  public function getParent() : Model {
    return $this->__parent;
  }


  public function populate($data, bool $enableRecursive = false) : Model {
    if (!is_array($data)) {
      throw RestmModelException::makeInvalidEntity(get_called_class(), gettype($data));
    }
    $this->applyPropertyValues($data);

    if (!$enableRecursive) {
      return $this;
    }

    foreach ($this->attachments() as $propertyName => $attachmentDeclaration) {
      // if property doesn't exist
      if (!$this->hasAttribute($propertyName)) {
        throw RestmModelException::makeInvalidDependency($propertyName, get_called_class());
      }
      // if target data didn't contains entities
      // if dependency has been declared then the target data must be contains field as null anyway
      if (!array_key_exists($propertyName, $data)) {
        throw RestmModelException::makeNotFoundDependency($propertyName, get_called_class());
      }
      // build with general interface
      $this->{$propertyName}=self::build($attachmentDeclaration, $data[$propertyName], $this);
    }

    return $this;
  }

  /**
   * Build from attachment declaration
   * @param array(2) $declaration
   * @param array $data
   * @param Model|null $parent
   * @return Model|Model[]
   * @throws RestmModelException
   */
  public static function build(array $attachmentDeclaration, $data, Model $parent=null) {
    // less hardcode
    // AS_ONE
    if (self::isOneAttachmentDeclaration($attachmentDeclaration)) {
      // if null
      if ($data===null) {
        return null;
      }
      $instance = new $attachmentDeclaration[self::CLASS_INDEX](null); /* @var $instance Model */
      return $instance->populate($data, $attachmentDeclaration[self::ENABLE_RECURSIVE_INDEX]);
    }

    // AS_MANY check source type
    if (self::isManyAttachmentsDeclaration($attachmentDeclaration) && !is_array($data)) {
      throw RestmModelException::makeInvalidEntity(get_called_class(), gettype($data));
    }

    $models=[];
    foreach ($data as $item) {
      $instance=new $attachmentDeclaration[self::CLASS_INDEX](null); /* @var $instance Model */
      $models[]=$instance->populate($item, $attachmentDeclaration[self::ENABLE_RECURSIVE_INDEX]);
    }
    return $models;
  }

  /**
   * Declared attachments. Format:
   * ['<property>' => <attachment>, ...]. dependencies are built using methods [[BaseOutputModel::asMany()]] and [[BaseOutputModel::asOne()]]
   *
   * @return array
   *@see  Model::asOneDeclaration()
   * @see Model::asManyDeclaration()
   */
  abstract protected function attachments() : array;

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
   * @throws RestmModelException
   */
  protected function applyPropertyValues(array $values) : void {
    foreach ($this->__attributeNames as $attributeName) {
      if (!array_key_exists($attributeName, $values)) {
        throw RestmModelException::makeNotFoundAttribute($attributeName, array_keys($values));
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
    foreach ($this->attachments() as $dependencyName => $declaration) {
      $asMany=$declaration[self::ATTACHMENT_TYPE_INDEX] === self::AS_MANY_ATTACHMENTS_TYPE;
      $children=!$asMany
        ? [$this->{$dependencyName}]
        : $this->{$dependencyName};

      $dependData=[];
      foreach ($children as $child) { /* @var $child Model */
        $dependData[]=$child instanceof Model
          ? $child->toArray()
          : null;
      }
      $data[$dependencyName]=$asMany
        ? $dependData
        : array_pop($dependData);
    }
    return $data;
  }

  /**
   * Get Attribute names
   * @return string[]
   */
  public function getAttributeNames() : array {
    return $this->__attributeNames;
  }


}