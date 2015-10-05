/**
 * @file
 * Handles AJAX fetching of views, including filter submission and response.
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  var ScaleSlider = function ScaleSlider( event ) {
    var slider = event.data.slider;
    var selector = event.data.selector;
    var parentwith = $('#' + selector).parent().width();
    slider.$ScaleWidth(parentwith);
  }

  /**
   * Attaches the Jssor behavior to Views.
   */
  Drupal.behaviors.ViewsJssorView = {};
  Drupal.behaviors.ViewsJssorView.attach = function () {
    if (drupalSettings && drupalSettings.views && drupalSettings.views.jssorViews) {
      var jssorViews = drupalSettings.views.jssorViews
      for (var i in jssorViews) {
        if (jssorViews.hasOwnProperty(i)) {
          Drupal.views.instances[i] = new Drupal.views.jssorView(jssorViews[i]);
        }
      }
    }
  };

  Drupal.views = {};
  Drupal.views.instances = {};

  /**
   * Javascript object for a certain view.
   */
  Drupal.views.jssorView = function (settings) {

    var selector = 'slider-dom-id-' + settings.view_dom_id;
    var parentwith = $('#' + selector).parent().width();

    var transitions = {
      transition0001: {$Duration:700,$Opacity:2,$Brother:{$Duration:1000,$Opacity:2}},
      transition0002: {$Duration:1200,$Zoom:11,$Rotate:-1,$Easing:{$Zoom:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:0.5},$Brother:{$Duration:1200,$Zoom:1,$Rotate:1,$Easing:$JssorEasing$.$EaseSwing,$Opacity:2,$Round:{$Rotate:0.5},$Shift:90}},
      transition0003: {$Duration:1400,x:0.25,$Zoom:1.5,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInSine},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:1400,x:-0.25,$Zoom:1.5,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Zoom:$JssorEasing$.$EaseInSine},$Opacity:2,$ZIndex:-10}},
      transition0004: {$Duration:1200,$Zoom:11,$Rotate:1,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:1},$ZIndex:-10,$Brother:{$Duration:1200,$Zoom:11,$Rotate:-1,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Round:{$Rotate:1},$ZIndex:-10,$Shift:600}},
      transition0005: {$Duration:1500,x:0.5,$Cols:2,$ChessMode:{$Column:3},$Easing:{$Left:$JssorEasing$.$EaseInOutCubic},$Opacity:2,$Brother:{$Duration:1500,$Opacity:2}},
      transition0006: {$Duration:1500,x:-0.3,y:0.5,$Zoom:1,$Rotate:0.1,$During:{$Left:[0.6,0.4],$Top:[0.6,0.4],$Rotate:[0.6,0.4],$Zoom:[0.6,0.4]},$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Brother:{$Duration:1000,$Zoom:11,$Rotate:-0.5,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Shift:200}},
      transition0007: {$Duration:1500,x:0.3,$During:{$Left:[0.6,0.4]},$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true,$Brother:{$Duration:1000,x:-0.3,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}},
      transition0008: {$Duration:1500,$Zoom:11,$Rotate:0.5,$During:{$Left:[0.4,0.6],$Top:[0.4,0.6],$Rotate:[0.4,0.6],$Zoom:[0.4,0.6]},$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Brother:{$Duration:1000,$Zoom:1,$Rotate:-0.5,$Easing:{$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Shift:200}},
      transition0009: {$Duration:1200,x:0.25,y:0.5,$Rotate:-0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Brother:{$Duration:1200,x:-0.1,y:-0.7,$Rotate:0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2}},
      transition0010: {$Duration:1600,x:1,$Rows:2,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1600,x:-1,$Rows:2,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}},
      transition0011: {$Duration:1600,y:-1,$Cols:2,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1600,y:1,$Cols:2,$ChessMode:{$Column:12},$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}},
      transition0012: {$Duration:1200,y:1,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1200,y:-1,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}},
      transition0013: {$Duration:1200,x:1,$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Brother:{$Duration:1200,x:-1,$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2}},
      transition0014: {$Duration:1200,y:-1,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:1200,y:-1,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Shift:-100}},
      transition0015: {$Duration:1200,x:1,$Delay:40,$Cols:6,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Left:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Brother:{$Duration:1200,x:1,$Delay:40,$Cols:6,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:{$Top:$JssorEasing$.$EaseInOutQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$ZIndex:-10,$Shift:-100}},
      transition0016: {$Duration:1500,x:-0.1,y:-0.7,$Rotate:0.1,$During:{$Left:[0.6,0.4],$Top:[0.6,0.4],$Rotate:[0.6,0.4]},$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2,$Brother:{$Duration:1000,x:0.2,y:0.5,$Rotate:-0.1,$Easing:{$Left:$JssorEasing$.$EaseInQuad,$Top:$JssorEasing$.$EaseInQuad,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInQuad},$Opacity:2}},
      transition0017: {$Duration:1600,x:-0.2,$Delay:40,$Cols:12,$During:{$Left:[0.4,0.6]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Opacity:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$Outside:true,$Round:{$Top:0.5},$Brother:{$Duration:1000,x:0.2,$Delay:40,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:1028,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Opacity:$JssorEasing$.$EaseInOutQuad},$Opacity:2,$Round:{$Top:0.5}}},
      transition0101: {$Duration:1200,$Opacity:2},
      transition0102: {$Duration:1200,x:0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition0103: {$Duration:1200,x:-0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition0201: {$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}},
      transition0202: {$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}},
      transition0203: {$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}},
      transition0204: {$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}},
      transition0205: {$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:1.3,$Top:2.5}},
      transition0301: {$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}},
      transition0302: {$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}},
      transition0303: {$Duration:1200,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}},
      transition0304: {$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}},
      transition0305: {$Duration:1200,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:3},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:1.3,$Top:2.5}},
      transition0401: {$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0402: {$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0403: {$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0404: {$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0405: {$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0501: {$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}},
      transition0502: {$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}},
      transition0503: {$Duration:1500,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}},
      transition0504: {$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}},
      transition0505: {$Duration:1500,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}},
      transition0601: {$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0602: {$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0603: {$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0604: {$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0605: {$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Outside:true,$Round:{$Left:0.8,$Top:2.5}},
      transition0701: {$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}},
      transition0702: {$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}},
      transition0703: {$Duration:1500,x:0.2,y:-0.1,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseOutQuad},$Round:{$Left:0.8,$Top:2.5}},
      transition0704: {$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Round:{$Left:0.8,$Top:2.5}},
      transition0705: {$Duration:1500,x:0.2,y:-0.1,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInWave,$Top:$JssorEasing$.$EaseInWave,$Clip:$JssorEasing$.$EaseLinear},$Round:{$Left:0.8,$Top:2.5}},
      transition0801: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0802: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0803: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0804: {$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0805: {$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0806: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0807: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0808: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0809: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0810: {$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0811: {$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0812: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump},$Outside:true,$Round:{$Left:0.8,$Top:0.8}},
      transition0901: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0902: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0903: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0904: {$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0905: {$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0906: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0907: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0908: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0909: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0910: {$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0911: {$Duration:1200,x:0.3,y:-0.3,$Delay:80,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Assembly:260,$ChessMode:{$Column:15,$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition0912: {$Duration:1200,x:0.3,y:-0.3,$Delay:20,$Cols:8,$Rows:4,$Clip:15,$During:{$Left:[0.2,0.8],$Top:[0.2,0.8]},$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInJump,$Top:$JssorEasing$.$EaseInJump,$Clip:$JssorEasing$.$EaseSwing},$Round:{$Left:0.8,$Top:0.8}},
      transition1001: {$Duration:1800,x:1,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:0.8}},
      transition1002: {$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:1.3}},
      transition1003: {$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:1.3}},
      transition1004: {$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true,$Round:{$Top:2}},
      transition1005: {$Duration:1800,x:1,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:0.8}},
      transition1006: {$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:1.3}},
      transition1007: {$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Outside:true,$Round:{$Top:1.3}},
      transition1008: {$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Outside:true,$Round:{$Top:2}},
      transition1101: {$Duration:1800,x:1,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7]},$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:0.8}},
      transition1102: {$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:1.3}},
      transition1103: {$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:1.3}},
      transition1104: {$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Top:2}},
      transition1105: {$Duration:1800,x:1,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7]},$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseInOutExpo,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:0.8}},
      transition1106: {$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:1.3}},
      transition1107: {$Duration:1800,x:1,y:0.2,$Delay:30,$Cols:10,$Rows:5,$Clip:15,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2050,$Easing:{$Left:$JssorEasing$.$EaseInOutSine,$Top:$JssorEasing$.$EaseOutWave,$Clip:$JssorEasing$.$EaseInOutQuad},$Round:{$Top:1.3}},
      transition1108: {$Duration:1500,x:0.2,y:-0.1,$Delay:150,$Cols:12,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutWave,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2,$Round:{$Top:2}},
      transition1201: {$Duration:1200,x:-1,y:2,$Rows:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}},
      transition1202: {$Duration:1200,x:2,y:1,$Cols:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1203: {$Duration:1200,x:-0.5,y:1,$Rows:2,$Zoom:1,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1204: {$Duration:1200,x:0.5,y:0.3,$Cols:2,$Zoom:1,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1205: {$Duration:1000,x:-1,y:2,$Rows:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.85}},
      transition1206: {$Duration:1000,x:4,y:2,$Cols:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}},
      transition1207: {$Duration:1000,x:-0.5,y:1,$Rows:2,$Zoom:1,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1208: {$Duration:1000,x:0.5,y:0.3,$Cols:2,$Zoom:1,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1209: {$Duration:1200,x:-4,y:2,$Rows:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Row:28},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1210: {$Duration:1200,x:1,y:2,$Cols:2,$Zoom:11,$Rotate:1,$Assembly:2049,$ChessMode:{$Column:19},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.8}},
      transition1211: {$Duration:1000,x:-3,y:1,$Rows:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:28},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1212: {$Duration:1000,x:1,y:2,$Cols:2,$Zoom:11,$Rotate:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:19},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}},
      transition1213: {$Duration:1200,$Zoom:11,$Rotate:1,$Easing:{$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1214: {$Duration:1200,x:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1215: {$Duration:1200,x:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1216: {$Duration:1200,y:4,$Zoom:11,$Rotate:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1217: {$Duration:1200,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1218: {$Duration:1200,x:4,y:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1219: {$Duration:1200,x:-4,y:4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1220: {$Duration:1200,x:4,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1221: {$Duration:1200,x:-4,y:-4,$Zoom:11,$Rotate:1,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad,$Rotate:$JssorEasing$.$EaseInCubic},$Opacity:2,$Round:{$Rotate:0.7}},
      transition1222: {$Duration:1000,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}},
      transition1223: {$Duration:1000,x:4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}},
      transition1224: {$Duration:1000,x:-4,$Zoom:11,$Rotate:1,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear,$Rotate:$JssorEasing$.$EaseInExpo},$Opacity:2,$Round:{$Rotate:0.8}},
      transition1301: {$Duration:1200,y:2,$Rows:2,$Zoom:11,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1302: {$Duration:1200,x:4,$Cols:2,$Zoom:11,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1303: {$Duration:1200,y:1,$Rows:2,$Zoom:1,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1304: {$Duration:1200,x:0.5,$Cols:2,$Zoom:1,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1305: {$Duration:1200,y:2,$Rows:2,$Zoom:11,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1306: {$Duration:1200,x:4,$Cols:2,$Zoom:11,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1307: {$Duration:1200,y:1,$Rows:2,$Zoom:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Row:15},$Easing:{$Top:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1308: {$Duration:1200,x:0.5,$Cols:2,$Zoom:1,$SlideOut:true,$Assembly:2049,$ChessMode:{$Column:15},$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1309: {$Duration:1000,$Zoom:11,$Easing:{$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1310: {$Duration:1000,x:4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1311: {$Duration:1000,x:-4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2,$Round:{$Top:2.5}},
      transition1312: {$Duration:1000,y:4,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1313: {$Duration:1000,y:-4,$Zoom:11,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1314: {$Duration:1000,x:4,y:4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1315: {$Duration:1000,x:-4,y:4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1316: {$Duration:1000,x:4,y:-4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1317: {$Duration:1000,x:-4,y:-4,$Zoom:11,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Zoom:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition1318: {$Duration:1000,$Zoom:11,$SlideOut:true,$Easing:{$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1319: {$Duration:1000,x:4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1320: {$Duration:1000,x:-4,$Zoom:11,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInExpo,$Zoom:$JssorEasing$.$EaseInExpo,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition1401: {$Duration:1000,$Delay:30,$Cols:8,$Rows:4,$Clip:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2049,$Easing:$JssorEasing$.$EaseOutQuad},
      transition1501: {$Duration:1200,y:-1,$Cols:8,$Rows:4,$Clip:15,$During:{$Top:[0.5,0.5],$Clip:[0,0.5]},$Formation:$JssorSlideshowFormations$.$FormationStraight,$ChessMode:{$Column:12},$ScaleClip:0.5},
      transition1601: {$Duration:1000,$Delay:30,$Cols:8,$Rows:4,$Clip:15,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Assembly:2050,$Easing:$JssorEasing$.$EaseInQuad},
      transition1701: {$Duration:2000,y:-1,$Delay:60,$Cols:15,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Easing:$JssorEasing$.$EaseOutJump,$Round:{$Top:1.5}},
      transition1801: {$Duration:1500,y:-0.5,$Delay:60,$Cols:12,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:$JssorEasing$.$EaseInWave,$Round:{$Top:1.5}},
      transition1901: {$Duration:1500,y:-0.5,$Delay:60,$Cols:12,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:$JssorEasing$.$EaseInWave,$Round:{$Top:1.5}},
      transition2001: {$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}},
      transition2002: {$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}},
      transition2003: {$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}},
      transition2004: {$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}},
      transition2005: {$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}},
      transition2006: {$Duration:1500,x:-1,y:0.5,$Delay:800,$Cols:8,$Rows:4,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}},
      transition2007: {$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}},
      transition2008: {$Duration:1500,x:-1,y:0.5,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseLinear,$Top:$JssorEasing$.$EaseOutJump},$Round:{$Top:1.5}},
      transition2101: {$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationStraight,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}},
      transition2102: {$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}},
      transition2103: {$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}},
      transition2104: {$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}},
      transition2105: {$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSquare,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}},
      transition2106: {$Duration:1500,x:-1,y:-0.5,$Delay:800,$Cols:8,$Rows:4,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationRectangle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}},
      transition2107: {$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationCircle,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}},
      transition2108: {$Duration:1500,x:-1,y:-0.5,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationRectangleCross,$Assembly:260,$Easing:{$Left:$JssorEasing$.$EaseSwing,$Top:$JssorEasing$.$EaseInJump},$Round:{$Top:1.5}},
      transition2201: {$Duration:600,x:-1,y:1,$Delay:100,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:264,$Easing:{$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition2202: {$Duration:600,x:-1,y:1,$Delay:100,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:264,$Easing:{$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition2203: {$Duration:600,x:1,y:1,$Delay:60,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition2204: {$Duration:600,x:1,y:1,$Delay:60,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:260,$ChessMode:{$Row:3},$Easing:{$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition2205: {$Duration:600,x:-1,y:1,$Delay:30,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInQuart,$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition2206: {$Duration:600,x:-1,y:1,$Delay:30,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationStraightStairs,$Easing:{$Left:$JssorEasing$.$EaseInQuart,$Top:$JssorEasing$.$EaseInQuart,$Opacity:$JssorEasing$.$EaseLinear},$Opacity:2},
      transition2301: {$Duration:600,x:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2302: {$Duration:600,y:1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2303: {$Duration:600,x:1,y:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2304: {$Duration:600,x:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2305: {$Duration:600,y:1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:264,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2306: {$Duration:600,x:-1,y:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:1028,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2307: {$Duration:600,x:-1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2308: {$Duration:600,y:1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2049,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2309: {$Duration:600,x:1,y:1,$Delay:50,$Cols:8,$Rows:4,$SlideOut:true,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2401: {$Duration:600,x:1,$Delay:50,$Cols:8,$Rows:4,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2402: {$Duration:600,y:-1,$Delay:50,$Cols:8,$Rows:4,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2403: {$Duration:600,x:-1,y:1,$Delay:50,$Cols:8,$Rows:4,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2404: {$Duration:600,x:1,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2405: {$Duration:600,y:-1,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:264,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2406: {$Duration:600,x:1,y:1,$Delay:50,$Cols:8,$Rows:4,$Formation:$JssorSlideshowFormations$.$FormationZigZag,$Assembly:1028,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2407: {$Duration:600,x:1,$Delay:50,$Cols:8,$Rows:4,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2408: {$Duration:600,y:-1,$Delay:50,$Cols:8,$Rows:4,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:2049,$Easing:{$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2409: {$Duration:600,x:-1,y:-1,$Delay:50,$Cols:8,$Rows:4,$Reverse:true,$Formation:$JssorSlideshowFormations$.$FormationSwirl,$Assembly:513,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$JssorEasing$.$EaseInCubic,$Top:$JssorEasing$.$EaseInCubic,$Opacity:$JssorEasing$.$EaseOutQuad},$Opacity:2},
      transition2501: {$Duration:500,y:1,$Easing:$JssorEasing$.$EaseInQuad},
      transition2502: {$Duration:400,x:1,$Easing:$JssorEasing$.$EaseInQuad},
      transition2503: {$Duration:1000,y:1,$Easing:$JssorEasing$.$EaseInBounce},
      transition2504: {$Duration:1000,x:1,$Easing:$JssorEasing$.$EaseInBounce}
    };


    var _SlideshowTransitions = [];
    _SlideshowTransitions.push(transitions[settings.transition]);

    if (settings.$ArrowNavigatorOptions.$Class) {
      settings.$ArrowNavigatorOptions.$Class = $JssorArrowNavigator$;
    }

    if (settings.$BulletNavigatorOptions.$Class) {
      settings.$BulletNavigatorOptions.$Class = $JssorBulletNavigator$;
    }

    if (settings.$SlideshowOptions.$Class) {
      settings.$SlideshowOptions.$Class = $JssorSlideshowRunner$;
      settings.$SlideshowOptions.$Transitions = _SlideshowTransitions;
    }

    var slider = new $JssorSlider$(selector, settings);

    $(window).on( "resize", {
      slider: slider,
      selector: selector
    }, ScaleSlider );

    $(window).on( "load", {
      slider: slider,
      selector: selector
    }, ScaleSlider );

    $(window).on( "orientationchange", {
      slider: slider,
      selector: selector
    }, ScaleSlider );

  };

})(jQuery, Drupal, drupalSettings);
