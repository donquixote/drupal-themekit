<?php

/**
 * Theme hook implementation.
 *
 * @param array $variables
 *   Contains the render array at $variables['element'], and the rendered
 *   children's html at $variables['element']['#children'].
 *
 * @return string
 *   Rendered html.
 *
 * @throws Exception
 *
 * @see theme_container()
 */
function theme_themekit_container($variables) {
  $element = $variables['element'];
  $tagName = isset($element['#tag_name'])
    ? $element['#tag_name']
    : 'div';
  $attributes_str = isset($element['#attributes'])
    ? drupal_attributes($element['#attributes'])
    : '';
  if (empty($element['#children'])) {
    return '';
  }

  return '<' . $tagName . $attributes_str . '>' . $element['#children'] . '</' . $tagName . '>';
}

/**
 * @param array $variables
 *
 * @return string
 *   Rendered html.
 *
 * @throws Exception
 * 
 * @see theme_link()
 */
function theme_themekit_link_wrapper(array $variables) {
  $element = $variables['element'];
  if (!isset($element['#path'])) {
    return '';
  }
  if (empty($element['#children'])) {
    return '';
  }
  $link_options = isset($element['#options'])
    ? $element['#options']
    : [];
  $link_options['html'] = TRUE;
  return l($element['#children'], $element['#path'], $link_options);
}

/**
 * @param array $variables
 *
 * @return string
 */
function theme_themekit_item_containers(array $variables) {

  $element = $variables['element'];

  $deltas = element_children($element);

  if ([] === $deltas) {
    return '';
  }

  if (!isset($element['#item_tag_name'])) {
    $tag_name = 'div';
    $item_close_tag = '</div>';
  }
  elseif (false !== $element['#item_tag_name']) {
    $tag_name = $element['#item_tag_name'];
    $item_close_tag = '</' . $tag_name . '>';
  }
  else {
    $html = '';
    foreach ($deltas as $delta) {
      $html .= drupal_render($element[$delta]);
    }

    return $html;
  }

  $common_attributes = !empty($element['#item_attributes'])
    ? $element['#item_attributes']
    : [];

  if (empty($element['#zebra']) && empty($element['#first']) && empty($element['#last'])) {

    $item_open_tag = [] !== $common_attributes
      ? '<' . $tag_name . drupal_attributes($common_attributes) . '>'
      : '<' . $tag_name . '>';

    $html = '';
    foreach ($deltas as $i => $delta) {
      $html .= $item_open_tag . drupal_render($element[$delta]) . $item_close_tag;
    }

    return $html;
  }

  if (empty($element['#zebra'])) {
    $attributes_by_delta = array_fill_keys($deltas, $common_attributes);
  }
  else {
    $zebra_stripes = is_array($element['#zebra'])
      ? $element['#zebra']
      : ['even', 'odd'];

    $n_zebra_stripes = count($zebra_stripes);

    $attributes_zebra = [];
    foreach ($zebra_stripes as $i => $class) {
      $attributes_zebra[$i] = $common_attributes;
      $attributes_zebra[$i]['class'][] = $class;
    }

    $attributes_by_delta = [];
    foreach ($deltas as $i => $delta) {
      $attributes_by_delta[$delta] = $attributes_zebra[$i % $n_zebra_stripes];
    }
  }

  if (!empty($element['#first'])) {
    $attributes_by_delta[$deltas[0]]['class'][] = is_string($element['#first'])
      ? $element['#first']
      : 'first';
  }

  if (!empty($element['#last'])) {
    $n = count($deltas);
    $attributes_by_delta[$deltas[$n - 1]]['class'][] = is_string($element['#last'])
      ? $element['#last']
      : 'last';
  }

  $html = '';
  foreach ($attributes_by_delta as $delta => $delta_attributes) {
    $item_open_tag = '<' . $tag_name . drupal_attributes($delta_attributes) . '>';
    $html .= $item_open_tag . drupal_render($element[$delta]) . $item_close_tag;
  }

  return $html;
}

/**
 * @param array $variables
 *
 * @return string
 *   Rendered HTML.
 */
