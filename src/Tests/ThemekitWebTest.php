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

    $this->assertTheme($html_expected, 'themekit_item_containers', ['element' => $element]);

    $element_copy = $element;
    $this->assertDrupalRender($html_expected, $element_copy);

    $element['#item_tag_name'] = false;
    $html_expected = 'XYZ';
    $this->assertTheme($html_expected, 'themekit_item_containers', ['element' => $element]);
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

    $this->assertDrupalRender($html_expected, $element);
  }

  public function testLinkWrapper() {

    $element = [
      /* @see theme_themekit_link_wrapper() */
      '#type' => 'themekit_link_wrapper',
      '#path' => 'admin',
      'content_0' => [
        '#markup' => 'Adminis',
      ],
      'content_1' => [
        '#markup' => 'tration',
      ],
    ];

    $html_expected = '<a href="/admin">Administration</a>';

    $this->assertDrupalRender($html_expected, $element);
  }

  public function testItemList() {

    $element = [
      /* @see theme_themekit_item_list() */
      '#theme' => 'themekit_item_list',
      '#tag_name' => 'ol',
      'item_0' => [
        '#markup' => 'Item 0',
      ],
      'item_1' => [
        '#markup' => 'Item 1',
      ],
    ];

    $html_expected = ''
      . '<ol>'
      . '<li>Item 0</li>'
      . '<li>Item 1</li>'
      . '</ol>'
      . '';

    $this->assertDrupalRender($html_expected, $element);

    $element = [
      /* @see theme_themekit_item_list() */
      '#theme' => 'themekit_item_list',
      '#attributes' => ['class' => ['list']],
      '#tag_name' => 'ol',
      'item_0' => [
        '#attributes' => ['class' => ['item-0']],
        '#markup' => 'Item 0',
      ],
      'item_1' => [
        '#markup' => 'Item 1',
      ],
    ];

    $html_expected = ''
      . '<ol class="list">'
      . '<li class="item-0">Item 0</li>'
      . '<li>Item 1</li>'
      . '</ol>'
      . '';

    $this->assertDrupalRender($html_expected, $element);

    $element = [
      /* @see theme_themekit_item_list() */
      '#theme' => 'themekit_item_list',
      '#tag_name' => 'ul',
      '#child_attributes' => ['class' => ['item']],
      'item_0' => [
        '#attributes' => ['class' => ['item-0']],
        '#markup' => 'Item 0',
      ],
      'item_1' => [
        // see what happens for duplicate class.
        '#attributes' => ['class' => ['item']],
        '#markup' => 'Item 1',
      ],
    ];

    $html_expected = ''
      . '<ul>'
      . '<li class="item-0 item">Item 0</li>'
      . '<li class="item">Item 1</li>'
      . '</ul>'
      . '';

    $this->assertDrupalRender($html_expected, $element);
  }

  public function testSeparatorList() {

    $element = [
      /* @see theme_themekit_separator_list() */
      '#theme' => 'themekit_separator_list',
      '#separator' => ' <span class="separator">|</span> ',
      'item_0' => [
        '#markup' => 'Item 0',
      ],
      'item_1' => [
        '#markup' => 'Item 1',
      ],
    ];

    $html_expected = 'Item 0 <span class="separator">|</span> Item 1';

    $this->assertDrupalRender($html_expected, $element);
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

  /**
   * Asserts that a render element produces expected output.
   *
   * @param string $expected
   *   Expected html returned from theme().
   * @param string $hook
   *   Theme hook to pass to theme().
   * @param array $variables
   *   Variables to pass to theme().
   *
   * @return bool
   *
   * @see assertThemeOutput()
   *   This core function is not as good as this one, I claim.
   */
  protected function assertTheme($expected, $hook, array $variables) {

    $message = ''
      . '<pre>theme(@hook, @variables)</pre>'
      . '';

    $replacements = [
      '@expected' => var_export($expected, TRUE),
      '@hook' => var_export($hook, TRUE),
      '@variables' => var_export($variables, TRUE),
    ];

    try {
      $actual = theme($hook, $variables);

      $replacements['@actual'] = var_export($actual, TRUE);

      if ($actual !== $expected) {
        $success = FALSE;
        $message .= ''
          . '<hr/>'
          . 'Output: <pre>@actual</pre>'
          . '<hr/>'
          . 'Expected: <pre>@expected</pre>'
          . '';
      }
      else {
        $success = TRUE;
        $message .= ''
          . '<hr/>'
          . 'Output: <pre>@expected</pre>'
          . '';
      }
    }
    catch (\Exception $e) {
      $success = FALSE;
      $replacements['@exception'] = _drupal_render_exception_safe($e);
      $message .= ''
        . '<hr/>'
        . 'Exception: @exception'
        . '<hr/>'
        . 'Expected: <pre>@expected</pre>'
        . '';
    }

    return $this->assert(
      $success,
      format_string($message, $replacements));
  }

  /**
   * Asserts that a render element produces expected output.
   *
   * @param string $expected
   *   Expected html to be returned from drupal_render().
   * @param array $element
   *   Render element to pass to drupal_render().
   *
   * @return bool
   */
  protected function assertDrupalRender($expected, array $element) {

    $message = ''
      . '<pre>drupal_render(@element)</pre>'
      . '';

    $replacements = [
      '@expected' => var_export($expected, TRUE),
      '@element' => var_export($element, TRUE),
    ];

    try {
      $actual = drupal_render($element);

      $replacements['@actual'] = var_export($actual, TRUE);

      if ($actual !== $expected) {
        $success = FALSE;
        $message .= ''
          . '<hr/>'
          . 'Output: <pre>@actual</pre>'
          . '<hr/>'
          . 'Expected: <pre>@expected</pre>'
          . '';
      }
      else {
        $success = TRUE;
        $message .= ''
          . '<hr/>'
          . 'Output as expected: <pre>@expected</pre>'
          . '';
      }
    }
    catch (\Exception $e) {
      $success = FALSE;
      $replacements['@exception'] = _drupal_render_exception_safe($e);
      $message .= ''
        . '<hr/>'
        . 'Exception: @exception'
        . '<hr/>'
        . 'Expected: <pre>@expected</pre>'
        . '';
    }

    return $this->assert(
      $success,
      format_string($message, $replacements));
  }

}
