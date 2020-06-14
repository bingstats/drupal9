<?php

namespace Drupal\bootstrap_layout_builder\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\bootstrap_layout_builder\Plugin\Layout\BootstrapLayout;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Makes a bootstrap layout for each layout config entity.
 */
class BootstrapLayoutDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    for ($i = 1; $i <= 12; $i++) {
      $label = $this->t('Bootstrap') . ' ' . $i . ' ';
      $label .= $i == 1 ? $this->t('Col') : $this->t('Cols');

      $this->derivatives['blb_col_' . $i] = new LayoutDefinition([
        'class' => BootstrapLayout::class,
        'label' => $label,
        'category' => 'Bootstrap',
        'regions' => $this->getRegions($i),
        'theme_hook' => 'row_columns',
        'icon_map' => $this->getIconMap($i),
      ]);
    }

    return $this->derivatives;
  }

  /**
   * Convert intger to number in letters.
   *
   * @param int $num
   *   The number that needed to be converted.
   *
   * @return string
   *   The number in letters.
   */
  private function formatNumberInLetters(int $num) {
    $numbers = [
      1 => "one",
      2 => "two",
      3 => "three",
      4 => "four",
      5 => "five",
      6 => "six",
      7 => "seven",
      8 => "eight",
      9 => "nine",
      10 => "ten",
      11 => "eleven",
      12 => "twelve",
    ];
    return $numbers[$num];
  }

  /**
   * Get the formated array of row regions based on columns count.
   *
   * @param int $columns_count
   *   The count of row columns.
   *
   * @return array
   *   The row columns 'regions'.
   */
  private function getRegions(int $columns_count) {
    $regions = [];

    for ($i = 1; $i <= $columns_count; $i++) {
      $key = 'blb_region_col_' . $i;
      $regions[$key] = [
        'label' => $this->t('Col') . ' ' . $i,
      ];
    }

    return $regions;
  }

  /**
   * Get the icon map array based on columns_count.
   *
   * @param int $columns_count
   *   The count of row columns.
   *
   * @return array
   *   The icon map array.
   */
  private function getIconMap(int $columns_count) {
    $row = [];

    for ($i = 1; $i <= $columns_count; $i++) {
      $row[] = 'square_' . $this->formatNumberInLetters($i);
    }

    $icon_map = [$row];
    return $icon_map;
  }

}
