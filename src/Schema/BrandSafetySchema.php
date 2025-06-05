<?php

namespace Drupal\brand_safety_manager\Schema;

class BrandSafetySchema {

  public static function getDefinition(): array {
    return [
      'brand_safety_keywords' => [
        'description' => 'Stores brand safety keywords with language and severity.',
        'fields' => [
          'id' => ['type' => 'serial', 'not null' => TRUE],
          'keyword' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE],
          'language' => ['type' => 'varchar', 'length' => 10, 'not null' => TRUE],
          'severity' => ['type' => 'varchar', 'length' => 10, 'not null' => TRUE],
          'created' => ['type' => 'int', 'not null' => TRUE, 'default' => 0],
          'updated' => ['type' => 'int', 'not null' => TRUE, 'default' => 0],
        ],
        'primary key' => ['id'],
        'indexes' => [
          'keyword' => ['keyword'],
          'language' => ['language'],
        ],
      ],
    ];
  }

}