function theme_themekit_item_list(array $variables) {
  $element = $variables['element'];
  $default_child_attributes = isset($element['#child_attributes'])
    ? $element['#child_attributes'] + []
    : [];
  $default_child_attributes_str = drupal_attributes($default_child_attributes);

  $html = '';
  foreach (element_children($element) as $key) {
    $child_html = drupal_render($element[$key]);
    if ('' === $child_html) {
      continue;
    }
    if (isset($element[$key]['#attributes'])) {
      $child_attributes = $element[$key]['#attributes'];
      if (isset($child_attributes['class'], $default_child_attributes['class'])) {
        $child_attributes['class'] = array_unique(array_merge($child_attributes['class'], $default_child_attributes['class']));
      }
      $child_attributes += $default_child_attributes;
      $child_attributes_str = drupal_attributes($child_attributes);
    }
    else {
      $child_attributes_str = $default_child_attributes_str;
    }
    $html .= '<li' . $child_attributes_str . '>' . $child_html . '</li>';
  }
  if (isset($element['#tag_name']) && $element['#tag_name'] === 'ol') {
    $tag_name = 'ol';
  }
  else {
    $tag_name = 'ul';
  }

  $attributes_str = isset($element['#attributes'])
    ? drupal_attributes($element['#attributes'])
    : '';

  return '<' . $tag_name . $attributes_str . '>' . $html . '</' . $tag_name . '>';
}

/**
 * @param array $variables
 *
 * @return string
 */
function theme_themekit_separator_list(array $variables) {
  $element = $variables['element'];
  $pieces = [];
  foreach (element_children($element) as $key) {
    $pieces[] = drupal_render($element[$key]);
  }
  $separator = isset($element['#separator'])
    ? $element['#separator']
    : '';
  return implode($separator, $pieces);
}

/**
 * @param array $variables
 *
 * @return string
 */
function theme_themekit_table(array $variables) {
  $table = $variables['element'];

  $col_keys = _themekit_table_collect_col_keys($table);

  $tsections_html = [
    'thead' => '',
    'tfoot' => '',
    'tbody' => '',
  ];
  $tbody_html = '';

  foreach (element_children($table) as $row_or_section_key) {

    if (isset($table[$row_or_section_key]['#themekit_table_type'])) {
      $section_tag_name = $table[$row_or_section_key]['#themekit_table_type'];
      if (isset($tsections_html[$section_tag_name])) {
        $tsection =& $table[$row_or_section_key];
        $section_html = '';
        foreach (element_children($tsection) as $row_key) {
          $trow =& $tsection[$row_key];
          $section_html .= _themekit_table_render_row($trow, $col_keys);
        }

        $tsections_html[$section_tag_name] .= _themekit_render_tag($section_tag_name, $tsection, $section_html);
        continue;
      }
    }

    $tbody_html .= _themekit_table_render_row($table[$row_or_section_key], $col_keys);
  }

  if ('' !== $tbody_html) {
    $tsections_html['tbody'] .= _themekit_render_tag('tbody', [], $tbody_html);
  }

  return _themekit_render_tag('table', $table, implode('', $tsections_html));
}

/**
 * @param array $table
 *
 * @return array
 */
function _themekit_table_collect_col_keys(array $table) {

  $tsection_types = [
    'thead' => 'thead',
    'tfoot' => 'tfoot',
    'tbody' => 'tbody',
  ];

  $col_keys = [];
  foreach (element_children($table) as $row_or_section_key) {
    $row_or_section =& $table[$row_or_section_key];

    if (isset($row_or_section['#themekit_table_type'])) {
      if (isset($tsection_types[$row_or_section['#themekit_table_type']])) {
        foreach (element_children($row_or_section) as $row_key) {
          $cell_keys = element_children($row_or_section[$row_key]);
          $col_keys += array_combine($cell_keys, $cell_keys);
        }
        continue;
      }
    }

    $cell_keys = element_children($row_or_section);
    $col_keys += array_combine($cell_keys, $cell_keys);
  }

  return $col_keys;
}

/**
 * @param array $trow
 *   Render element representing a table row.
 * @param string[] $col_keys
 *
 * @return string
 */
function _themekit_table_render_row(array $trow, array $col_keys) {

  $row_html = '';
  foreach ($col_keys as $col_key) {
    if (!isset($trow[$col_key])) {
      $row_html .= '<td></td>';
      continue;
    }
    $tcell =& $trow[$col_key];
    $cell_html = drupal_render($tcell);

    $cell_tag_name = 'td';
    if (isset($tcell['#themekit_table_type']) && 'th' === $tcell['#themekit_table_type']) {
      $cell_tag_name = 'th';
    }

    $row_html .= _themekit_render_tag($cell_tag_name, $tcell, $cell_html);
  }

  return _themekit_render_tag('tr', $trow, $row_html);
}

/**
 * @param string $tag_name
 * @param array $element
 * @param string $inner_html
 *
 * @return string
 */
function _themekit_render_tag($tag_name, array $element, $inner_html) {
  $attributes = '';
  if (!empty($element['#attributes'])) {
    $attributes = drupal_attributes($element['#attributes']);
  }
  return '<' . $tag_name . $attributes . '>' . $inner_html . '</' . $tag_name . '>';
}
