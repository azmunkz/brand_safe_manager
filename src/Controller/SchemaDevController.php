<?php

namespace Drupal\brand_safety_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\brand_safety_manager\Schema\BrandSafetySchema;

class SchemaDevController extends ControllerBase {

  public function createTable() {
    $schema_service = \Drupal::database()->schema();
    if (!$schema_service->tableExists('brand_safety_keywords')) {
      $schema = BrandSafetySchema::getDefinition();
      $schema_service->createTable('brand_safety_keywords', $schema['brand_safety_keywords']);
      $this->messenger()->addStatus('✅ Table brand_safety_keywords has been created successfully.');
    }
    else {
      $this->messenger()->addWarning('⚠️ Table already exists.');
    }

    return new RedirectResponse('/admin/config/brand-safety/keywords');
  }
}
