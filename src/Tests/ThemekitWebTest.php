<?php

namespace Drupal\themekit\Tests;

use Drupal\themekit\Callback\Callback_ElementReparent;

class ThemekitWebTest extends \DrupalWebTestCase {

  public function setUp() {
    parent::setUp('themekit');
  }

  public static function getInfo() {
    // Note: getInfo() strings should not be translated.
    return [
      'name' => 'Themekit web test',
      'description' => 'Tests theme functions provided by themekit.',
      'group' => 'Themekit',
    ];
  }

  public function testThemekitItemContainers() {

    $element = [
      /* @see theme_themekit_item_containers() */
      '#theme' => 'themekit_item_containers',
      '#item_attributes' => ['class' => ['field-item']],
      '#first' => 'field-item-first',
      '#last' => 'field-item-last',
      '#zebra' => ['field-item-even', 'field-item-odd'],
      ['#markup' => 'X'],
      ['#markup' => 'Y'],
      ['#markup' => 'Z'],
    ];

    $html_expected = ''
      . '<div class="field-item field-item-even field-item-first">X</div>'
      . '<div class="field-item field-item-odd">Y</div>'
      . '<div class="field-item field-item-even field-item-last">Z</div>'
      . '';

    $this->assertIdentical($html_expected, theme('themekit_item_containers', ['element' => $element]));

    $element_copy = $element;
    $this->assertIdentical($html_expected, drupal_render($element_copy));

    $element['#item_tag_name'] = false;
    $html_expected = 'XYZ';
    $this->assertIdentical($html_expected, theme('themekit_item_containers', ['element' => $element]));
  }

  public function testThemekitItemContainersWithContainer() {

    $element = [
      // Outer wrapper <ol>.
      /* @see theme_themekit_container() */
      '#type' => 'themekit_container',
      '#tag_name' => 'ul',
      '#attributes' => ['class' => ['menu']],
      // Wrap each item in <li>.
      /* @see theme_themekit_item_containers() */
      '#theme' => 'themekit_item_containers',
      '#item_tag_name' => 'li',
      '#zebra' => TRUE,
      // Items.
      ['#markup' => 'X'],
      ['#markup' => 'Y'],
      ['#markup' => 'Z'],
    ];

    $html_expected = ''
      . '<ul class="menu">'
      . '<li class="even">X</li>'
      . '<li class="odd">Y</li>'
      . '<li class="even">Z</li>'
      . '</ul>'
      . '';

    $this->assertIdentical($html_expected, $html = drupal_render($element));
  }

  public function testThemekitProcessReparent() {

    $form_orig = [
      'group_a' => [
        '#tree' => TRUE,
        'text' => [
          '#type' => 'textfield',
        ],
      ],
    ];

    // First run without any reparenting.
    $form = $this->buildForm($form_orig);
    $this->assertIdentical(['group_a'], $form['group_a']['#array_parents']);
    $this->assertIdentical(['group_a'], $form['group_a']['#parents']);
    $this->assertFalse(isset($form['group_a']['#name']));
    $this->assertIdentical(['group_a', 'text'], $form['group_a']['text']['#array_parents']);
    $this->assertIdentical(['group_a', 'text'], $form['group_a']['text']['#parents']);
    $this->assertIdentical('group_a[text]', $form['group_a']['text']['#name']);

    // Assign THEMEKIT_POP_PARENT.
    $form_orig['group_a']['#process'] = [THEMEKIT_POP_PARENT];

    // Run again with reparented elements.
    $form = $this->buildForm($form_orig);
    $this->assertIdentical(['group_a'], $form['group_a']['#array_parents']);
    $this->assertIdentical([], $form['group_a']['#parents']);
    $this->assertFalse(isset($form['group_a']['#name']));
    $this->assertIdentical(['group_a', 'text'], $form['group_a']['text']['#array_parents']);
    $this->assertIdentical(['text'], $form['group_a']['text']['#parents']);
    $this->assertIdentical('text', $form['group_a']['text']['#name']);

    // Now assign reparent.
    $form_orig['group_a']['#process'][] = new Callback_ElementReparent(1, ['group_b']);

    // Run again with reparented elements.
    $form = $this->buildForm($form_orig);
    $this->assertIdentical(['group_a'], $form['group_a']['#array_parents']);
    $this->assertIdentical(['group_b'], $form['group_a']['#parents']);
    $this->assertFalse(isset($form['group_a']['#name']));
    $this->assertIdentical(['group_a', 'text'], $form['group_a']['text']['#array_parents']);
    $this->assertIdentical(['group_b', 'text'], $form['group_a']['text']['#parents']);
    $this->assertIdentical('group_b[text]', $form['group_a']['text']['#name']);
  }

  private function buildForm(array $form) {
    $form_id = '?';
    $form_state = form_state_defaults() + ['values' => []];
    drupal_prepare_form($form_id, $form, $form_state);

    // Clear out all group associations as these might be different when
    // re-rendering the form.
    $form_state['groups'] = [];

    // Return a fully built form that is ready for rendering.
    return form_builder($form_id, $form, $form_state);
  }

}
