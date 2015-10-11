<?php

/**
 * @file
 * Contains \Drupal\og\Plugin\Validation\Constraint\ValidOgMembershipReferenceConstraintValidator.
 */

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidOgMembershipReferenceConstraintValidator extends ConstraintValidator {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /* @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }

    $entity = \Drupal::entityManager()
      ->getStorage($value->getFieldDefinition()->getTargetEntityTypeId())
      ->load($value->get('target_id')->getValue());

    if (!$entity) {
      return;
    }

    $params['%label'] = $entity->label();

    if (!$entity->hasField(OG_GROUP_FIELD)) {
      $this->context->addViolation($this->t($constraint->NotValidGroup, $params));
      return;
    }
  }
}
