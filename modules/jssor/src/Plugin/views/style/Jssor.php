<?php

/**
 * @file
 * Definition of Drupal\jssor\Plugin\views\style\Jssor.
 */

namespace Drupal\jssor\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render each item in an ordered or unordered list.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "jssor",
 *   title = @Translation("Jssor Slider"),
 *   help = @Translation("Display rows or entity in a Jssor Slider."),
 *   theme = "jssor",
 *   display_types = {"normal"}
 * )
 */
class Jssor extends StylePluginBase {

  /**
   * Does the style plugin allows to use the row.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Does the style plugin support grouping.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['autoplay'] = array('default' => TRUE);
    $options['autoplayinterval'] = array('default' => 3000);
    $options['autoplaysteps'] = array('default' => 1);
    $options['pauseonhover'] = array('default' => 1);
    $options['arrownavigator'] = array('default' => FALSE);
    $options['bulletnavigator'] = array('default' => FALSE);
    $options['chancetoshow'] = array('default' => 0);
    $options['arrowskin'] = array('default' => 1);
    $options['bulletskin'] = array('default' => 1);
    $options['autocenter'] = array('default' => 0);
    $options['spacingx'] = array('default' => 0);
    $options['spacingy'] = array('default' => 0);
    $options['orientation'] = array('default' => 1);
    $options['steps'] = array('default' => 1);
    $options['lanes'] = array('default' => 1);
    $options['transition'] = array('default' => 'transition001');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['global'] = array(
      '#type' => 'fieldset',
      '#title' => 'Global',
    );
    $form['global']['autoplay'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => (isset($this->options['global']['autoplay'])) ?
        $this->options['global']['autoplay'] : $this->options['autoplay'],
      '#description' => t('Enable to auto play.'),
    );
    $form['global']['autoplayinterval'] = array(
      '#type' => 'number',
      '#title' => $this->t('Autoplay interval'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => (isset($this->options['global']['autoplayinterval'])) ?
          $this->options['global']['autoplayinterval'] : $this->options['autoplayinterval'],
      ),
      '#description' => t('Interval (in milliseconds) to go for next slide since the previous stopped.'),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][autoplay]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['global']['autoplaysteps'] = array(
      '#type' => 'number',
      '#title' => $this->t('Autoplay step'),
      '#attributes' => array(
        'min' => 1,
        'step' => 1,
        'value' => (isset($this->options['global']['autoplaysteps'])) ?
          $this->options['global']['autoplaysteps'] : $this->options['autoplaysteps'],
      ),
      '#description' => t('Steps to go for each navigation request.'),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][autoplay]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['global']['pauseonhover'] = array(
      '#type' => 'select',
      '#title' => $this->t('Pause on hover'),
      '#description' => t('Whether to pause when mouse over if a slider is auto playing.'),
      '#default_value' => (isset($this->options['global']['pauseonhover'])) ?
        $this->options['global']['pauseonhover'] : $this->options['pauseonhover'],
      '#options' => array(
        0 => $this->t('No pause'),
        1 => $this->t('Pause for desktop'),
        2 => $this->t('Pause for touch device'),
        3 => $this->t('Pause for desktop and touch device'),
        4 => $this->t('Freeze for desktop'),
        8 => $this->t('Freeze for touch device'),
        12 => $this->t('Freeze for desktop and touch device'),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][autoplay]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['global']['transition'] = array(
      '#type' => 'select',
      '#title' => $this->t('Transition'),
      '#description' => t('Whether to pause when mouse over if a slider is auto playing.'),
      '#default_value' => (isset($this->options['global']['transition'])) ?
        $this->options['global']['transition'] : $this->options['transition'],
      '#options' => array(
        t('Twins Effects') => array(
          'transition0001' => $this->t('Fade Twins'),
          'transition0002' => $this->t('Rotate Overlap'),
          'transition0003' => $this->t('Switch'),
          'transition0004' => $this->t('Rotate Relay'),
          'transition0005' => $this->t('Doors'),
          'transition0006' => $this->t('Rotate in+ out-'),
          'transition0007' => $this->t('Fly Twins'),
          'transition0008' => $this->t('Rotate in- out+'),
          'transition0009' => $this->t('Rotate Axis up overlap'),
          'transition0010' => $this->t('Chess Replace TB'),
          'transition0011' => $this->t('Chess Replace LR'),
          'transition0012' => $this->t('Shift TB'),
          'transition0013' => $this->t('Shift LR'),
          'transition0014' => $this->t('Return TB'),
          'transition0015' => $this->t('Return LR'),
          'transition0016' => $this->t('Rotate Axis down'),
          'transition0017' => $this->t('Extrude Replace'),
        ),
        t('Fade Effects') => array(
          'transition0101' => $this->t('Fade'),
          'transition0102' => $this->t('Fade in L'),
          'transition0103' => $this->t('Fade in R'),
        ),
        t('Swing Outside Effects') => array(
          'transition0201' => $this->t('Swing Outside in Stairs'),
          'transition0202' => $this->t('Swing Outside in ZigZag'),
          'transition0203' => $this->t('Swing Outside in Swirl'),
          'transition0204' => $this->t('Swing Outside in Random'),
          'transition0205' => $this->t('Swing Outside in Random Chess'),
        ),
        t('Swing Inside Effects') => array(
          'transition0301' => $this->t('Swing Inside in Stairs'),
          'transition0302' => $this->t('Swing Inside in ZigZag'),
          'transition0303' => $this->t('Swing Inside in Swirl'),
          'transition0304' => $this->t('Swing Inside in Random'),
          'transition0305' => $this->t('Swing Inside in Random Chess'),
        ),
        t('Dodge Dance Outside Effects') => array(
          'transition0401' => $this->t('Dodge Dance Outside in Stairs'),
          'transition0402' => $this->t('Dodge Dance Outside in Swirl'),
          'transition0403' => $this->t('Dodge Dance Outside in ZigZag'),
          'transition0404' => $this->t('Dodge Dance Outside in Random'),
          'transition0405' => $this->t('Dodge Dance Outside in Random Chess'),
        ),
        t('Dodge Dance Inside Effects') => array(
          'transition0501' => $this->t('Dodge Dance Inside in Stairs'),
          'transition0502' => $this->t('Dodge Dance Inside in Swirl'),
          'transition0503' => $this->t('Dodge Dance Inside in ZigZag'),
          'transition0504' => $this->t('Dodge Dance Inside in Random'),
          'transition0505' => $this->t('Dodge Dance Inside in Random Chess'),
        ),
        t('Dodge Pet Outside Effects') => array(
          'transition0601' => $this->t('Dodge Pet Outside in Stairs'),
          'transition0602' => $this->t('Dodge Pet Outside in Swirl'),
          'transition0603' => $this->t('Dodge Pet Outside in ZigZag'),
          'transition0604' => $this->t('Dodge Pet Outside in Random'),
          'transition0605' => $this->t('Dodge Pet Outside in Random Chess'),
        ),
        t('Dodge Pet Inside Effects') => array(
          'transition0701' => $this->t('Dodge Pet Inside in Stairs'),
          'transition0702' => $this->t('Dodge Pet Inside in Swirl'),
          'transition0703' => $this->t('Dodge Pet Inside in ZigZag'),
          'transition0704' => $this->t('Dodge Pet Inside in Random'),
          'transition0705' => $this->t('Dodge Pet Inside in Random Chess'),
        ),
        t('Dodge Outside Effects') => array(
          'transition0801' => $this->t('Dodge Outside out Stairs'),
          'transition0802' => $this->t('Dodge Outside out Swirl'),
          'transition0803' => $this->t('Dodge Outside out ZigZag'),
          'transition0804' => $this->t('Dodge Outside out Random'),
          'transition0805' => $this->t('Dodge Outside out Random Chess'),
          'transition0806' => $this->t('Dodge Outside out Square'),
          'transition0807' => $this->t('Dodge Outside in Stairs'),
          'transition0808' => $this->t('Dodge Outside in Swirl'),
          'transition0809' => $this->t('Dodge Outside in ZigZag'),
          'transition0810' => $this->t('Dodge Outside in Random'),
          'transition0811' => $this->t('Dodge Outside in Random Chess'),
          'transition0812' => $this->t('Dodge Outside in Square'),
        ),
        t('Dodge Inside Effects') => array(
          'transition0901' => $this->t('Dodge Inside out Stairs'),
          'transition0902' => $this->t('Dodge Inside out Swirl'),
          'transition0903' => $this->t('Dodge Inside out ZigZag'),
          'transition0904' => $this->t('Dodge Inside out Random'),
          'transition0905' => $this->t('Dodge Inside out Random Chess'),
          'transition0906' => $this->t('Dodge Inside out Square'),
          'transition0907' => $this->t('Dodge Inside in Stairs'),
          'transition0908' => $this->t('Dodge Inside in Swirl'),
          'transition0909' => $this->t('Dodge Inside in ZigZag'),
          'transition0910' => $this->t('Dodge Inside in Random'),
          'transition0911' => $this->t('Dodge Inside in Random Chess'),
          'transition0912' => $this->t('Dodge Inside in Square'),
        ),
        t('Flutter Outside Effects') => array(
          'transition1001' => $this->t('Flutter Outside in'),
          'transition1002' => $this->t('Flutter Outside in Wind'),
          'transition1003' => $this->t('Flutter Outside in Swirl'),
          'transition1004' => $this->t('Flutter Outside in Column'),
          'transition1005' => $this->t('Flutter Outside out'),
          'transition1006' => $this->t('Flutter Outside out Wind'),
          'transition1007' => $this->t('Flutter Outside out Swirl'),
          'transition1008' => $this->t('Flutter Outside out Column'),
        ),
        t('Flutter Inside Effects') => array(
          'transition1101' => $this->t('Flutter Inside in'),
          'transition1102' => $this->t('Flutter Inside in Wind'),
          'transition1103' => $this->t('Flutter Inside in Swirl'),
          'transition1104' => $this->t('Flutter Inside in Column'),
          'transition1105' => $this->t('Flutter Inside out'),
          'transition1106' => $this->t('Flutter Inside out Wind'),
          'transition1107' => $this->t('Flutter Inside out Swirl'),
          'transition1108' => $this->t('Flutter Inside out Column'),
        ),
        t('Rotate Effects') => array(
          'transition1201' => $this->t('Rotate VDouble+ in'),
          'transition1202' => $this->t('Rotate HDouble+ in'),
          'transition1203' => $this->t('Rotate VDouble- in'),
          'transition1204' => $this->t('Rotate HDouble- in'),
          'transition1205' => $this->t('Rotate VDouble+ out'),
          'transition1206' => $this->t('Rotate HDouble+ out'),
          'transition1207' => $this->t('Rotate VDouble- out'),
          'transition1208' => $this->t('Rotate HDouble- out'),
          'transition1209' => $this->t('Rotate VFork+ in'),
          'transition1210' => $this->t('Rotate HFork+ in'),
          'transition1211' => $this->t('Rotate VFork+ out'),
          'transition1212' => $this->t('Rotate HFork+ out'),
          'transition1213' => $this->t('Rotate Zoom+ in'),
          'transition1214' => $this->t('Rotate Zoom+ in L'),
          'transition1215' => $this->t('Rotate Zoom+ in R'),
          'transition1216' => $this->t('Rotate Zoom+ in T'),
          'transition1217' => $this->t('Rotate Zoom+ in B'),
          'transition1218' => $this->t('Rotate Zoom+ in TL'),
          'transition1219' => $this->t('Rotate Zoom+ in TR'),
          'transition1220' => $this->t('Rotate Zoom+ in BL'),
          'transition1221' => $this->t('Rotate Zoom+ in BR'),
          'transition1222' => $this->t('Rotate Zoom+ out'),
          'transition1223' => $this->t('Rotate Zoom+ out L'),
          'transition1224' => $this->t('Rotate Zoom+ out R'),
        ),
        t('Zoom Effects') => array(
          'transition1301' => $this->t('Zoom VDouble+ in'),
          'transition1302' => $this->t('Zoom HDouble+ in'),
          'transition1303' => $this->t('Zoom VDouble- in'),
          'transition1304' => $this->t('Zoom HDouble- in'),
          'transition1305' => $this->t('Zoom VDouble+ out'),
          'transition1306' => $this->t('Zoom HDouble+ out'),
          'transition1307' => $this->t('Zoom VDouble- out'),
          'transition1308' => $this->t('Zoom HDouble- out'),
          'transition1309' => $this->t('Zoom+ in'),
          'transition1310' => $this->t('Zoom+ in L'),
          'transition1311' => $this->t('Zoom+ in R'),
          'transition1312' => $this->t('Zoom+ in T'),
          'transition1313' => $this->t('Zoom+ in B'),
          'transition1314' => $this->t('Zoom+ in TL'),
          'transition1315' => $this->t('Zoom+ in TR'),
          'transition1316' => $this->t('Zoom+ in BL'),
          'transition1317' => $this->t('Zoom+ in BR'),
          'transition1318' => $this->t('Zoom+ out'),
          'transition1319' => $this->t('Zoom+ out L'),
          'transition1320' => $this->t('Zoom+ out R'),
        ),
        t('Collapse Effects') => array(
          'transition1401' => $this->t('Collapse Stairs'),
        ),
        t('Compound Effects') => array(
          'transition1501' => $this->t('Clip &amp; Chess in'),
        ),
        t('Expand Effects') => array(
          'transition1601' => $this->t('Expand Stairs'),
        ),
        t('Stripe Effects') => array(
          'transition1701' => $this->t('Dominoes Stripe'),
        ),
        t('Wave out Effects') => array(
          'transition1801' => $this->t('Wave out'),
        ),
        t('Wave in Effects') => array(
          'transition1901' => $this->t('Wave in'),
        ),
        t('Jump out Effects') => array(
          'transition2001' => $this->t('Jump out Straight'),
          'transition2002' => $this->t('Jump out Swirl'),
          'transition2003' => $this->t('Jump out ZigZag'),
          'transition2004' => $this->t('Jump out Square'),
          'transition2005' => $this->t('Jump out Square with Chess'),
          'transition2006' => $this->t('Jump out Rectangle'),
          'transition2007' => $this->t('Jump out Circle'),
          'transition2008' => $this->t('Jump out Rectangle Cross'),
        ),
        t('Jump in Effects') => array(
          'transition2101' => $this->t('Jump in Straight'),
          'transition2101' => $this->t('Jump in Straight'),
          'transition2102' => $this->t('Jump in Swirl'),
          'transition2103' => $this->t('Jump in ZigZag'),
          'transition2104' => $this->t('Jump in Square'),
          'transition2105' => $this->t('Jump in Square with Chess'),
          'transition2106' => $this->t('Jump in Rectangle'),
          'transition2107' => $this->t('Jump in Circle'),
          'transition2108' => $this->t('Jump in Rectangle Cross'),
        ),
        t('Parabola Effects') => array(
          'transition2201' => $this->t('Parabola Swirl in'),
          'transition2202' => $this->t('Parabola Swirl out'),
          'transition2203' => $this->t('Parabola ZigZag in'),
          'transition2204' => $this->t('Parabola ZigZag out'),
          'transition2205' => $this->t('Parabola Stairs in'),
          'transition2206' => $this->t('Parabola Stairs out'),
        ),
        t('Float Effects') => array(
          'transition2301' => $this->t('Float Right Random'),
          'transition2302' => $this->t('Float up Random'),
          'transition2303' => $this->t('Float up Random with Chess'),
          'transition2304' => $this->t('Float Right ZigZag'),
          'transition2305' => $this->t('Float up ZigZag'),
          'transition2306' => $this->t('Float up ZigZag with Chess'),
          'transition2307' => $this->t('Float Right Swirl'),
          'transition2308' => $this->t('Float up Swirl'),
          'transition2309' => $this->t('Float up Swirl with Chess'),
        ),
        t('Fly Effects') => array(
          'transition2401' => $this->t('Fly Right Random'),
          'transition2402' => $this->t('Fly up Random'),
          'transition2403' => $this->t('Fly up Random with Chess'),
          'transition2404' => $this->t('Fly Right ZigZag'),
          'transition2405' => $this->t('Fly up ZigZag'),
          'transition2406' => $this->t('Fly up ZigZag with Chess'),
          'transition2407' => $this->t('Fly Right Swirl'),
          'transition2408' => $this->t('Fly up Swirl'),
          'transition2409' => $this->t('Fly up Swirl with Chess'),
        ),
        t('Stone Effects') => array(
          'transition2501' => $this->t('Slide Down'),
          'transition2502' => $this->t('Slide Right'),
          'transition2503' => $this->t('Bounce Down'),
          'transition2504' => $this->t('Bounce Right'),
        ),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][autoplay]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['global']['arrownavigator'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable arrow navigator'),
      '#default_value' => (isset($this->options['global']['arrownavigator'])) ?
        $this->options['global']['arrownavigator'] : $this->options['arrownavigator'],
    );
    $form['global']['bulletnavigator'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable bullet navigator'),
      '#default_value' => (isset($this->options['global']['bulletnavigator'])) ?
        $this->options['global']['bulletnavigator'] : $this->options['bulletnavigator'],
    );

    // Arrow navigator.
    $form['arrownavigator'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Arrow navigator'),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][arrownavigator]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $arrowskin = array();
    for ($i = 1 ; $i < 22; $i++) {
      $i = ($i < 10) ? '0' . $i : $i;
      $arrowskin[$i] = $this->t('Arrow ') . $i;
    }
    $form['arrownavigator']['arrowskin'] = array(
      '#type' => 'select',
      '#title' => $this->t('Skin'),
      '#default_value' => (isset($this->options['arrownavigator']['arrowskin'])) ?
        $this->options['arrownavigator']['arrowskin'] : $this->options['arrowskin'],
      '#options' => $arrowskin,
    );
    $form['arrownavigator']['autocenter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Auto center'),
      '#description' => $this->t('Auto center arrows in parent container'),
      '#default_value' => (isset($this->options['arrownavigator']['autocenter'])) ?
        $this->options['arrownavigator']['autocenter'] : $this->options['autocenter'],
      '#options' => array(
        0 => $this->t('No'),
        1 => $this->t('Horizontal'),
        2 => $this->t('Vertical'),
        3 => $this->t('Both'),
      ),
    );
    $form['arrownavigator']['chancetoshow'] = array(
      '#type' => 'select',
      '#title' => $this->t('Chance to show'),
      '#default_value' => (isset($this->options['arrownavigator']['chancetoshow'])) ?
        $this->options['arrownavigator']['chancetoshow'] : $this->options['chancetoshow'],
      '#options' => array(
        0 => $this->t('Never'),
        1 => $this->t('Mouse Over'),
        2 => $this->t('Always'),
      ),
    );

    // Bullet navigator.
    $form['bulletnavigator'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Bullet navigator'),
      '#states' => array(
        'visible' => array(
          ':input[name="style_options[global][bulletnavigator]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $bulletskin = array();
    for ($i = 1 ; $i < 22; $i++) {
      $i = ($i < 10) ? '0' . $i : $i;
      $bulletskin[$i] = $this->t('Bullet ') . $i;
    }
    $form['bulletnavigator']['bulletskin'] = array(
      '#type' => 'select',
      '#title' => $this->t('Skin'),
      '#default_value' => (isset($this->options['bulletnavigator']['bulletskin'])) ?
        $this->options['bulletnavigator']['bulletskin'] : $this->options['bulletskin'],
      '#options' => $bulletskin,
    );
    $form['bulletnavigator']['autocenter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Auto center'),
      '#description' => $this->t('Auto center arrows in parent container'),
      '#default_value' => (isset($this->options['bulletnavigator']['autocenter'])) ?
        $this->options['bulletnavigator']['autocenter'] : $this->options['autocenter'],
      '#options' => array(
        0 => $this->t('No'),
        1 => $this->t('Horizontal'),
        2 => $this->t('Vertical'),
        3 => $this->t('Both'),
      ),
    );
    $form['bulletnavigator']['chancetoshow'] = array(
      '#type' => 'select',
      '#title' => $this->t('Chance to show'),
      '#default_value' => (isset($this->options['bulletnavigator']['chancetoshow'])) ?
        $this->options['bulletnavigator']['chancetoshow'] : $this->options['chancetoshow'],
      '#options' => array(
        0 => $this->t('Never'),
        1 => $this->t('Mouse Over'),
        2 => $this->t('Always'),
      ),
    );
    $form['bulletnavigator']['spacingx'] = array(
      '#type' => 'number',
      '#title' => $this->t('Horizontal space'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => (isset($this->options['bulletnavigator']['spacingx'])) ?
          $this->options['bulletnavigator']['spacingx'] : $this->options['spacingx'],
      ),
      '#description' => t('Horizontal space between each item in pixel.'),
    );
    $form['bulletnavigator']['spacingy'] = array(
      '#type' => 'number',
      '#title' => $this->t('Vertical space'),
      '#attributes' => array(
        'min' => 0,
        'step' => 1,
        'value' => (isset($this->options['bulletnavigator']['spacingy'])) ?
          $this->options['bulletnavigator']['spacingy'] : $this->options['spacingy'],
      ),
      '#description' => t('Vertical space between each item in pixel.'),
    );
    $form['bulletnavigator']['orientation'] = array(
      '#type' => 'select',
      '#title' => $this->t('The orientation of the navigator'),
      '#default_value' => (isset($this->options['bulletnavigator']['orientation'])) ?
        $this->options['bulletnavigator']['orientation'] : $this->options['orientation'],
      '#options' => array(
        1 => $this->t('Horizontal'),
        2 => $this->t('Vertical'),
      ),
    );
    $form['bulletnavigator']['steps'] = array(
      '#type' => 'number',
      '#title' => $this->t('Steps'),
      '#attributes' => array(
        'min' => 1,
        'step' => 1,
        'value' => (isset($this->options['bulletnavigator']['steps'])) ?
          $this->options['bulletnavigator']['steps'] : $this->options['steps'],
      ),
      '#description' => t('Steps to go for each navigation request.'),
    );
    $form['bulletnavigator']['lanes'] = array(
      '#type' => 'number',
      '#title' => $this->t('Lanes'),
      '#attributes' => array(
        'min' => 1,
        'step' => 1,
        'value' => (isset($this->options['bulletnavigator']['lanes'])) ?
          $this->options['bulletnavigator']['lanes'] : $this->options['lanes'],
      ),
      '#description' => t('Specify lanes to arrange items.'),
    );
  }
}
