<?php

namespace Drupal\themekit\Tests;

class ThemekitRenderTableTest extends \DrupalWebTestCase {

  public function setUp() {
    parent::setUp('themekit');
  }

  public static function getInfo() {
    // Note: getInfo() strings should not be translated.
    return [
      'name' => 'Themekit render table test',
      'description' => "Tests the 'themekit_table' render element type provided by themekit.",
      'group' => 'Themekit',
    ];
  }

  /**
   * Test multiple tables in the same function, to avoid repeated calls to setUp().
   */
  public function testTable() {

    $element = [
      /* @see theme_themekit_table() */
      '#theme' => 'themekit_table',
      'thead' => [
        '#themekit_table_type' => 'thead',
        'head_row' => [
          'col_0' => ['#markup' => 'Col 0'],
          'col_1' => ['#markup' => 'Col 1', '#themekit_table_type' => 'th'],
        ],
      ],
      'first_tbody_section' => [
        '#themekit_table_type' => 'tbody',
        'tbody_0_row_0' => [
          'col_0' => ['#markup' => 'TB0 Cell 0.0'],
          'col_1' => ['#markup' => 'TB0 Cell 0.1'],
        ],
      ],
      // Rows that are not in a tsection always go into the last tbody section.
      'body_row_0' => [
        'col_0' => ['#markup' => 'Cell 0.0'],
        'col_1' => ['#markup' => 'Cell 0.1'],
      ],
      'body_row_1' => [
        'col_0' => ['#markup' => 'Cell 1.0'],
        'col_1' => ['#markup' => 'Cell 1.1'],
      ],
      // tfoot always comes before tbody in the html.
      'tfoot' => [
        '#themekit_table_type' => 'tfoot',
        'foot_row' => [
          'col_0' => ['#markup' => 'tfoot cell 0.0'],
          'col_1' => ['#markup' => 'tfoot cell 0.1'],
        ],
      ],
    ];

    $html_expected = ''
      . '<table>'
      . '<thead><tr><td>Col 0</td><th>Col 1</th></tr></thead>'
      . '<tfoot><tr><td>tfoot cell 0.0</td><td>tfoot cell 0.1</td></tr></tfoot>'
      . '<tbody>'
      . '<tr><td>TB0 Cell 0.0</td><td>TB0 Cell 0.1</td></tr>'
      . '</tbody>'
      . '<tbody>'
      . '<tr><td>Cell 0.0</td><td>Cell 0.1</td></tr>'
      . '<tr><td>Cell 1.0</td><td>Cell 1.1</td></tr>'
      . '</tbody>'
      . '</table>'
      . '';

    $this->assertIdentical($html_expected, $html = drupal_render($element));

    $element = [
      /* @see theme_themekit_table() */
      '#theme' => 'themekit_table',
      '#attributes' => ['class' => ['theTable']],
      'thead' => [
        '#attributes' => ['class' => ['theTableHead']],
        '#themekit_table_type' => 'thead',
        'head_row' => [
          '#attributes' => ['class' => ['theHeadRow']],
          'col_0' => ['#markup' => 'Col 0'],
          'col_1' => [
            '#attributes' => ['class' => ['theHeadCell1']],
            '#markup' => 'Col 1',
            '#themekit_table_type' => 'th',
          ],
        ],
      ],
      'body_row_0' => [
        '#attributes' => ['class' => ['theFirstBodyRow']],
        'col_0' => [
          '#attributes' => ['class' => ['theCell_0_0']],
          '#markup' => 'Cell 0.0',
        ],
        'col_1' => ['#markup' => 'Cell 0.1'],
      ],
      'body_row_1' => [
        '#attributes' => ['class' => ['theSecondBodyRow']],
        // col_0 will be first, because the order is defined in previous rows.
        'col_1' => ['#markup' => 'Cell 1.1'],
        'col_0' => ['#markup' => 'Cell 1.0'],
      ],
      'body_row_2' => [
        'col_1' => ['#markup' => 'Cell 2.1'],
      ],
    ];

    $html_expected = ''
      . '<table class="theTable">'
      . '<thead class="theTableHead"><tr class="theHeadRow"><td>Col 0</td><th class="theHeadCell1">Col 1</th></tr></thead>'
      . '<tbody>'
      . '<tr class="theFirstBodyRow"><td class="theCell_0_0">Cell 0.0</td><td>Cell 0.1</td></tr>'
      . '<tr class="theSecondBodyRow"><td>Cell 1.0</td><td>Cell 1.1</td></tr>'
      . '<tr><td></td><td>Cell 2.1</td></tr>'
      . '</tbody>'
      . '</table>'
      . '';

    $this->assertIdentical($html_expected, $html = drupal_render($element));
  }

}
